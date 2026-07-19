<?php

namespace Tests\Unit;

use App\Services\PbInfo\PbInfoClient;
use PHPUnit\Framework\TestCase;

class PbInfoClientTest extends TestCase
{
    public function test_parse_problem_listing_html(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/pbinfo/problems-page.html');
        $client = new PbInfoClient(requestDelayMs: 0);

        $problems = $client->parseProblemListingHtml($html);

        $this->assertCount(3, $problems);
        $this->assertSame(1, $problems[0]['pbinfo_id']);
        $this->assertSame('Sumă', $problems[0]['title']);
        $this->assertStringContainsString('/probleme/1', $problems[0]['url']);
    }

    public function test_aggregate_journal_keeps_best_score(): void
    {
        $payload = json_decode(file_get_contents(__DIR__.'/../Fixtures/pbinfo/journal.json'), true);
        $client = new PbInfoClient(requestDelayMs: 0);
        $entries = [];

        foreach ($payload['content'] as $row) {
            $entries[] = [
                'id' => (int) $row['id'],
                'denumire' => $row['denumire'],
                'scor' => (int) $row['scor'],
                'data' => $row['data'],
            ];
        }

        $aggregated = $client->aggregateJournal($entries);

        $this->assertSame(100, $aggregated[1]['best_score']);
        $this->assertSame(2, $aggregated[1]['attempts']);
        $this->assertSame(70, $aggregated[42]['best_score']);
        $this->assertSame(0, $aggregated[99]['best_score']);
    }

    public function test_extract_logged_in_username_ignores_feed_profile_links(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/pbinfo/logged-in-with-feed.html');
        $client = new PbInfoClient(requestDelayMs: 0);

        $method = new \ReflectionMethod(PbInfoClient::class, 'extractLoggedInUsername');
        $method->setAccessible(true);

        $this->assertSame('ananm_07', $method->invoke($client, $html));
    }
}
