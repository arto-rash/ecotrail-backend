<?php

namespace App\Catalogue;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Thin clients for the open data services the catalogue is built from. */
class OpenData
{
    private const USER_AGENT = 'EcoTrail-catalogue/1.0 (+https://github.com/arto-rash/ecotrail-backend)';

    private const OVERPASS = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.private.coffee/api/interpreter',
    ];

    private function http(): PendingRequest
    {
        return Http::withUserAgent(self::USER_AGENT)->acceptJson()->timeout(180)
            ->retry(3, fn (int $attempt) => $attempt * 3000, throw: false);
    }

    /** Run an Overpass QL query against the first mirror that answers. */
    public function overpass(string $query): array
    {
        foreach (self::OVERPASS as $url) {
            $res = $this->http()->asForm()->post($url, ['data' => $query]);
            if ($res->successful() && is_array($res->json('elements'))) {
                return $res->json('elements');
            }
        }
        throw new RuntimeException('All Overpass mirrors failed for query: '.substr($query, 0, 120));
    }

    /**
     * Wikidata entities keyed by Q-id, with az/en labels, descriptions, claims and sitelinks.
     *
     * @param  list<string>  $ids
     */
    public function wikidata(array $ids): array
    {
        $out = [];
        foreach (array_chunk(array_values(array_unique($ids)), 50) as $chunk) {
            $res = $this->http()->get('https://www.wikidata.org/w/api.php', [
                'action' => 'wbgetentities', 'format' => 'json', 'ids' => implode('|', $chunk),
                'props' => 'labels|descriptions|claims|sitelinks', 'languages' => 'az|en',
                'sitefilter' => 'azwiki|enwiki',
            ]);
            foreach ($res->json('entities') ?? [] as $id => $entity) {
                if (! isset($entity['missing'])) {
                    $out[$id] = $entity;
                }
            }
            usleep(500_000);
        }

        return $out;
    }

    /** Intro summary of a Wikipedia article, or null when there is none. */
    public function wikipediaSummary(string $lang, string $title): ?array
    {
        $res = $this->http()->get("https://{$lang}.wikipedia.org/api/rest_v1/page/summary/".rawurlencode(str_replace(' ', '_', $title)));
        usleep(200_000);
        if (! $res->successful() || $res->json('type') !== 'standard' || ! $res->json('extract')) {
            return null;
        }

        return [
            'text' => trim($res->json('extract')),
            'url' => $res->json('content_urls.desktop.page'),
            'title' => $res->json('title'),
        ];
    }

    /**
     * Thumbnail URL plus attribution for Commons files.
     *
     * @param  list<string>  $files  File names without the "File:" prefix
     */
    public function commonsImages(array $files, int $width = 1280): array
    {
        $out = [];
        foreach (array_chunk(array_values(array_unique($files)), 40) as $chunk) {
            $res = $this->http()->get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query', 'format' => 'json', 'prop' => 'imageinfo',
                'iiprop' => 'url|extmetadata', 'iiurlwidth' => $width,
                'titles' => implode('|', array_map(fn ($f) => "File:$f", $chunk)),
            ]);
            $normalized = collect($res->json('query.normalized') ?? [])->pluck('from', 'to');
            foreach ($res->json('query.pages') ?? [] as $page) {
                $info = $page['imageinfo'][0] ?? null;
                if (! $info) {
                    continue;
                }
                $meta = $info['extmetadata'] ?? [];
                $clean = fn ($k) => isset($meta[$k]['value']) ? trim(html_entity_decode(strip_tags($meta[$k]['value']))) : null;
                $title = $normalized[$page['title']] ?? $page['title'];
                $out[substr($title, 5)] = [
                    // Drop Wikimedia's utm_* tracking parameters.
                    'url' => strtok($info['thumburl'] ?? $info['url'], '?'),
                    'author' => $clean('Artist') ?: 'Unknown',
                    'license' => $clean('LicenseShortName') ?: 'See source',
                    'source' => $info['descriptionurl'],
                ];
            }
            usleep(500_000);
        }

        return $out;
    }

    /**
     * SRTM 30 m elevations (metres) for [lng, lat] points, from OpenTopoData.
     *
     * @return list<float|null>
     */
    public function elevations(array $points): array
    {
        $out = [];
        foreach (array_chunk($points, 100) as $chunk) {
            $locations = implode('|', array_map(fn ($p) => round($p[1], 6).','.round($p[0], 6), $chunk));
            $res = $this->http()->get('https://api.opentopodata.org/v1/srtm30m', ['locations' => $locations]);
            if (! $res->successful() || $res->json('status') !== 'OK') {
                throw new RuntimeException('OpenTopoData failed: '.$res->status());
            }
            foreach ($res->json('results') as $r) {
                $out[] = $r['elevation'] === null ? null : (float) $r['elevation'];
            }
            sleep(1); // Public API allows one request per second.
        }

        return $out;
    }
}
