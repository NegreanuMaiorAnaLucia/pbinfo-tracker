<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Problem;
use App\Models\UserProblemStat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProblemController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $categoryId = $request->query('category');
        $difficulty = (string) $request->query('difficulty', 'all');

        $query = Problem::query()
            ->with(['category:id,name,slug'])
            ->leftJoin('user_problem_stats', function ($join) use ($user) {
                $join->on('user_problem_stats.problem_id', '=', 'problems.id')
                    ->where('user_problem_stats.user_id', '=', $user->id);
            })
            ->select([
                'problems.*',
                'user_problem_stats.best_score',
                'user_problem_stats.status as user_status',
                'user_problem_stats.attempts',
                'user_problem_stats.last_submission_at',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('problems.title', 'like', "%{$search}%")
                    ->orWhere('problems.pbinfo_id', $search);
            });
        }

        if ($categoryId) {
            $query->where('problems.category_id', $categoryId);
        }

        if ($difficulty !== 'all' && $difficulty !== '') {
            $query->where('problems.difficulty', $difficulty);
        }

        if ($status === 'solved') {
            $query->where('user_problem_stats.status', UserProblemStat::STATUS_SOLVED);
        } elseif ($status === 'attempted') {
            $query->where('user_problem_stats.status', UserProblemStat::STATUS_ATTEMPTED);
        } elseif ($status === 'unsolved') {
            $query->where(function ($q) {
                $q->whereNull('user_problem_stats.id')
                    ->orWhere('user_problem_stats.status', UserProblemStat::STATUS_UNSOLVED);
            });
        }

        $problems = $query
            ->orderByRaw("CASE WHEN user_problem_stats.status = 'solved' THEN 2 WHEN user_problem_stats.status = 'attempted' THEN 1 ELSE 0 END ASC")
            ->orderBy('problems.pbinfo_id')
            ->paginate(40)
            ->withQueryString()
            ->through(fn (Problem $problem) => [
                'id' => $problem->pbinfo_id,
                'title' => $problem->title,
                'url' => $problem->url .'/'.$problem->slug,
                'difficulty' => $problem->difficulty,
                'category' => $problem->category?->name,
                'score' => $problem->best_score ?? 0,
                'status' => $problem->user_status ?? UserProblemStat::STATUS_UNSOLVED,
                'attempts' => $problem->attempts ?? 0,
            ]);

        return Inertia::render('Problems/Index', [
            'problems' => $problems,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'category' => $categoryId,
                'difficulty' => $difficulty,
            ],
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'difficulties' => Problem::query()->whereNotNull('difficulty')->distinct()->orderBy('difficulty')->pluck('difficulty'),
        ]);
    }
}
