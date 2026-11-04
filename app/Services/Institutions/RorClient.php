<?php

namespace App\Services\Institutions;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Research Organization Registry (https://ror.org) API v2, called server-side only:
 * the browser never talks to third parties (CSP connect-src 'self').
 */
final class RorClient
{
    public const ID_PATTERN = '/^0[a-z0-9]{6}[0-9]{2}$/';

    /**
     * @return list<array{ror_id: string, name: string, country_code: string|null, city: string|null}>
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 3) {
            return [];
        }

        $key = 'ror:search:'.hash('sha256', mb_strtolower($query));

        return Cache::remember($key, now()->addMinutes((int) config('scim.ror.cache_minutes')), function () use ($query) {
            try {
                $response = $this->http()->get('/organizations', ['query' => $query]);
            } catch (Throwable $e) {
                Log::warning('ROR search failed', ['error' => $e->getMessage()]);

                return [];
            }

            if (! $response->successful()) {
                return [];
            }

            return array_values(array_filter(array_map(
                fn ($item) => $this->map($item),
                array_slice((array) $response->json('items', []), 0, 10)
            )));
        });
    }

    /**
     * @return array{ror_id: string, name: string, country_code: string|null, city: string|null}|null
     */
    public function find(string $rorId): ?array
    {
        if (! preg_match(self::ID_PATTERN, $rorId)) {
            return null;
        }

        return Cache::remember('ror:org:'.$rorId, now()->addMinutes((int) config('scim.ror.cache_minutes')), function () use ($rorId) {
            try {
                $response = $this->http()->get('/organizations/'.$rorId);
            } catch (Throwable $e) {
                Log::warning('ROR lookup failed', ['error' => $e->getMessage()]);

                return null;
            }

            return $response->successful() ? $this->map((array) $response->json()) : null;
        });
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{ror_id: string, name: string, country_code: string|null, city: string|null}|null
     */
    private function map(array $item): ?array
    {
        $id = basename((string) ($item['id'] ?? ''));

        if (! preg_match(self::ID_PATTERN, $id)) {
            return null;
        }

        $name = null;
        foreach ((array) ($item['names'] ?? []) as $n) {
            if (in_array('ror_display', (array) ($n['types'] ?? []), true)) {
                $name = (string) $n['value'];
                break;
            }
        }
        $name ??= (string) ($item['names'][0]['value'] ?? '');

        if ($name === '') {
            return null;
        }

        $geo = $item['locations'][0]['geonames_details'] ?? [];

        return [
            'ror_id' => $id,
            'name' => mb_substr($name, 0, 255),
            'country_code' => isset($geo['country_code']) ? strtoupper((string) $geo['country_code']) : null,
            'city' => isset($geo['name']) ? (string) $geo['name'] : null,
        ];
    }

    private function http(): PendingRequest
    {
        $request = Http::baseUrl((string) config('scim.ror.base_url'))
            ->acceptJson()
            ->timeout((int) config('scim.ror.timeout'))
            ->withUserAgent('SCIMSCORE (+https://scimscore.com)');

        if ($clientId = config('scim.ror.client_id')) {
            $request = $request->withHeaders(['Client-Id' => $clientId]);
        }

        return $request;
    }
}
