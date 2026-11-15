<?php

namespace App\Http\Controllers;

use App\Models\AchievementCategory;
use App\Support\CategorySpreadsheet;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AchievementCategoryController extends Controller
{
    private const MANAGER_ROLES = ['admin', 'bk', 'kesiswaan', 'kepala_sekolah'];

    /** Daftar role yang dianggap staf sekolah. User tanpa role apa pun = DITOLAK. */
    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    private function authorizeStaff(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::STAFF_ROLES),
            403,
            'Akses ditolak. Fitur ini hanya untuk staf sekolah.'
        );
    }

    private function authorizeManagement(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::MANAGER_ROLES),
            403,
            'Hanya admin / kesiswaan / kepala sekolah yang boleh mengubah master kategori prestasi.',
        );
    }

    public function index(Request $request)
    {
        $this->authorizeStaff();

        $search = request('search');
        $sort = $request->string('sort')->toString();
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'code', 'name', 'parent', 'points', 'status', 'created_at'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'id';

        $query = AchievementCategory::withCount('children')
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('parent', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            }));

        // "Kategori" (nama induk) pindah tabel — join alias, bukan relasi live.
        if ($sort === 'parent') {
            $query->leftJoin('achievement_categories as pc', 'pc.id', '=', 'achievement_categories.parent_id')
                ->orderBy('pc.name', $order)
                ->select('achievement_categories.*');
        } else {
            $query->orderBy($sort, $order);
        }

        $categories = $query->with('parent')->paginate(20)->withQueryString();

        return view('achievement-categories.index', compact('categories', 'search', 'sort', 'order'));
    }

    public function create()
    {
        $this->authorizeManagement();

        // Hanya kategori induk yang boleh menjadi induk langsung.
        $parents = AchievementCategory::whereNull('parent_id')->orderBy('name')->get();

        return view('achievement-categories.create', compact('parents'));
    }

    /**
     * Download a blank XLSX import template.
     */
    public function downloadTemplate()
    {
        $this->authorizeManagement();

        $rows = [[
            'code', 'parent_code', 'name', 'points', 'description', 'group_type',
            'max_points_per_event', 'effective_date', 'expiry_date', 'status',
        ]];
        $path = Xlsx::write($rows, 'Template Prestasi', true);

        return response()->download($path, 'template-kategori-prestasi.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export achievement categories and their parent codes to XLSX.
     */
    public function export()
    {
        $this->authorizeManagement();

        $rows = [[
            'code', 'parent_code', 'name', 'points', 'description', 'group_type',
            'max_points_per_event', 'effective_date', 'expiry_date', 'status',
        ]];

        AchievementCategory::with('parent')->orderBy('code')->chunk(500, function ($categories) use (&$rows) {
            foreach ($categories as $category) {
                $rows[] = [
                    $category->code,
                    $category->parent?->code ?? '',
                    $category->name,
                    (string) $category->points,
                    $category->description ?? '',
                    $category->group_type ?? '',
                    (string) ($category->max_points_per_event ?? ''),
                    $category->effective_date?->format('Y-m-d') ?? '',
                    $category->expiry_date?->format('Y-m-d') ?? '',
                    $category->status,
                ];
            }
        });

        $path = Xlsx::write($rows, 'Kategori Prestasi', true);

        return response()->download($path, 'kategori-prestasi-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import achievement categories from CSV or XLSX without overwriting codes.
     */
    public function import(Request $request)
    {
        $this->authorizeManagement();
        $request->validate(['file' => ['required', 'file', 'max:5120']]);

        $file = $request->file('file');
        if (! in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt', 'xlsx'], true)) {
            return back()->with('error', 'Format file tidak didukung. Gunakan berkas CSV atau XLSX.');
        }

        try {
            $rows = CategorySpreadsheet::read($file);
        } catch (\Throwable $exception) {
            return back()->with('error', 'Gagal membaca berkas kategori: '.$exception->getMessage());
        }

        if (count($rows) < 2) {
            return back()->with('error', 'Berkas kategori tidak berisi baris data.');
        }

        $headers = CategorySpreadsheet::normalizeHeaders(array_shift($rows));
        $requiredHeaders = ['code', 'parent_code', 'name', 'points', 'status'];
        $missingHeaders = array_values(array_diff($requiredHeaders, $headers));
        if ($missingHeaders !== []) {
            return back()->with('error', 'Header wajib belum tersedia: '.implode(', ', $missingHeaders).'.');
        }

        $indexes = array_flip($headers);
        $preparedRows = [];
        $seenCodes = [];
        $issues = [];
        $skipped = 0;

        foreach ($rows as $offset => $row) {
            $rowNumber = $offset + 2;
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $data = [];
            foreach ([
                'code', 'parent_code', 'name', 'points', 'description', 'group_type',
                'max_points_per_event', 'effective_date', 'expiry_date', 'status',
            ] as $field) {
                $data[$field] = trim((string) ($row[$indexes[$field] ?? -1] ?? ''));
            }

            $validator = Validator::make($data, [
                'code' => ['required', 'string', 'max:20'],
                'parent_code' => ['nullable', 'string', 'max:20'],
                'name' => ['required', 'string', 'max:150'],
                'points' => ['required', 'integer', 'min:0', 'max:1000'],
                'description' => ['nullable', 'string'],
                'group_type' => ['nullable', 'string', 'max:50'],
                'max_points_per_event' => ['nullable', 'integer', 'min:0', 'max:1000'],
                'effective_date' => ['nullable', 'date'],
                'expiry_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
                'status' => ['required', 'in:draft,active'],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $issues[] = "Baris {$rowNumber}: ".$validator->errors()->first().'.';

                continue;
            }

            $codeKey = mb_strtolower($data['code']);
            if (isset($seenCodes[$codeKey]) || AchievementCategory::query()
                ->whereRaw('LOWER(code) = ?', [$codeKey])
                ->exists()) {
                $skipped++;
                $issues[] = "Baris {$rowNumber}: kode {$data['code']} sudah ada, baris dilewati.";

                continue;
            }

            if ($data['parent_code'] !== '' && (int) $data['points'] < 1) {
                $skipped++;
                $issues[] = "Baris {$rowNumber}: sub-kategori harus memiliki poin minimal 1.";

                continue;
            }

            $seenCodes[$codeKey] = true;
            $data['parent_code'] = $data['parent_code'] !== '' ? $data['parent_code'] : null;
            $preparedRows[] = ['row' => $rowNumber, 'data' => $data];
        }

        $imported = 0;
        DB::transaction(function () use ($preparedRows, &$imported, &$skipped, &$issues) {
            foreach ([false, true] as $importChildren) {
                foreach ($preparedRows as $prepared) {
                    $data = $prepared['data'];
                    if (($data['parent_code'] !== null) !== $importChildren) {
                        continue;
                    }

                    $parent = null;
                    if ($data['parent_code'] !== null) {
                        $parent = AchievementCategory::query()
                            ->whereRaw('LOWER(code) = ?', [mb_strtolower($data['parent_code'])])
                            ->whereNull('parent_id')
                            ->first();

                        if (! $parent) {
                            $skipped++;
                            $issues[] = "Baris {$prepared['row']}: kode induk {$data['parent_code']} tidak ditemukan sebagai kategori induk.";

                            continue;
                        }
                    }

                    AchievementCategory::create([
                        'code' => $data['code'],
                        'parent_id' => $parent?->id,
                        'name' => $data['name'],
                        'points' => (int) $data['points'],
                        'description' => $data['description'] ?: null,
                        'group_type' => $data['group_type'] ?: 'akademik',
                        'max_points_per_event' => $data['max_points_per_event'] !== '' ? (int) $data['max_points_per_event'] : null,
                        'effective_date' => $data['effective_date'] ?: null,
                        'expiry_date' => $data['expiry_date'] ?: null,
                        'status' => $data['status'],
                    ]);
                    $imported++;
                }
            }
        });

        $message = $this->importSummary($imported, $skipped, $issues);

        return back()->with($imported > 0 ? 'success' : 'error', $message);
    }

    /**
     * Build a concise import summary with a limited number of row errors.
     *
     * @param  array<int, string>  $issues
     */
    private function importSummary(int $imported, int $skipped, array $issues): string
    {
        $message = "Import selesai: {$imported} kategori ditambahkan, {$skipped} dilewati.";
        if ($issues !== []) {
            $message .= ' '.implode(' ', array_slice($issues, 0, 5));
            if (count($issues) > 5) {
                $message .= ' Dan '.(count($issues) - 5).' masalah lainnya.';
            }
        }

        return $message;
    }

    public function store(Request $request)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:achievement_categories,code'],
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => [
                'nullable',
                Rule::exists('achievement_categories', 'id')->where(fn ($query) => $query->whereNull('parent_id')),
            ],
            'points' => [
                'required',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $parentId = $request->input('parent_id');
                    if ($parentId !== null && $parentId !== '' && (int) $value < 1) {
                        $fail('Sub-kategori prestasi harus memiliki poin minimal 1.');
                    }
                },
            ],
            'status' => ['required', 'in:draft,active'],
        ]);

        AchievementCategory::create($validated);

        return redirect()
            ->route('achievement-categories.index')
            ->with('success', 'Kategori prestasi '.$validated['code'].' berhasil ditambahkan.');
    }

    public function edit(AchievementCategory $achievementCategory)
    {
        $this->authorizeManagement();

        // Kecualikan dirinya sendiri agar tidak bisa jadi induk diri sendiri.
        $parents = AchievementCategory::whereNull('parent_id')
            ->where('id', '!=', $achievementCategory->id)
            ->orderBy('name')->get();

        return view('achievement-categories.edit', compact('achievementCategory', 'parents'));
    }

    public function update(Request $request, AchievementCategory $achievementCategory)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:achievement_categories,code,'.$achievementCategory->id],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'parent_id' => [
                'nullable',
                Rule::exists('achievement_categories', 'id')->where(fn ($query) => $query->whereNull('parent_id')),
                // Self-reference dilarang: kategori tidak boleh jadi induk dirinya sendiri.
                Rule::notIn([$achievementCategory->id]),
                function (string $attribute, mixed $value, \Closure $fail) use ($achievementCategory) {
                    if ($value !== null && $value !== '' && $achievementCategory->children()->exists()) {
                        $fail('Kategori yang memiliki sub-kategori tidak dapat dipindahkan ke bawah kategori lain.');
                    }
                },
            ],
            'points' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $achievementCategory) {
                    $parentId = $request->input('parent_id', $achievementCategory->parent_id);
                    if ($parentId !== null && $parentId !== '' && (int) $value < 1) {
                        $fail('Sub-kategori prestasi harus memiliki poin minimal 1.');
                    }
                },
            ],
            'status' => ['sometimes', 'required', 'in:draft,active'],
        ]);

        $achievementCategory->update($validated);

        return redirect()
            ->route('achievement-categories.index')
            ->with('success', 'Berhasil diupdate');
    }

    public function destroy(AchievementCategory $achievementCategory)
    {
        $this->authorizeManagement();

        // Sub-kategori masih menunjuk ke kategori ini — tolak agar tidak jadi yatim.
        if ($achievementCategory->children()->exists()) {
            return redirect()
                ->route('achievement-categories.index')
                ->with('error', 'Kategori '.$achievementCategory->code.' masih memiliki sub-kategori. Hapus sub-kategorinya dulu.');
        }

        if ($achievementCategory->achievementRecords()->exists()) {
            return redirect()
                ->route('achievement-categories.index')
                ->with('error', 'Kategori tidak bisa dihapus karena masih digunakan oleh rekam prestasi.');
        }

        $achievementCategory->delete();

        return redirect()
            ->route('achievement-categories.index')
            ->with('success', 'Berhasil dihapus');
    }
}
