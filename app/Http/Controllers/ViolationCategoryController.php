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
        $sort = $request->string('sort')->toString();
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'code', 'name', 'severity', 'points', 'status', 'created_at'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'id';

        $categories = ViolationCategory::with('parent')
            ->orderBy($sort, $order)->paginate(20)->withQueryString();

        return view('violation-categories.index', compact('categories', 'sort', 'order'));
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
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:violation_categories,code'],
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'exists:violation_categories,id'],
            'severity' => ['required', 'in:ringan,sedang,berat'],
            'points' => ['required', 'integer', 'min:1'],
            'sanksi' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,active'],
        ]);

        ViolationCategory::create($validated);

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
        $validated = $request->validate([
            'code' => ['sometimes','required', 'string', 'max:20', 'unique:violation_categories,code,' . $violationCategory->id],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'parent_id' => [
                'nullable',
                'exists:violation_categories,id',
                // Self-reference dilarang: kategori tidak boleh jadi induk dirinya sendiri.
                Rule::notIn([$violationCategory->id]),
            ],
            'severity' => ['sometimes', 'required', 'in:ringan,sedang,berat'],
            'points' => ['sometimes', 'required', 'integer', 'min:1'],
            'sanksi' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'in:draft,active'],
        ]);

        $violationCategory->update($validated);

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
            'name' => $violationCategory->name,
            'severity' => $violationCategory->severity,
            'points' => $violationCategory->points,
            'sanksi' => $violationCategory->sanksi,
            'is_zero_tolerance' => (bool) $violationCategory->is_zero_tolerance,
            'recovery' => $this->recoveryFor($violationCategory),
        ]);
    }

    /**
     * Sub-prestasi padanan untuk satu sub-kategori.
     * Sumber utama: redemption_rules (dokumen padanan). Fallback: tier severity.
     * Public agar dipakai DisciplineCaseController@show.
     */
    public function recoveryFor(ViolationCategory $category): array
    {
        if ($category->is_zero_tolerance) {
            return [];
        }

        $rules = $category->redemptionRules()->with('achievementCategory')->get();

        if ($rules->isNotEmpty()) {
            return $rules->map(fn ($rule) => [
                'code' => $rule->achievementCategory?->code,
                'name' => $rule->achievementCategory?->name,
                'poin' => $rule->poin_penebus,
            ])->filter(fn ($row) => $row['name'] !== null)->values()->all();
        }

        // Fallback tier severity — kategori tanpa padanan tetap punya rekomendasi.
        $max = match ($category->severity) {
            'ringan' => 10,
            'sedang' => 20,
            'berat' => 1000,
        };

        return AchievementCategory::where('status', 'active')
            ->where('points', '<=', $max)
            ->orderBy('points', 'desc')
            ->limit(3)
            ->get()
            ->map(fn ($ach) => [
                'code' => $ach->code,
                'name' => $ach->name,
                'poin' => $ach->points,
            ])->all();
    }
}
