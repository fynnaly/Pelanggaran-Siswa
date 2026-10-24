<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\AchievementCategory;

class AchievementCategoryController extends Controller
{
    public function index(Request $request)
    {
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
        // Daftar kategori induk untuk dropdown "Induk Kategori".
        $parents = AchievementCategory::whereNull('parent_id')->orderBy('name')->get();

        return view('achievement-categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:achievement_categories,code'],
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'exists:achievement_categories,id'],
            'points' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,active'],
        ]);

        AchievementCategory::create($validated);

        return redirect()
            ->route('achievement-categories.index')
            ->with('success', 'Kategori prestasi ' . $validated['code'] . ' berhasil ditambahkan.');
    }

    public function edit(AchievementCategory $achievementCategory)
    {
        // Kecualikan dirinya sendiri agar tidak bisa jadi induk diri sendiri.
        $parents = AchievementCategory::whereNull('parent_id')
            ->where('id', '!=', $achievementCategory->id)
            ->orderBy('name')->get();

        return view('achievement-categories.edit', compact('achievementCategory', 'parents'));
    }

    public function update(Request $request, AchievementCategory $achievementCategory)
    {
        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:achievement_categories,code,' . $achievementCategory->id],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'parent_id' => [
                'nullable',
                'exists:achievement_categories,id',
                // Self-reference dilarang: kategori tidak boleh jadi induk dirinya sendiri.
                Rule::notIn([$achievementCategory->id]),
            ],
            'points' => ['sometimes', 'required', 'integer', 'min:1'],
            'status' => ['sometimes', 'required', 'in:draft,active'],
        ]);

        $achievementCategory->update($validated);

        return redirect()
            ->route('achievement-categories.index')
            ->with('success', 'Berhasil diupdate');
    }

    public function destroy(AchievementCategory $achievementCategory)
    {
        // Sub-kategori masih menunjuk ke kategori ini — tolak agar tidak jadi yatim.
        if ($achievementCategory->children()->exists()) {
            return redirect()
                ->route('achievement-categories.index')
                ->with('error', 'Kategori ' . $achievementCategory->code . ' masih memiliki sub-kategori. Hapus sub-kategorinya dulu.');
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
