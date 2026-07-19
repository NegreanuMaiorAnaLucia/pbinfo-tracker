<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\UserProblemStat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $categories = Category::query()
            ->withCount('problems')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Category $category) use ($userId) {
                $solved = UserProblemStat::query()
                    ->where('user_id', $userId)
                    ->where('status', UserProblemStat::STATUS_SOLVED)
                    ->whereIn('problem_id', $category->problems()->select('problems.id'))
                    ->count();

                $attempted = UserProblemStat::query()
                    ->where('user_id', $userId)
                    ->where('status', UserProblemStat::STATUS_ATTEMPTED)
                    ->whereIn('problem_id', $category->problems()->select('problems.id'))
                    ->count();

                $total = $category->problems_count;
                $percent = $total > 0 ? round(($solved / $total) * 100, 1) : 0;

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'total' => $total,
                    'solved' => $solved,
                    'attempted' => $attempted,
                    'percent' => $percent,
                ];
            })
            ->filter(fn (array $c) => $c['total'] > 0)
            ->values();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }
}
