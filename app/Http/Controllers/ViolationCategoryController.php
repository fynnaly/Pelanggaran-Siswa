<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ViolationCategory;
use App\Models\AchievementCategory;

class ViolationCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
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
     * Memvalidasi input kategori, termasuk DUA bentuk sumber poin:
     * - poin_ke_1/2/3 (form baru, 3 batas toleransi pengulangan)
     * - points (form lama / API) sebagai fallback batas pertama
     */
    private function validateCategory(Request $request, ?ViolationCategory $existing = null): array
    {
        $uniqueCode = $existing
            ? 'unique:violation_categories,code,' . $existing->id
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Daftar kategori induk untuk dropdown "Induk Kategori".
        $parents = ViolationCategory::whereNull('parent_id')->orderBy('name')->get();

        return view('violation-categories.create', compact('parents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateCategory($request);

        $data = collect($validated)->except(['poin_ke_1', 'poin_ke_2', 'poin_ke_3'])->all();
        $data = array_merge($data, $this->tierColumns($validated));

        ViolationCategory::create($data);

        // mengarahkan ke halaman ke -
        return redirect()
        ->route('violation-categories.index')
        ->with('success', 'Kategori pelanggaran ' . $validated['code'] . ' berhasil ditambahkan.');
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
        $validated = $this->validateCategory($request, $violationCategory);

        $data = collect($validated)->except(['poin_ke_1', 'poin_ke_2', 'poin_ke_3'])->all();
        $data = array_merge($data, $this->tierColumns($validated));

        $violationCategory->update($data);

        return redirect()
            ->route('violation-categories.index')
            ->with('success','Berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ViolationCategory $violationCategory)
    {
        // Sub-kategori masih menunjuk ke kategori ini — tolak agar tidak jadi yatim.
        if ($violationCategory->children()->exists()) {
            return redirect()
                ->route('violation-categories.index')
                ->with('error', 'Kategori ' . $violationCategory->code . ' masih memiliki sub-kategori. Hapus sub-kategorinya dulu.');
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
