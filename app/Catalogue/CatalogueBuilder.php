<?php

namespace App\Catalogue;

use Closure;
use Illuminate\Support\Str;

/**
 * Builds database/data/catalogue.json from open data only:
 * OpenStreetMap (routes, waterfalls, lakes, peaks, settlements), Wikidata (labels,
 * elevation, images, sitelinks), Wikipedia (descriptions), Wikimedia Commons (photos)
 * and SRTM elevation via OpenTopoData. Nothing is written by hand except the lists of
 * which OSM relations and Wikidata items to include.
 */
class CatalogueBuilder
{
    /** Named hiking relations to import as whole trails. */
    private const ROUTE_RELATIONS = [12287488, 12287489, 12287490, 12287491, 20622058, 20622059, 21420390];

    /** Transcaucasian Trail relations; each continuous section becomes its own trail. */
    private const TCT_RELATIONS = [13712375, 13923116, 13933328, 18905100];

    private const TCT_MIN_SECTION_KM = 5.0;

    /** Landmarks not reliably tagged in OSM, by Wikidata id => place type. */
    private const CURATED = [
        'Q2395313' => 'village',  // Xınalıq
        'Q1800656' => 'village',  // Lahıc
        'Q2672033' => 'village',  // İlisu
        'Q4869446' => 'village',  // Laza (Qusar)
        'Q318181' => 'historic',  // Qobustan Dövlət Tarix-Bədii Qoruğu
        'Q593274' => 'park',      // Göygöl Milli Parkı
        'Q21981804' => 'lake',    // Qaranohur (İsmayıllı)
        'Q260554' => 'peak',      // Şahdağ
        'Q725591' => 'peak',      // Bazardüzü
        'Q4466667' => 'peak',     // Tufandağ
        'Q4074121' => 'peak',     // Babadağ
        'Q6921599' => 'peak',     // Kəpəz
    ];

    /** Economic regions where hiking is not advised (post-conflict landmine contamination). */
    public const EXCLUDED_REGIONS = ['karabakh', 'east-zangezur'];

    private const AREA = 'area["ISO3166-1"="AZ"][admin_level=2]->.a;';

    private array $settlements = [];

    public function __construct(
        private OpenData $data,
        private DistrictLocator $districts,
        private array $localPhotos,
    ) {}

    public function build(Closure $log): array
    {
        $log('Settlements…');
        $this->settlements = $this->fetchSettlements();

        $log('Places…');
        $places = $this->buildPlaces($log);

        $log('Trails…');
        $trails = $this->buildTrails($log, $places);

        return [
            'generated_at' => now()->toIso8601String(),
            'sources' => [
                ['label' => 'OpenStreetMap contributors', 'url' => 'https://www.openstreetmap.org/copyright', 'license' => 'ODbL 1.0'],
                ['label' => 'Wikidata', 'url' => 'https://www.wikidata.org', 'license' => 'CC0 1.0'],
                ['label' => 'Wikipedia', 'url' => 'https://www.wikipedia.org', 'license' => 'CC BY-SA 4.0'],
                ['label' => 'SRTM 30 m via OpenTopoData', 'url' => 'https://www.opentopodata.org', 'license' => 'Public domain'],
                ['label' => 'OCHA COD-AB Azerbaijan boundaries', 'url' => 'https://data.humdata.org/dataset/cod-ab-aze', 'license' => 'CC BY-IGO'],
            ],
            'excluded_regions' => self::EXCLUDED_REGIONS,
            'trails' => $trails,
            'places' => $places,
        ];
    }

    // ---------------------------------------------------------------- places

    private function buildPlaces(Closure $log): array
    {
        $candidates = [];
        $queries = [
            'waterfall' => 'nwr["waterway"="waterfall"]["name"](area.a);',
            'lake' => 'nwr["natural"="water"]["water"="lake"]["name"](area.a);',
            'peak' => 'node["natural"="peak"]["name"]["wikidata"](area.a);',
        ];
        foreach ($queries as $type => $q) {
            foreach ($this->data->overpass('[out:json][timeout:180];'.self::AREA.$q.'out center;') as $el) {
                $lat = $el['lat'] ?? $el['center']['lat'] ?? null;
                $lng = $el['lon'] ?? $el['center']['lon'] ?? null;
                if ($lat === null) {
                    continue;
                }
                $candidates[] = ['type' => $type, 'osm' => "{$el['type']}/{$el['id']}", 'tags' => $el['tags'], 'lat' => $lat, 'lng' => $lng];
            }
        }
        $log('  OSM candidates: '.count($candidates));

        // Curated Wikidata landmarks not already present through an OSM wikidata tag.
        $osmWikidata = array_filter(array_map(fn ($c) => $c['tags']['wikidata'] ?? null, $candidates));
        foreach (self::CURATED as $qid => $type) {
            if (! in_array($qid, $osmWikidata, true)) {
                $candidates[] = ['type' => $type, 'osm' => null, 'tags' => ['wikidata' => $qid], 'lat' => null, 'lng' => null];
            } else {
                foreach ($candidates as &$c) {
                    if (($c['tags']['wikidata'] ?? null) === $qid) {
                        $c['type'] = $type;
                    }
                }
                unset($c);
            }
        }

        $entities = $this->data->wikidata(array_values(array_filter(array_map(fn ($c) => $c['tags']['wikidata'] ?? null, $candidates))));
        $log('  Wikidata entities: '.count($entities));

        $places = [];
        foreach ($candidates as $c) {
            $qid = $c['tags']['wikidata'] ?? null;
            $entity = $qid ? ($entities[$qid] ?? null) : null;
            if ($c['lat'] === null) {
                $coord = $entity['claims']['P625'][0]['mainsnak']['datavalue']['value'] ?? null;
                if (! $coord) {
                    continue; // No verifiable location.
                }
                [$c['lat'], $c['lng']] = [$coord['latitude'], $coord['longitude']];
            }

            $district = $this->districts->locate($c['lng'], $c['lat']);
            if (! $district || in_array($district['region'], self::EXCLUDED_REGIONS, true)) {
                continue;
            }

            $name = $this->placeNames($c['tags'], $entity);
            if (! $name) {
                continue;
            }

            $ele = $this->number($c['tags']['ele'] ?? null)
                ?? $this->number($entity['claims']['P2044'][0]['mainsnak']['datavalue']['value']['amount'] ?? null);

            $sources = [];
            if ($c['osm']) {
                $sources[] = ['label' => 'OpenStreetMap', 'url' => "https://www.openstreetmap.org/{$c['osm']}", 'license' => 'ODbL 1.0'];
            }
            if ($qid) {
                $sources[] = ['label' => "Wikidata $qid", 'url' => "https://www.wikidata.org/wiki/$qid", 'license' => 'CC0 1.0'];
            }

            $places[] = [
                'key' => $c['osm'] ? "osm:{$c['osm']}" : "wikidata:$qid",
                'type' => $c['type'],
                'name' => $name,
                'short' => [
                    'az' => $entity['descriptions']['az']['value'] ?? null,
                    'en' => $entity['descriptions']['en']['value'] ?? null,
                ],
                'lat' => round($c['lat'], 6),
                'lng' => round($c['lng'], 6),
                'ele_m' => $ele !== null ? (int) round($ele) : null,
                'district' => $district['pcode'],
                'osm' => $c['osm'],
                'wikidata_id' => $qid,
                'image_file' => $entity['claims']['P18'][0]['mainsnak']['datavalue']['value'] ?? null,
                'sitelinks' => [
                    'az' => $entity['sitelinks']['azwiki']['title'] ?? null,
                    'en' => $entity['sitelinks']['enwiki']['title'] ?? null,
                ],
                'sources' => $sources,
            ];
        }

        $places = $this->dedupe($places);

        // Places without a mapped elevation get the SRTM value, marked as such.
        $missing = array_keys(array_filter($places, fn ($p) => $p['ele_m'] === null));
        $dem = $this->data->elevations(array_map(fn ($i) => [$places[$i]['lng'], $places[$i]['lat']], $missing));
        foreach ($missing as $n => $i) {
            if ($dem[$n] !== null) {
                $places[$i]['ele_m'] = (int) round($dem[$n]);
                $places[$i]['ele_source'] = 'srtm';
            }
        }
        foreach ($places as &$p) {
            $p['ele_source'] ??= $p['ele_m'] !== null ? 'mapped' : null;
        }
        unset($p);

        // Lowland and urban lakes are not hiking destinations unless notable enough for Wikidata.
        $places = array_values(array_filter($places, fn ($p) => $p['type'] !== 'lake' || $p['wikidata_id'] || ($p['ele_m'] ?? 0) >= 400));
        $log('  Places after filtering and de-duplication: '.count($places));

        $log('  Wikipedia summaries…');
        foreach ($places as &$p) {
            $p['description'] = ['az' => null, 'en' => null];
            foreach (['az', 'en'] as $lang) {
                if ($title = $p['sitelinks'][$lang]) {
                    if ($summary = $this->data->wikipediaSummary($lang, $title)) {
                        $p['description'][$lang] = $summary['text'];
                        $p['sources'][] = ['label' => "Wikipedia ($lang): {$summary['title']}", 'url' => $summary['url'], 'license' => 'CC BY-SA 4.0'];
                    }
                }
            }
            unset($p['sitelinks']);
        }
        unset($p);

        $log('  Photos…');
        $images = $this->data->commonsImages(array_values(array_filter(array_column($places, 'image_file'))));
        foreach ($places as &$p) {
            $local = $this->localPhotos[$p['key']] ?? $this->localPhotos["wikidata:{$p['wikidata_id']}"] ?? null;
            $p['image'] = $local ?? ($p['image_file'] ? ($images[$p['image_file']] ?? null) : null);
            unset($p['image_file']);
        }
        unset($p);

        return $this->withSlugs($places);
    }

    private function richness(array $p): int
    {
        return (isset($this->localPhotos[$p['key']]) ? 4 : 0) + ($p['wikidata_id'] ? 2 : 0) + ($p['ele_m'] ? 1 : 0);
    }

    /** Merge entries that share a Wikidata id, the same name within 1 km, or the same type within 300 m. */
    private function dedupe(array $places): array
    {
        $out = [];
        foreach ($places as $p) {
            foreach ($out as &$q) {
                $sameEntity = $p['wikidata_id'] && $p['wikidata_id'] === $q['wikidata_id'];
                $km = Geo::distanceKm([$p['lng'], $p['lat']], [$q['lng'], $q['lat']]);
                $sameName = Str::lower($p['name']['az']) === Str::lower($q['name']['az']) && $km < 1;
                $sameSpot = $p['type'] === $q['type'] && $km < 0.3;
                if ($sameEntity || $sameName || $sameSpot) {
                    // Keep the richer record: one with a local photo, else one with Wikidata.
                    if ($this->richness($p) > $this->richness($q)) {
                        $q = $p;
                    }
                    continue 2;
                }
            }
            unset($q);
            $out[] = $p;
        }

        return $out;
    }

    // ---------------------------------------------------------------- trails

    private function buildTrails(Closure $log, array $places): array
    {
        $ids = [...self::ROUTE_RELATIONS, ...self::TCT_RELATIONS];
        $relations = collect($this->data->overpass('[out:json][timeout:180];relation(id:'.implode(',', $ids).');out geom;'))->keyBy('id');
        $wayTags = collect($this->data->overpass('[out:json][timeout:180];relation(id:'.implode(',', $ids).');way(r);out tags;'))
            ->mapWithKeys(fn ($w) => [$w['id'] => $w['tags'] ?? []]);
        $log('  Relations found: '.$relations->count().' of '.count($ids));

        $entities = $this->data->wikidata($relations->pluck('tags.wikidata')->filter()->values()->all());
        $tctSummary = [
            'en' => $this->data->wikipediaSummary('en', 'Transcaucasian Trail'),
            'az' => $this->data->wikipediaSummary('az', 'Transqafqaz cığırı'),
        ];

        $trails = [];
        foreach ($relations as $id => $rel) {
            [$ways, $sac] = $this->relationWays($rel, $wayTags);
            $segments = Geo::stitch($ways);
            if (! $segments) {
                continue;
            }
            $isTct = in_array($id, self::TCT_RELATIONS, true);
            $osmSource = ['label' => "OpenStreetMap relation $id", 'url' => "https://www.openstreetmap.org/relation/$id", 'license' => 'ODbL 1.0'];

            if ($isTct) {
                foreach ($segments as $i => $segment) {
                    if (Geo::lineLengthKm($segment) < self::TCT_MIN_SECTION_KM) {
                        continue;
                    }
                    $from = $this->endpointName($segment[0]);
                    $to = $this->endpointName(end($segment));
                    if (! $from || ! $to) {
                        continue;
                    }
                    $sources = [$osmSource];
                    $description = ['az' => null, 'en' => null];
                    foreach (['az', 'en'] as $lang) {
                        if ($tctSummary[$lang]) {
                            $description[$lang] = $tctSummary[$lang]['text'];
                            $sources[] = ['label' => "Wikipedia ($lang): {$tctSummary[$lang]['title']}", 'url' => $tctSummary[$lang]['url'], 'license' => 'CC BY-SA 4.0'];
                        }
                    }
                    $trail = $this->trailFromSegments($log, "osm:relation/$id#$i", [$segment], $sac, $places, [
                        'name' => [
                            'az' => "Transqafqaz cığırı: {$from['az']} – {$to['az']}",
                            'en' => "Transcaucasian Trail: {$from['en']} – {$to['en']}",
                        ],
                        'description' => $description,
                        'short' => ['az' => null, 'en' => null],
                        'network' => 'tct',
                        'osm_relation_id' => $id,
                        'wikidata_id' => null,
                        'sources' => $sources,
                    ]);
                    if ($trail) {
                        $trails[] = $trail;
                    }
                }

                continue;
            }

            $tags = $rel['tags'] ?? [];
            $qid = $tags['wikidata'] ?? null;
            $entity = $qid ? ($entities[$qid] ?? null) : null;
            $sources = [$osmSource];
            if ($qid) {
                $sources[] = ['label' => "Wikidata $qid", 'url' => "https://www.wikidata.org/wiki/$qid", 'license' => 'CC0 1.0'];
            }
            if (! empty($tags['website'])) {
                $sources[] = ['label' => 'Wikivoyage', 'url' => $tags['website'], 'license' => 'CC BY-SA 4.0'];
            }
            $trail = $this->trailFromSegments($log, "osm:relation/$id", $segments, $sac, $places, [
                'name' => $this->names($tags, $entity),
                'description' => [
                    'az' => $tags['description:az'] ?? $tags['description'] ?? null,
                    'en' => $tags['description:en'] ?? null,
                ],
                'short' => [
                    'az' => $entity['descriptions']['az']['value'] ?? null,
                    'en' => $entity['descriptions']['en']['value'] ?? null,
                ],
                'network' => $tags['network'] ?? null,
                'osm_relation_id' => $id,
                'wikidata_id' => $qid,
                'sources' => $sources,
            ]);
            if ($trail) {
                $trails[] = $trail;
            }
        }

        return $this->withSlugs($trails);
    }

    /** @return array{0: list<list<array>>, 1: array<string, string>} ways, and sac_scale keyed by a way's first vertex */
    private function relationWays(array $rel, $wayTags): array
    {
        $ways = [];
        $sac = [];
        foreach ($rel['members'] ?? [] as $m) {
            if ($m['type'] !== 'way' || empty($m['geometry'])) {
                continue;
            }
            $way = array_map(fn ($p) => [$p['lon'], $p['lat']], $m['geometry']);
            $ways[] = $way;
            if ($s = $wayTags[$m['ref']]['sac_scale'] ?? null) {
                $sac[$this->vertexKey($way[0])] = $s;
                $sac[$this->vertexKey(end($way))] = $s;
            }
        }

        return [$ways, $sac];
    }

    private function vertexKey(array $p): string
    {
        return round($p[0], 7).','.round($p[1], 7);
    }

    /** sac_scale values of the member ways that ended up in these segments. */
    private function sacFor(array $segments, array $sacByVertex): array
    {
        $found = [];
        foreach ($segments as $seg) {
            foreach ($seg as $pt) {
                if (isset($sacByVertex[$k = $this->vertexKey($pt)])) {
                    $found[] = $sacByVertex[$k];
                }
            }
        }

        return $found;
    }

    private function trailFromSegments(Closure $log, string $key, array $segments, array $sac, array $places, array $meta): ?array
    {
        $sac = $this->sacFor($segments, $sac);
        $main = $segments[0];
        $start = $main[0];
        $district = $this->districts->locate(...$start);
        if (! $district || in_array($district['region'], self::EXCLUDED_REGIONS, true)) {
            return null;
        }
        if (! ($meta['name']['az'] ?? null) && ! ($meta['name']['en'] ?? null)) {
            return null;
        }

        $totalKm = array_sum(array_map([Geo::class, 'lineLengthKm'], $segments));
        $mainKm = Geo::lineLengthKm($main);

        // Elevation profile along the main line, one sample roughly every 250 m.
        $samples = Geo::resample($main, (int) max(20, min(400, ceil($mainKm / 0.25))));
        $log("  $key: {$meta['name']['en']} — ".round($totalKm, 1).' km, '.count($samples).' elevation samples');
        $elevations = $this->data->elevations(array_map(fn ($s) => [$s[0], $s[1]], $samples));
        $profile = [];
        foreach ($samples as $i => $s) {
            if ($elevations[$i] !== null) {
                $profile[] = [round($s[2], 3), (int) round($elevations[$i])];
            }
        }
        [$ascent, $descent] = $this->climb(array_column($profile, 1));
        $eles = array_column($profile, 1);

        $minutes = Geo::toblerMinutes($profile);
        // Extra segments (branches, alternates) walked at the flat Tobler speed.
        $minutes += (int) (round(($totalKm - $mainKm) / 5.036 * 60 / 5) * 5);

        [$difficulty, $difficultyBasis] = $this->difficulty($sac, $totalKm, $ascent);

        $end = end($main);
        $isLoop = Geo::distanceKm($start, $end) < 0.3;

        $route = array_map(fn ($seg) => array_map(fn ($p) => [round($p[0], 5), round($p[1], 5)], Geo::simplify($seg, 0.00008)), $segments);

        return [
            'key' => $key,
            ...$meta,
            'difficulty' => $difficulty,
            'difficulty_basis' => $difficultyBasis,
            'sac_scale' => $sac ? collect($sac)->countBy()->sortDesc()->keys()->first() : null,
            'route_type' => $isLoop ? 'loop' : 'point_to_point',
            'distance_km' => round($totalKm, 2),
            'segments' => count($segments),
            'duration_minutes' => max(5, $minutes),
            'ascent_m' => $ascent,
            'descent_m' => $descent,
            'min_ele_m' => $eles ? min($eles) : null,
            'max_ele_m' => $eles ? max($eles) : null,
            'lat' => round($start[1], 6),
            'lng' => round($start[0], 6),
            'district' => $district['pcode'],
            'route' => ['type' => 'MultiLineString', 'coordinates' => $route],
            'elevation_profile' => $this->thinProfile($profile, 150),
            'features' => $this->featuresNear($segments, $places),
            'nearby_places' => $nearby = $this->placesNear($segments, $places, 1.0),
            'image' => $this->coverFromPlaces($nearby, $places),
        ];
    }

    /** The photo of the first place with one along the route, captioned with that place's name. */
    private function coverFromPlaces(array $slugs, array $places): ?array
    {
        foreach ($slugs as $slug) {
            $p = collect($places)->firstWhere('slug', $slug);
            if ($p['image'] ?? null) {
                return [...$p['image'], 'caption' => $p['name']];
            }
        }

        return null;
    }

    /** Sum of climbs and descents, ignoring changes under 5 m to damp DEM noise. */
    private function climb(array $eles): array
    {
        $up = $down = 0;
        $ref = $eles[0] ?? 0;
        foreach ($eles as $e) {
            $d = $e - $ref;
            if (abs($d) >= 5) {
                $d > 0 ? $up += $d : $down -= $d;
                $ref = $e;
            }
        }

        return [(int) $up, (int) $down];
    }

    /**
     * SAC scale when mapped; otherwise "effort km" (distance + ascent/100 m, the Swiss
     * Leistungskilometer rule of thumb): < 10 easy, < 20 moderate, < 35 hard, else expert.
     */
    private function difficulty(array $sac, float $km, int $ascent): array
    {
        if ($sac) {
            $worst = collect($sac)->map(fn ($s) => [
                'hiking' => 1, 'mountain_hiking' => 2, 'demanding_mountain_hiking' => 3,
                'alpine_hiking' => 4, 'demanding_alpine_hiking' => 4, 'difficult_alpine_hiking' => 4,
            ][$s] ?? 1)->max();

            return [[1 => 'easy', 2 => 'moderate', 3 => 'hard', 4 => 'expert'][$worst], 'sac_scale'];
        }
        $effort = $km + $ascent / 100;

        return [match (true) {
            $effort < 10 => 'easy',
            $effort < 20 => 'moderate',
            $effort < 35 => 'hard',
            default => 'expert',
        }, 'effort'];
    }

    private function featuresNear(array $segments, array $places): array
    {
        $map = ['waterfall' => 'waterfall', 'lake' => 'lake', 'peak' => 'summit', 'village' => 'village', 'historic' => 'historic'];
        $features = [];
        foreach ($this->placesNear($segments, $places, 0.3) as $slug) {
            $type = collect($places)->firstWhere('slug', $slug)['type'];
            if (isset($map[$type])) {
                $features[$map[$type]] = true;
            }
        }

        return array_keys($features);
    }

    /** Slugs of places within $km of any route vertex. */
    private function placesNear(array $segments, array $places, float $km): array
    {
        $near = [];
        foreach ($places as $p) {
            foreach ($segments as $seg) {
                foreach ($seg as $i => $pt) {
                    if ($i % 3 && $i !== count($seg) - 1) {
                        continue;
                    }
                    if (Geo::distanceKm($pt, [$p['lng'], $p['lat']]) <= $km) {
                        $near[] = $p['slug'];
                        continue 3;
                    }
                }
            }
        }

        return $near;
    }

    private function thinProfile(array $profile, int $max): array
    {
        $n = count($profile);
        if ($n <= $max) {
            return $profile;
        }
        $out = [];
        for ($i = 0; $i < $max; $i++) {
            $out[] = $profile[(int) round($i * ($n - 1) / ($max - 1))];
        }

        return $out;
    }

    // ---------------------------------------------------------------- helpers

    private function fetchSettlements(): array
    {
        $out = [];
        foreach ($this->data->overpass('[out:json][timeout:180];'.self::AREA.'node["place"~"^(city|town|village)$"]["name"](area.a);out;') as $el) {
            $out[] = [
                'lng' => $el['lon'], 'lat' => $el['lat'],
                'az' => $el['tags']['name:az'] ?? $el['tags']['name'],
                'en' => $el['tags']['name:en'] ?? $el['tags']['name:az'] ?? $el['tags']['name'],
            ];
        }

        return $out;
    }

    /** The nearest village within 6 km, else within 20 km, else the district's name. */
    private function endpointName(array $point): ?array
    {
        if ($s = $this->nearestSettlement($point) ?? $this->nearestSettlement($point, 20)) {
            return $s;
        }
        $d = $this->districts->locate(...$point);

        return $d ? ['az' => $d['name_az'], 'en' => $d['name_en']] : null;
    }

    private function nearestSettlement(array $point, float $maxKm = 6): ?array
    {
        $best = null;
        foreach ($this->settlements as $s) {
            $d = Geo::distanceKm($point, [$s['lng'], $s['lat']]);
            if ($d <= $maxKm && ($best === null || $d < $best[0])) {
                $best = [$d, $s];
            }
        }

        return $best[1] ?? null;
    }

    /** Names that are just a category word or otherwise not a real name. */
    private const GENERIC_NAMES = ['şəlalə', 'göl', 'gol', 'park', 'adsız', 'waterfall', 'lake', 'dağ', 'zirvə'];

    /**
     * Place names: Azerbaijani from OSM name:az, the Wikidata az label or OSM name; English from
     * OSM name:en, else the English Wikipedia title, else the Azerbaijani name (proper nouns are
     * better left untranslated than guessed). Returns null for generic or non-Latin-only names.
     */
    private function placeNames(array $tags, ?array $entity): ?array
    {
        // Drops OSM "Name \ District" suffixes and Wikidata "(disambiguation)" parentheses.
        $clean = fn (?string $n) => $n === null ? null : (trim(preg_replace(['/\\s*\\\\.*$/u', '/\\s*\\([^)]*\\)\\s*$/u'], '', $n)) ?: null);
        $az = $clean($tags['name:az'] ?? $entity['labels']['az']['value'] ?? $tags['name'] ?? null);
        $en = $clean($tags['name:en'] ?? null) ?? $this->withoutPlaceQualifier($clean($entity['sitelinks']['enwiki']['title'] ?? null)) ?? $az;
        if (! $az || in_array(mb_strtolower($az), self::GENERIC_NAMES, true) || ! preg_match('/\\p{Latin}/u', $az)) {
            return null;
        }

        return ['az' => $az, 'en' => $en];
    }

    /** Drops Wikipedia's ", District" or ", Azerbaijan" disambiguation from an article title. */
    private function withoutPlaceQualifier(?string $title): ?string
    {
        if ($title === null || ! str_contains($title, ', ')) {
            return $title;
        }
        [$name, $qualifier] = explode(', ', $title, 2);
        $districts = $this->districts->all();
        $places = [...array_column($districts, 'name_en'), ...array_column($districts, 'name_az'), 'Azerbaijan'];

        return in_array($qualifier, $places, true) ? $name : $title;
    }

    /** az/en names from OSM tags first, then Wikidata labels, then the plain name. */
    private function names(array $tags, ?array $entity): array
    {
        $plain = $tags['name'] ?? null;
        $az = $tags['name:az'] ?? $entity['labels']['az']['value'] ?? $plain;
        $en = $tags['name:en'] ?? $entity['labels']['en']['value'] ?? $az;

        return ['az' => $az, 'en' => $en];
    }

    private function number(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        $v = (float) str_replace(',', '.', ltrim((string) $value, '+'));

        return $v > 0 ? $v : null;
    }

    private function withSlugs(array $items): array
    {
        $used = [];
        foreach ($items as &$item) {
            $base = Str::slug(Str::ascii($item['name']['en'] ?? $item['name']['az'])) ?: 'item';
            $slug = $base;
            if (isset($used[$slug])) {
                $slug = $base.'-'.Str::slug($this->districtSlug($item['district']));
            }
            $n = 2;
            while (isset($used[$slug])) {
                $slug = $base.'-'.$n++;
            }
            $used[$slug] = true;
            $item['slug'] = $slug;
        }

        return $items;
    }

    private function districtSlug(string $pcode): string
    {
        return collect($this->districts->all())->firstWhere('pcode', $pcode)['slug'] ?? $pcode;
    }
}
