<?php

namespace App\Services\PbInfo;

use App\Models\Category;
use App\Models\Problem;
use App\Models\SyncRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSyncService
{
    public function __construct(private PbInfoClient $client) {}

    public function sync(SyncRun $run, int $maxPagesPerCategory = 30): void
    {
        $run->markRunning();

        try {
            $categories = $this->client->fetchCategories();
            $created = 0;
            $updated = 0;
            $processed = 0;

            DB::transaction(function () use ($categories, $maxPagesPerCategory, &$created, &$updated, &$processed) {
                $sort = 0;

                foreach ($categories as $categoryData) {
                    $sort++;
                    $category = Category::query()->updateOrCreate(
                        ['slug' => $categoryData['slug']],
                        [
                            'pbinfo_id' => $categoryData['pbinfo_id'],
                            'name' => $categoryData['name'],
                            'url' => $categoryData['url'],
                            'sort_order' => $sort,
                            'source_hash' => hash('sha256', $categoryData['url'].'|'.$categoryData['name']),
                        ]
                    );

                    $problems = $this->client->fetchProblemsFromListing($categoryData['url'], $maxPagesPerCategory);

                    foreach ($problems as $problemData) {
                        $processed++;
                        $existing = Problem::query()->where('pbinfo_id', $problemData['pbinfo_id'])->first();

                        $payload = [
                            'category_id' => $category->id,
                            'title' => $problemData['title'],
                            'slug' => $problemData['slug'] ?? Str::slug($problemData['title']),
                            'difficulty' => $problemData['difficulty'],
                            'url' => $problemData['url'],
                            'source_hash' => hash('sha256', $problemData['url'].'|'.$problemData['title']),
                        ];

                        if ($existing) {
                            $existing->fill($payload)->save();
                            $updated++;
                        } else {
                            Problem::query()->create([
                                'pbinfo_id' => $problemData['pbinfo_id'],
                                ...$payload,
                            ]);
                            $created++;
                        }
                    }
                }

                // Also scrape the main problems index once.
                $indexProblems = $this->client->fetchProblemsFromListing(PbInfoClient::BASE_URL.'/probleme', 20);
                foreach ($indexProblems as $problemData) {
                    $processed++;
                    $problem = Problem::query()->firstOrNew(['pbinfo_id' => $problemData['pbinfo_id']]);
                    $wasNew = ! $problem->exists;
                    $problem->fill([
                        'title' => $problemData['title'],
                        'slug' => $problemData['slug'] ?? Str::slug($problemData['title']),
                        'difficulty' => $problem->difficulty ?: $problemData['difficulty'],
                        'url' => $problemData['url'],
                        'source_hash' => hash('sha256', $problemData['url'].'|'.$problemData['title']),
                    ])->save();
                    $wasNew ? $created++ : $updated++;
                }
            });

            $run->markSuccess($processed, $created, $updated);
        } catch (\Throwable $e) {
            $run->markFailed($e->getMessage());
            throw $e;
        }
    }
}
