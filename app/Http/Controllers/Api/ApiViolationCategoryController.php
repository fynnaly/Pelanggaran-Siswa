<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ViolationCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiViolationCategoryController extends Controller
{
    /**
     * Daftar kategori pelanggaran untuk dropdown form & buku tata tertib.
     */
    public function index(Request $request): JsonResponse
    {
        $grouped = filter_var($request->query('grouped', 'true'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($grouped === null) {
            $grouped = true;
        }

        if ($grouped) {
            $categories = ViolationCategory::query()
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->orderBy('code')])
                ->orderBy('code')
                ->get();

            return response()->json([
                'data' => $categories->map(fn (ViolationCategory $cat) => [
                    'id' => $cat->id,
                    'code' => $cat->code,
                    'name' => $cat->name,
                    'sub_categories' => $cat->children->map(fn (ViolationCategory $child) => [
                        'id' => $child->id,
                        'code' => $child->code,
                        'name' => $child->name,
                        'points' => (int) $child->points,
                        'is_recurring' => (bool) $child->is_recurring,
                    ])->values(),
                ])->values(),
            ]);
        }

        $subCategories = ViolationCategory::query()
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        return response()->json([
            'data' => $subCategories->map(fn (ViolationCategory $sub) => [
                'id' => $sub->id,
                'code' => $sub->code,
                'name' => $sub->name,
                'points' => (int) $sub->points,
                'is_recurring' => (bool) $sub->is_recurring,
            ])->values(),
        ]);
    }
}
