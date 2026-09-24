<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AchievementCategory;

class AchievementCategoryController extends Controller
{
    public function index()
    {
        $search = request('search');
        $categories = AchievementCategory::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest('code')
            ->paginate(20)
            ->withQueryString();

        return view('achievement-categories.index', compact('categories', 'search'));
    }

    public function create()
    {
        return view('achievement-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:achievement_categories,code'],
            'name' => ['required', 'string', 'max:150'],
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
        return view('achievement-categories.edit', compact('achievementCategory'));
    }

    public function update(Request $request, AchievementCategory $achievementCategory)
    {
        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:achievement_categories,code,' . $achievementCategory->id],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
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
