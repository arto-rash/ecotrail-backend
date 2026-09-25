<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\Trail;
use Illuminate\Http\JsonResponse;

/** Everything the map page draws, as two GeoJSON FeatureCollections. */
class MapController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $trails = Trail::query()->published()->whereNotNull('route')->with('region')->get()->map(fn (Trail $t) => [
            'type' => 'Feature',
            'geometry' => $t->route,
            'properties' => [
                'kind' => 'trail', 'slug' => $t->slug, 'name' => $t->localized('name', true),
                'difficulty' => $t->difficulty->value, 'distance_km' => $t->distance_km,
                'duration_minutes' => $t->duration_minutes, 'region' => $t->region?->slug,
                'lat' => $t->lat, 'lng' => $t->lng, 'image' => $t->image_url,
            ],
        ]);

        $places = Place::query()->with('region')->get()->map(fn (Place $p) => [
            'type' => 'Feature',
            'geometry' => ['type' => 'Point', 'coordinates' => [$p->lng, $p->lat]],
            'properties' => [
                'kind' => 'place', 'slug' => $p->slug, 'name' => $p->localized('name', true),
                'type' => $p->type->value, 'ele_m' => $p->ele_m, 'region' => $p->region?->slug,
                'image' => $p->image_url,
            ],
        ]);

        return response()->json([
            'trails' => ['type' => 'FeatureCollection', 'features' => $trails->values()],
            'places' => ['type' => 'FeatureCollection', 'features' => $places->values()],
        ]);
    }
}
