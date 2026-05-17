<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class WbApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $key,
        private readonly int $timeout = 60,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            rtrim(config('wb_api.base_url'), '/'),
            config('wb_api.key'),
            config('wb_api.timeout'),
        );
    }

    /**
     * @return \Generator<int, array<int, array<string, mixed>>>
     *
     * @throws ConnectionException|RequestException
     */
    public function fetchAll(string $endpoint, array $params = []): \Generator
    {
        $page = 1;
        $limit = config('wb_api.limit', 500);

        do {
            $query = array_merge($params, [
                'page' => $page,
                'limit' => $limit,
                'key' => $this->key,
            ]);

            $response = $this->requestWithRetry("{$this->baseUrl}/api/{$endpoint}", $query);

            $body = $response->json();

            if (isset($body['dateFrom']) || isset($body['dateTo']) || isset($body['key'])) {
                throw new \RuntimeException(
                    "API validation error for /api/{$endpoint}: ".json_encode($body, JSON_UNESCAPED_UNICODE)
                );
            }

            $items = $body['data'] ?? [];
            $lastPage = (int) ($body['meta']['last_page'] ?? 1);

            yield $page => $items;

            $page++;
        } while ($page <= $lastPage);
    }

  /**
     * @param  array<string, mixed>  $query
     */
    private function requestWithRetry(string $url, array $query): \Illuminate\Http\Client\Response
    {
        $attempts = 0;
        $maxAttempts = 8;

        while (true) {
            $attempts++;
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($url, $query);

            if ($response->successful()) {
                usleep(150_000);

                return $response;
            }

            if (! in_array($response->status(), [429, 500, 502, 503, 504], true) || $attempts >= $maxAttempts) {
                $response->throw();
            }

            $delay = min(30, 2 ** $attempts);
            sleep($delay);
        }
    }
}
