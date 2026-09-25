<?php

namespace Tests\Feature;

use App\Catalogue\CatalogueSync;
use App\Catalogue\Geo;
use App\Models\District;
use App\Models\EconomicRegion;
use App\Models\Place;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_loads_everything_and_is_idempotent(): void
    {
        $catalogue = json_decode(file_get_contents(database_path('data/catalogue.json')), true);

        $first = CatalogueSync::fromDefaults()->run();
        $second = CatalogueSync::fromDefaults()->run();

        $this->assertSame($first, $second);
        $this->assertSame(14, EconomicRegion::count());
        $this->assertSame(74, District::count());
        $this->assertSame(count($catalogue['places']), Place::count());
        $this->assertSame(count($catalogue['trails']), Trail::count());
    }

    public function test_every_record_is_sourced_and_outside_excluded_regions(): void
    {
        CatalogueSync::fromDefaults()->run();

        foreach ([Trail::all(), Place::all()] as $records) {
            foreach ($records as $r) {
                $this->assertNotEmpty($r->sources, "{$r->slug} has no sources");
                $this->assertNotNull($r->district_id, "{$r->slug} has no district");
            }
        }
        $excluded = EconomicRegion::where('hiking_advised', false)->pluck('slug')->sort()->values()->all();
        $this->assertSame(['east-zangezur', 'karabakh'], $excluded);
        $this->assertSame(0, Place::whereHas('region', fn ($q) => $q->where('hiking_advised', false))->count());
        $this->assertSame(0, Trail::whereHas('region', fn ($q) => $q->where('hiking_advised', false))->count());
    }

    public function test_sync_keeps_community_trails(): void
    {
        CatalogueSync::fromDefaults()->run();
        Trail::create([
            'slug' => 'community-one', 'source' => 'community', 'name' => ['az' => 'Test'],
            'difficulty' => 'easy', 'route_type' => 'loop', 'distance_km' => 2,
            'duration_minutes' => 30, 'lat' => 40.8, 'lng' => 48.3, 'status' => 'pending',
        ]);

        CatalogueSync::fromDefaults()->run();

        $this->assertDatabaseHas('trails', ['slug' => 'community-one']);
    }

    public function test_geo_helpers(): void
    {
        $this->assertEqualsWithDelta(111.2, Geo::distanceKm([48, 40], [48, 41]), 0.1);

        $segments = Geo::stitch([[[0, 0], [0, 0.01]], [[0, 0.02], [0, 0.01]], [[0, -0.01], [0, 0]], [[1, 1], [1, 1.01]]]);
        $this->assertCount(2, $segments);
        $this->assertSame([[0, -0.01], [0, 0], [0, 0.01], [0, 0.02]], $segments[0]);

        // 3 km with a 200 m climb is roughly 45 minutes by Tobler's function.
        $this->assertSame(45, Geo::toblerMinutes([[0, 500], [1, 600], [2, 700], [3, 650]]));
    }
}
