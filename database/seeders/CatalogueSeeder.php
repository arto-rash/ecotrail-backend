<?php

namespace Database\Seeders;

use App\Enums\TrailStatus;
use App\Models\Guide;
use App\Models\Region;
use App\Models\Review;
use App\Models\Trail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CatalogueSeeder extends Seeder
{
    private const REGIONS = [
        'ismayilli' => ['Ismayilli', 'Forested foothills, craft villages and hidden lakes.'],
        'qusar' => ['Qusar', 'Gateway to Shahdag National Park and the high Caucasus.'],
        'quba' => ['Quba', 'Apple orchards, gorges and ancient mountain villages.'],
        'gabala' => ['Gabala', 'Waterfalls, lakes and ridges below the main Caucasus chain.'],
        'goygol' => ['Göygöl', 'Alpine lakes and old-growth forest around Mount Kəpəz.'],
        'gakh' => ['Gakh', 'River valleys, medieval towers and quiet forest trails.'],
        'gobustan' => ['Gobustan', 'Semi-desert, mud volcanoes and prehistoric rock art.'],
        'lerik' => ['Lerik', 'The lush, rolling Talysh mountains of the far south.'],
    ];

    // Sample reviews for demo purposes only.
    private const REVIEWS = [
        ['Leyla M.', 5, 'Absolutely worth the early start. We had the place almost to ourselves until about ten.'],
        ['Harry P.', 4, 'Beautiful route, well trodden. A couple of muddy sections after rain — proper boots recommended.'],
        ['Aysel Q.', 5, 'Went with my parents and they managed fine. The views at the end are unreal.'],
        ['Liana K.', 4, 'Lovely hike. Signage is sparse, so download the map before you lose signal.'],
        ['Rashad H.', 5, 'One of my favourite day trips from Baku. Bring cash for tea in the village afterwards.'],
        ['Agne S.', 4, 'Stunning scenery. Took us longer than the estimate because we kept stopping for photos!'],
        ['Tural A.', 3, 'Nice walk but quite crowded on the weekend. Go on a weekday if you can.'],
        ['Mariam D.', 5, 'The locals were so welcoming. The trail itself is easy to follow in dry weather.'],
        ['Jonas B.', 4, 'Great workout. Weather turned fast in the afternoon, so pack a jacket even in summer.'],
        ['Nigar E.', 5, 'Magical in autumn — the colours in the forest were incredible.'],
        ['Samir V.', 4, 'Solid trail, good for a half day. Parking at the start is limited.'],
        ['Chloe R.', 5, 'Highlight of our trip to Azerbaijan. Please carry your rubbish out, it keeps this place special.'],
    ];

    private const GUIDES = [
        ['elvin-mountain-guide', 'Elvin Mammadov', 'guide', 'qusar', 'High-Caucasus trekking & Shahdag ascents', 'Certified mountain guide born in Qusar, leading multi-day treks around Shahdag and Bazarduzu for over a decade.', ['Azerbaijani', 'English', 'Russian'], ['Multi-day treks', 'Summits', 'Photography'], 4.9, 214, 120, true],
        ['caucasus-trails-co', 'Caucasus Trails Co.', 'agency', 'quba', 'Small-group hikes from Baku', 'Weekend departures to Quba, Qusar and Khinalug with transport, local guides and village homestays included.', ['Azerbaijani', 'English', 'Russian', 'Turkish'], ['Day trips', 'Homestays', 'Transport'], 4.7, 860, 85, true],
        ['nigar-forest-walks', 'Nigar Aliyeva', 'guide', 'ismayilli', 'Forest walks, crafts & food in Ismayilli', 'Former biology teacher who pairs gentle forest hikes with visits to Lahij coppersmiths and family kitchens.', ['Azerbaijani', 'English'], ['Families', 'Culture', 'Birdwatching'], 4.8, 132, 60, true],
        ['talysh-hikes', 'Talysh Hikes', 'agency', 'lerik', 'Hiking and longevity tours in the south', 'Local collective from Lerik running village-to-village walks through the Talysh mountains.', ['Azerbaijani', 'Russian', 'Talysh'], ['Village walks', 'Culture'], 4.6, 97, 50, false],
        ['kamran-alpine', 'Kamran Huseynov', 'guide', 'goygol', 'Göygöl & Kəpəz specialist', 'Park-licensed guide for routes around Göygöl National Park, including the Maralgöl approach.', ['Azerbaijani', 'English', 'German'], ['Alpine lakes', 'Permits', 'Camping'], 4.9, 178, 110, true],
        ['gabala-outdoor', 'Gabala Outdoor', 'agency', 'gabala', 'Waterfalls, ridges and ski-season snowshoeing', 'Year-round guided hikes around Gabala, from easy waterfall walks to full days on Tufandağ.', ['Azerbaijani', 'English', 'Russian'], ['Waterfalls', 'Snowshoeing', 'Families'], 4.5, 402, 70, false],
    ];

    public function run(): void
    {
        $regions = collect(self::REGIONS)->mapWithKeys(fn ($r, $slug) => [
            $slug => Region::updateOrCreate(['slug' => $slug], ['name' => $r[0], 'description' => $r[1]]),
        ]);

        foreach (require database_path('data/trails.php') as $i => $data) {
            $trail = Trail::updateOrCreate(['slug' => $data['slug']], [
                ...collect($data)->except('region')->all(),
                'region_id' => $regions[$data['region']]->id,
                'status' => TrailStatus::Published,
            ]);

            $trail->reviews()->delete();
            $count = 2 + ($i % 4);
            for ($n = 0; $n < $count; $n++) {
                [$author, $rating, $body] = self::REVIEWS[($i * 5 + $n * 7) % count(self::REVIEWS)];
                Review::create([
                    'trail_id' => $trail->id,
                    'author_name' => $author,
                    'rating' => $rating,
                    'body' => $body,
                    'hiked_on' => Carbon::create(2026, 4, 1)->subDays(($i * 11 + $n * 23) % 300),
                ]);
            }
        }

        foreach (self::GUIDES as [$slug, $name, $kind, $region, $headline, $bio, $languages, $specialties, $rating, $trips, $price, $verified]) {
            Guide::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'kind' => $kind, 'region_id' => $regions[$region]->id,
                'headline' => $headline, 'bio' => $bio, 'languages' => $languages,
                'specialties' => $specialties, 'rating' => $rating, 'trips_count' => $trips,
                'price_from_azn' => $price, 'is_verified' => $verified,
            ]);
        }
    }
}
