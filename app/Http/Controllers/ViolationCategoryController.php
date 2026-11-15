<?php

namespace App\Http\Controllers;

use App\Models\ViolationCategory;
use App\Support\CategorySpreadsheet;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ViolationCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $search = $request->string('search')->toString();
        $sort = $request->string('sort')->toString();
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'code', 'name', 'parent', 'severity', 'points', 'status', 'created_at'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'id';

        $query = ViolationCategory::query()
            // Kualifikasi kolom: saat sort=parent ada join alias `pc`,
            // where('name') polos akan ambigu dan memicu SQL error.
            // Pencarian tembus nama induk: ketik "kedis" → P5 Kedisiplinan
            // beserta seluruh sub-nya ikut muncul.
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('violation_categories.name', 'like', "%{$search}%")
                    ->orWhere('violation_categories.code', 'like', "%{$search}%")
                    ->orWhereHas('parent', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            }));

        // "Kategori" (nama induk) pindah tabel — pakai join alias seperti students index.
        if ($sort === 'parent') {
            $query->leftJoin('violation_categories as pc', 'pc.id', '=', 'violation_categories.parent_id')
                ->orderBy('pc.name', $order)
                ->select('violation_categories.*');
        } else {
            $query->orderBy($sort, $order);
        }

        $categories = $query->with('parent')->paginate(20)->withQueryString();

        return view('violation-categories.index', compact('categories', 'search', 'sort', 'order'));
    }

    /**
     * Download a blank XLSX import template.
     */
    public function downloadTemplate()
    {
        $this->authorizeManagement();

        $rows = [[
            'code', 'parent_code', 'name', 'severity', 'points', 'description',
            'sanksi', 'group_type', 'is_recurring', 'recurring_increment',
            'max_recurring_points', 'is_zero_tolerance', 'effective_date',
            'expiry_date', 'status',
        ]];
        $path = Xlsx::write($rows, 'Template Pelanggaran', true);

        return response()->download($path, 'template-kategori-pelanggaran.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export violation categories and their parent codes to XLSX.
     */
    public function export()
    {
        $this->authorizeManagement();

        $rows = [[
            'code', 'parent_code', 'name', 'severity', 'points', 'description',
            'sanksi', 'group_type', 'is_recurring', 'recurring_increment',
            'max_recurring_points', 'is_zero_tolerance', 'effective_date',
            'expiry_date', 'status',
        ]];

        ViolationCategory::with('parent')->orderBy('code')->chunk(500, function ($categories) use (&$rows) {
            foreach ($categories as $category) {
                $rows[] = [
                    $category->code,
                    $category->parent?->code ?? '',
                    $category->name,
                    $category->severity,
                    (string) $category->points,
                    $category->description ?? '',
                    $category->sanksi ?? '',
                    $category->group_type ?? '',
                    $category->is_recurring ? '1' : '0',
                    (string) ($category->recurring_increment ?? ''),
                    (string) ($category->max_recurring_points ?? ''),
                    $category->is_zero_tolerance ? '1' : '0',
                    $category->effective_date?->format('Y-m-d') ?? '',
                    $category->expiry_date?->format('Y-m-d') ?? '',
                    $category->status,
                ];
            }
        });

        $path = Xlsx::write($rows, 'Kategori Pelanggaran', true);

        return response()->download($path, 'kategori-pelanggaran-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import categories from CSV or XLSX without overwriting existing codes.
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
        $requiredHeaders = ['code', 'parent_code', 'name', 'severity', 'points', 'status'];
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
                'code', 'parent_code', 'name', 'severity', 'points', 'description', 'sanksi',
                'group_type', 'is_recurring', 'recurring_increment', 'max_recurring_points',
                'is_zero_tolerance', 'effective_date', 'expiry_date', 'status',
            ] as $field) {
                $data[$field] = trim((string) ($row[$indexes[$field] ?? -1] ?? ''));
            }

            $validator = Validator::make($data, [
                'code' => ['required', 'string', 'max:20'],
                'parent_code' => ['nullable', 'string', 'max:20'],
                'name' => ['required', 'string', 'max:150'],
                'severity' => ['required', 'in:ringan,sedang,berat'],
                'points' => ['required', 'integer', 'min:0', 'max:1000'],
                'description' => ['nullable', 'string'],
                'sanksi' => ['nullable', 'string', 'max:255'],
                'group_type' => ['nullable', 'string', 'max:50'],
                'is_recurring' => ['nullable', 'boolean'],
                'recurring_increment' => ['nullable', 'integer', 'min:0', 'max:1000'],
                'max_recurring_points' => ['nullable', 'integer', 'min:0', 'max:1000'],
                'is_zero_tolerance' => ['nullable', 'boolean'],
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
            if (isset($seenCodes[$codeKey]) || ViolationCategory::query()
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
                        $parent = ViolationCategory::query()
                            ->whereRaw('LOWER(code) = ?', [mb_strtolower($data['parent_code'])])
                            ->whereNull('parent_id')
                            ->first();

                        if (! $parent) {
                            $skipped++;
                            $issues[] = "Baris {$prepared['row']}: kode induk {$data['parent_code']} tidak ditemukan sebagai kategori induk.";

                            continue;
                        }
                    }

                    ViolationCategory::create([
                        'code' => $data['code'],
                        'parent_id' => $parent?->id,
                        'name' => $data['name'],
                        'severity' => $data['severity'],
                        'points' => (int) $data['points'],
                        'description' => $data['description'] ?: null,
                        'sanksi' => $data['sanksi'] ?: null,
                        'group_type' => $data['group_type'] ?: 'kedisiplinan',
                        'is_recurring' => $this->importBoolean($data['is_recurring']),
                        'recurring_increment' => $data['recurring_increment'] !== '' ? (int) $data['recurring_increment'] : null,
                        'max_recurring_points' => $data['max_recurring_points'] !== '' ? (int) $data['max_recurring_points'] : null,
                        'is_zero_tolerance' => $this->importBoolean($data['is_zero_tolerance']),
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

    private function importBoolean(string $value): bool
    {
        return $value !== '' && filter_var($value, FILTER_VALIDATE_BOOLEAN);
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

    /**
     * Memvalidasi input kategori, termasuk DUA bentuk sumber poin:
     * - poin_ke_1/2/3 (form baru, 3 batas toleransi pengulangan)
     * - points (form lama / API) sebagai fallback batas pertama
     */
    private function validateCategory(Request $request, ?ViolationCategory $existing = null): array
    {
        $uniqueCode = $existing
            ? 'unique:violation_categories,code,'.$existing->id
            : 'unique:violation_categories,code';

        return $request->validate([
            'code' => [$existing ? 'sometimes' : 'required', 'required', 'string', 'max:20', $uniqueCode],
            'name' => [$existing ? 'sometimes' : 'required', 'required', 'string', 'max:150'],
            'parent_id' => $existing
                ? ['nullable', 'exists:violation_categories,id', Rule::notIn([$existing->id])]
                : ['nullable', 'exists:violation_categories,id'],
            'severity' => [$existing ? 'sometimes' : 'required', 'required', 'in:ringan,sedang,berat,sangat_berat'],
            'points' => ['nullable', 'integer', 'min:0', 'max:1000'],
            // 0 atau kosong = tidak ada toleransi untuk batas itu.
            // tierColumns() mengabaikan 0/non-naik, jadi ke-3=0 → berhenti di ke-2.
            'poin_ke_1' => ['nullable', 'integer', 'min:0', 'max:1000', 'required_without:points'],
            'poin_ke_2' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'poin_ke_3' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'description' => ['nullable', 'string'],
            'status' => [$existing ? 'sometimes' : 'required', 'required', 'in:draft,active'],
        ]);
    }

    /**
     * Ubah 3 batas toleransi (poin ke-I/II/III) menjadi kolom penyimpanan.
     *
     * Batas yang tidak naik dari batas sebelumnya diabaikan, sehingga kategori
     * bisa dibuat non-berulang (hanya ke-I) atau berulang sampai 3 batas.
     * Kolom: points = ke-I, recurring_increment = ke-II − ke-I,
     *        max_recurring_points = ke-III (nilai persis).
     */
    private function tierColumns(array $input): array
    {
        $first = (int) ($input['poin_ke_1'] ?? $input['points'] ?? 0);

        $tiers = [$first];
        foreach ([$input['poin_ke_2'] ?? null, $input['poin_ke_3'] ?? null] as $candidate) {
            if ($candidate !== null && (int) $candidate > end($tiers)) {
                $tiers[] = (int) $candidate;
            }
        }

        $escalating = count($tiers) > 1;

        return [
            'points' => $tiers[0],
            'is_recurring' => $escalating,
            'recurring_increment' => $escalating ? $tiers[1] - $tiers[0] : null,
            'max_recurring_points' => count($tiers) > 2 ? $tiers[2] : null,
        ];
    }

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
            'Hanya admin / kesiswaan / kepala sekolah yang boleh mengubah master tata tertib.',
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorizeManagement();

        // Daftar kategori induk untuk dropdown "Induk Kategori".
        $parents = ViolationCategory::whereNull('parent_id')->orderBy('name')->get();

        return view('violation-categories.create', compact('parents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeManagement();

        $validated = $this->validateCategory($request);

        $data = collect($validated)->except(['poin_ke_1', 'poin_ke_2', 'poin_ke_3'])->all();
        $data = array_merge($data, $this->tierColumns($validated));

        ViolationCategory::create($data);

        // mengarahkan ke halaman ke -
        return redirect()
            ->route('violation-categories.index')
            ->with('success', 'Kategori pelanggaran '.$validated['code'].' berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ViolationCategory $violationCategory)
    {
        $this->authorizeManagement();

        // Kecualikan dirinya sendiri agar tidak bisa jadi induk diri sendiri.
        $parents = ViolationCategory::whereNull('parent_id')
            ->where('id', '!=', $violationCategory->id)
            ->orderBy('name')->get();

        return view('violation-categories.edit', compact('violationCategory', 'parents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ViolationCategory $violationCategory)
    {
        $this->authorizeManagement();

        $validated = $this->validateCategory($request, $violationCategory);

        $data = collect($validated)->except(['poin_ke_1', 'poin_ke_2', 'poin_ke_3'])->all();
        $data = array_merge($data, $this->tierColumns($validated));

        $violationCategory->update($data);

        return redirect()
            ->route('violation-categories.index')
            ->with('success', 'Berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ViolationCategory $violationCategory)
    {
        $this->authorizeManagement();

        // Sub-kategori masih menunjuk ke kategori ini — tolak agar tidak jadi yatim.
        if ($violationCategory->children()->exists()) {
            return redirect()
                ->route('violation-categories.index')
                ->with('error', 'Kategori '.$violationCategory->code.' masih memiliki sub-kategori. Hapus sub-kategorinya dulu.');
        }

        $violationCategory->delete();

        return redirect()
            ->route('violation-categories.index')
            ->with('success', 'Berhasil dihapus');
    }

    /**
     * Rekomendasi pemulihan otomatis untuk satu sub-kategori (JSON).
     * Dipakai form kasus: pilih sub → panel sanksi + rekomendasi tampil sendiri.
     */
    public function rekomendasi(ViolationCategory $violationCategory)
    {
        $this->authorizeStaff();

        return response()->json([
            'id' => $violationCategory->id,
            'code' => $violationCategory->code,
            'name' => $violationCategory->name,
            'severity' => $violationCategory->severity,
            'points' => $violationCategory->points,
            // 3 batas toleransi: [ke-I, ke-II, ke-III] — dipakai panel form kasus.
            'tiers' => $violationCategory->tierPoints(),
            'sanksi' => $violationCategory->resolveSanksi(),
            'is_zero_tolerance' => (bool) $violationCategory->is_zero_tolerance,
            'recovery' => $this->recoveryFor($violationCategory),
        ]);
    }

    /**
     * Sub-prestasi padanan untuk satu sub-kategori.
     * Delegasi ke model (satu sumber kebenaran).
     * Public agar dipakai DisciplineCaseController@show.
     */
    public function recoveryFor(ViolationCategory $category): array
    {
        return $category->recoveryOptions();
    }
}
