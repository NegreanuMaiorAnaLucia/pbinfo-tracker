<?php

namespace App\Services\PbInfo;

use App\Services\PbInfo\Exceptions\PbInfoAuthException;
use App\Services\PbInfo\Exceptions\PbInfoRequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class PbInfoClient
{
    public const BASE_URL = 'https://www.pbinfo.ro';

    private Client $http;

    private CookieJar $cookies;

    private int $requestDelayMs;

    private int $maxRetries;

    public function __construct(?Client $http = null, int $requestDelayMs = 350, int $maxRetries = 3)
    {
        $this->cookies = new CookieJar;
        $this->requestDelayMs = $requestDelayMs;
        $this->maxRetries = $maxRetries;
        $this->http = $http ?? new Client([
            'base_uri' => self::BASE_URL,
            'timeout' => 30,
            'http_errors' => false,
            'cookies' => $this->cookies,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'ro-RO,ro;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Upgrade-Insecure-Requests' => '1',
            ],
        ]);
    }

    /**
     * @param  array<string, string>  $cookieMap
     */
    public function withCookies(array $cookieMap): self
    {
        foreach ($cookieMap as $name => $value) {
            $this->cookies->setCookie(new \GuzzleHttp\Cookie\SetCookie([
                'Name' => $name,
                'Value' => $value,
                'Domain' => 'www.pbinfo.ro',
                'Path' => '/',
            ]));
        }

        return $this;
    }

    /**
     * @return array{username: string, cookies: array<string, string>}
     */
    public function login(string $username, string $password): array
    {
        $this->request('GET', '/');

        $response = $this->request('POST', '/', [
            'form_params' => [
                'user' => $username,
                'parola' => $password,
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Referer' => self::BASE_URL.'/',
            ],
            'allow_redirects' => true,
        ]);

        $body = (string) $response->getBody();
        $cookieMap = $this->exportCookies();

        if (! isset($cookieMap['SSID']) || $this->looksLoggedOut($body)) {
            throw new PbInfoAuthException('Invalid PbInfo username or password.');
        }

        $resolvedUsername = $this->extractLoggedInUsername($body) ?? $username;

        return [
            'username' => $resolvedUsername,
            'cookies' => $cookieMap,
        ];
    }

    /**
     * @param  array<string, string>|null  $cookies
     * @return list<array{id: int, denumire: string, scor: int, data: ?string}>
     */
    public function fetchJournal(string $username, ?array $cookies = null): array
    {
        if ($cookies) {
            $this->withCookies($cookies);
        }

        $response = $this->request('GET', '/ajx-module/profil/json-jurnal.php', [
            'query' => ['user' => $username],
            'headers' => [
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => self::BASE_URL.'/profil/'.$username,
            ],
        ]);

        $payload = json_decode((string) $response->getBody(), true);

        if (! is_array($payload)) {
            throw new PbInfoRequestException('Failed to parse journal JSON from PbInfo.');
        }

        $entries = $payload['content'] ?? $payload;

        if (! is_array($entries)) {
            return [];
        }

        $normalized = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $id = (int) ($entry['id'] ?? $entry['id_problema'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $normalized[] = [
                'id' => $id,
                'denumire' => (string) ($entry['denumire'] ?? $entry['titlu'] ?? 'Problema '.$id),
                'scor' => (int) ($entry['scor'] ?? $entry['score'] ?? 0),
                'data' => isset($entry['data']) ? (string) $entry['data'] : null,
            ];
        }

        return $normalized;
    }

    /**
     * @return list<array{name: string, slug: string, url: string, pbinfo_id: ?int}>
     */
    public function fetchCategories(): array
    {
        try {
            $response = $this->request('GET', '/probleme-categorii');
            $html = (string) $response->getBody();
        } catch (PbInfoRequestException) {
            return $this->defaultCategories();
        }

        $crawler = new Crawler($html);
        $categories = [];

        $crawler->filter('a[href*="probleme"], a[href*="categorie"], a[href*="categorii"]')->each(function (Crawler $node) use (&$categories) {
            $href = $node->attr('href');
            $name = trim($node->text(''));

            if (! $href || $name === '' || mb_strlen($name) < 2) {
                return;
            }

            $url = $this->absolutize($href);
            $slug = Str::slug($name);

            if ($slug === '' || isset($categories[$slug])) {
                return;
            }

            $categories[$slug] = [
                'name' => $name,
                'slug' => $slug,
                'url' => $url,
                'pbinfo_id' => $this->extractIdFromUrl($href),
            ];
        });

        if ($categories === []) {
            return $this->defaultCategories();
        }

        return array_values($categories);
    }

    /**
     * @return list<array{name: string, slug: string, url: string, pbinfo_id: ?int}>
     */
    private function defaultCategories(): array
    {
        $categories = [];

        foreach ([
            ['Clasa a IX-a', '/probleme'],
            ['Clasa a X-a', '/probleme'],
            ['Clasa a XI-a', '/probleme'],
            ['Clasa a XII-a', '/probleme'],
            ['Toate problemele', '/probleme'],
        ] as [$name, $path]) {
            $slug = Str::slug($name);
            $categories[] = [
                'name' => $name,
                'slug' => $slug,
                'url' => self::BASE_URL.$path,
                'pbinfo_id' => null,
            ];
        }

        return $categories;
    }

    /**
     * @return list<array{pbinfo_id: int, title: string, slug: ?string, difficulty: ?string, url: string, category_hint: ?string}>
     */
    public function fetchProblemsFromListing(string $listingUrl, int $maxPages = 50): array
    {
        $problems = [];
        $page = 1;
        $seenHashes = [];

        while ($page <= $maxPages) {
            $url = $this->withPage($listingUrl, $page);
            $response = $this->request('GET', $url);
            $html = (string) $response->getBody();
            $hash = hash('sha256', $html);

            if (isset($seenHashes[$hash])) {
                break;
            }
            $seenHashes[$hash] = true;

            $pageProblems = $this->parseProblemListingHtml($html);

            if ($pageProblems === []) {
                break;
            }

            foreach ($pageProblems as $problem) {
                $problems[$problem['pbinfo_id']] = $problem;
            }

            if (! $this->hasNextPage($html, $page)) {
                break;
            }

            $page++;
        }

        return array_values($problems);
    }

    /**
     * @return list<array{pbinfo_id: int, title: string, slug: ?string, difficulty: ?string, url: string, category_hint: ?string}>
     */
    public function parseProblemListingHtml(string $html): array
    {
        $crawler = new Crawler($html);
        $problems = [];

        $crawler->filter('a[href*="/probleme/"]')->each(function (Crawler $node) use (&$problems) {
            $href = (string) $node->attr('href');
            $id = $this->extractIdFromUrl($href);

            if (! $id) {
                return;
            }

            $title = trim(preg_replace('/\s+/', ' ', $node->text('')) ?? '');
            if ($title === '' || isset($problems[$id])) {
                return;
            }

            $difficulty = null;
            $parentText = '';
            try {
                $parentText = $node->closest('tr, li, div, td')?->text('') ?? '';
            } catch (\InvalidArgumentException) {
                $parentText = '';
            }

            if (preg_match('/\b(u[sș]oar[aă]|medie|dificil[aă]|concurs)\b/iu', $parentText, $m)) {
                $difficulty = mb_strtolower($m[1]);
            }

            $problems[$id] = [
                'pbinfo_id' => $id,
                'title' => $title,
                'slug' => Str::slug($title) ?: null,
                'difficulty' => $difficulty,
                'url' => $this->absolutize($href),
                'category_hint' => null,
            ];
        });

        return array_values($problems);
    }

    /**
     * @param  list<array{id: int, denumire: string, scor: int, data: ?string}>  $entries
     * @return array<int, array{id: int, title: string, best_score: int, attempts: int, last_submission_at: ?string}>
     */
    public function aggregateJournal(array $entries): array
    {
        $aggregated = [];

        foreach ($entries as $entry) {
            $id = $entry['id'];
            $score = max(0, min(100, (int) $entry['scor']));

            if (! isset($aggregated[$id])) {
                $aggregated[$id] = [
                    'id' => $id,
                    'title' => $entry['denumire'],
                    'best_score' => $score,
                    'attempts' => 1,
                    'last_submission_at' => $entry['data'],
                ];

                continue;
            }

            $aggregated[$id]['attempts']++;
            $aggregated[$id]['best_score'] = max($aggregated[$id]['best_score'], $score);
            if ($entry['data']) {
                $aggregated[$id]['last_submission_at'] = $entry['data'];
            }
        }

        return $aggregated;
    }

    /**
     * @return array<string, string>
     */
    public function exportCookies(): array
    {
        $map = [];

        foreach ($this->cookies->toArray() as $cookie) {
            $map[$cookie['Name']] = $cookie['Value'];
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function request(string $method, string $uri, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            if ($this->requestDelayMs > 0) {
                usleep($this->requestDelayMs * 1000);
            }

            try {
                $response = $this->http->request($method, $uri, $options);
                $status = $response->getStatusCode();

                if ($status >= 500 || $status === 429) {
                    usleep((int) (pow(2, $attempt) * 200000));
                    continue;
                }

                if ($status >= 400) {
                    throw new PbInfoRequestException("PbInfo returned HTTP {$status} for {$method} {$uri}");
                }

                return $response;
            } catch (GuzzleException $e) {
                $lastException = $e;
                usleep((int) (pow(2, $attempt) * 200000));
            }
        }

        throw new PbInfoRequestException(
            'PbInfo request failed after retries: '.($lastException?->getMessage() ?? 'unknown error'),
            previous: $lastException
        );
    }

    private function looksLoggedOut(string $html): bool
    {
        $hasLoginForm = str_contains($html, 'id="form-login"') || str_contains($html, 'id="parola"');
        $hasUserWidget = str_contains($html, 'pbi-widget-user') || str_contains($html, '/profil/');

        return $hasLoginForm && ! $hasUserWidget;
    }

    private function extractLoggedInUsername(string $html): ?string
    {
        if (preg_match('#/profil/([A-Za-z0-9._-]+)#', $html, $m)) {
            return $m[1];
        }

        return null;
    }

    private function absolutize(string $href): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        if (str_starts_with($href, '/')) {
            return self::BASE_URL.$href;
        }

        return self::BASE_URL.'/'.$href;
    }

    private function extractIdFromUrl(string $href): ?int
    {
        if (preg_match('#/probleme/(\d+)#', $href, $m)) {
            return (int) $m[1];
        }

        if (preg_match('#(?:id|categorie|categorii)[=/](\d+)#', $href, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function withPage(string $url, int $page): string
    {
        if ($page <= 1) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'pagina='.$page;
    }

    private function hasNextPage(string $html, int $currentPage): bool
    {
        return str_contains($html, 'pagina='.($currentPage + 1))
            || str_contains($html, 'page='.($currentPage + 1));
    }
}
