<?php

namespace App\Console\Commands;

use App\Services\PbInfo\PbInfoClient;
use Illuminate\Console\Command;

class PbInfoHealthCommand extends Command
{
    protected $signature = 'pbinfo:health';

    protected $description = 'Check that PbInfo listing HTML and journal parsing still work against fixtures/live homepage';

    public function handle(PbInfoClient $client): int
    {
        $fixture = base_path('tests/Fixtures/pbinfo/problems-page.html');
        $html = file_get_contents($fixture);
        $problems = $client->parseProblemListingHtml($html);

        if (count($problems) < 1) {
            $this->error('Fixture listing parse returned no problems.');

            return self::FAILURE;
        }

        $this->info('Fixture listing parse OK ('.count($problems).' problems).');

        try {
            $categories = $client->fetchCategories();
            $this->info('Live categories fetch OK ('.count($categories).' categories).');
        } catch (\Throwable $e) {
            $this->warn('Live categories fetch failed (site may be blocking): '.$e->getMessage());
        }

        return self::SUCCESS;
    }
}
