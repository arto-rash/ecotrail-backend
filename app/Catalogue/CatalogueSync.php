<?php

namespace App\Catalogue;

use App\Models\District;
use App\Models\EconomicRegion;
use App\Models\Place;
use App\Models\Trail;
use Illuminate\Support\Facades\DB;

/**
 * Loads database/data/catalogue.json (plus regions and districts) into the database.
 * Idempotent: records are matched by source_key, and catalogue records that disappeared
 * from the JSON are removed. Community submissions are never touched.
 */
class CatalogueSync
{
    public function __construct(
        private string $cataloguePath,
        private string $regionsPath,
        private string $districtsPath,
    ) {}

    public static function fromDefaults(): self
    {
        return new self(
            database_path('data/catalogue.json'),
            database_path('data/economic-regions.php'),
            database_path('data/az-districts.geojson'),
        );
    }

    /** @return array{regions: int, districts: int, places: int, trails: int, removed: int} */
    public function run(): array
    {
        $catalogue = json_decode(file_get_contents($this->cataloguePath), true, flags: JSON_THROW_ON_ERROR);
        $excluded = $catalogue['excluded_regions'] ?? [];

        return DB::transaction(function () use ($catalogue, $excluded) {
            $regionIds = [];
            $sort = 0;
            foreach (require $this->regionsPath as $slug => $r) {
                $regionIds[$slug] = EconomicRegion::updateOrCreate(['slug' => $slug], [
                    'name' => ['az' => $r['az'], 'en' => $r['en']],
                    'sort' => $sort++,
                    'hiking_advised' => ! in_array($slug, $excluded, true),
                ])->id;
            }

            $districts = [];
            $geo = json_decode(file_get_contents($this->districtsPath), true);
            foreach ($geo['features'] as $f) {
                $p = $f['properties'];
                $districts[$p['pcode']] = District::updateOrCreate(['pcode' => $p['pcode']], [
                    'slug' => $p['slug'],
                    'name' => ['az' => $p['name_az'], 'en' => $p['name_en']],
                    'type' => $p['type'],
                    'economic_region_id' => $regionIds[$p['region']],
                ]);
            }

            $placeIds = [];
            foreach ($catalogue['places'] as $p) {
                $district = $districts[$p['district']] ?? null;
                $place = Place::updateOrCreate(['source_key' => $p['key']], [
                    'slug' => $p['slug'],
                    'type' => $p['type'],
                    'name' => $this->text($p['name']),
                    'summary' => $this->text($p['short'] ?? []),
                    'description' => $this->text($p['description'] ?? []),
                    'lat' => $p['lat'],
                    'lng' => $p['lng'],
                    'ele_m' => $p['ele_m'],
                    'ele_source' => $p['ele_source'] ?? null,
                    'district_id' => $district?->id,
                    'economic_region_id' => $district?->economic_region_id,
                    'osm_id' => $p['osm'],
                    'wikidata_id' => $p['wikidata_id'],
                    'image_url' => $p['image']['url'] ?? null,
                    'image_credit' => $p['image'] ? array_diff_key($p['image'], ['url' => 1]) : null,
                    'sources' => $p['sources'],
                    'is_featured' => (bool) ($p['image'] && ($p['description']['az'] ?? $p['description']['en'] ?? null)),
                ]);
                $placeIds[$p['slug']] = $place->id;
            }

            $trailKeys = [];
            foreach ($catalogue['trails'] as $t) {
                $district = $districts[$t['district']] ?? null;
                $trail = Trail::updateOrCreate(['source_key' => $t['key']], [
                    'slug' => $t['slug'],
                    'source' => 'osm',
                    'name' => $this->text($t['name']),
                    'summary' => $this->text($t['short'] ?? []),
                    'description' => $this->text($t['description'] ?? []),
                    'district_id' => $district?->id,
                    'economic_region_id' => $district?->economic_region_id,
                    'difficulty' => $t['difficulty'],
                    'difficulty_basis' => $t['difficulty_basis'],
                    'sac_scale' => $t['sac_scale'],
                    'route_type' => $t['route_type'],
                    'distance_km' => $t['distance_km'],
                    'segments' => $t['segments'],
                    'duration_minutes' => $t['duration_minutes'],
                    'ascent_m' => $t['ascent_m'],
                    'descent_m' => $t['descent_m'],
                    'min_ele_m' => $t['min_ele_m'],
                    'max_ele_m' => $t['max_ele_m'],
                    'stats_estimated' => true,
                    'lat' => $t['lat'],
                    'lng' => $t['lng'],
                    'route' => $t['route'],
                    'elevation_profile' => $t['elevation_profile'],
                    'network' => $t['network'],
                    'osm_relation_id' => $t['osm_relation_id'],
                    'wikidata_id' => $t['wikidata_id'],
                    'image_url' => $t['image']['url'] ?? null,
                    'image_credit' => $t['image'] ? array_diff_key($t['image'], ['url' => 1]) : null,
                    'features' => $t['features'],
                    'sources' => $t['sources'],
                    'status' => 'published',
                    'is_featured' => (bool) $t['image'],
                ]);
                $trail->places()->sync(array_values(array_intersect_key($placeIds, array_flip($t['nearby_places']))));
                $trailKeys[] = $t['key'];
            }

            $removed = Trail::where('source', 'osm')->whereNotIn('source_key', $trailKeys)->delete()
                + Place::whereNotIn('source_key', array_column($catalogue['places'], 'key'))->delete();

            return [
                'regions' => count($regionIds),
                'districts' => count($districts),
                'places' => count($placeIds),
                'trails' => count($trailKeys),
                'removed' => $removed,
            ];
        });
    }

    private function text(array $t): array
    {
        return array_filter(['az' => $t['az'] ?? null, 'en' => $t['en'] ?? null], fn ($v) => $v !== null && $v !== '');
    }
}
