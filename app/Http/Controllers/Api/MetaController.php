<?php

namespace App\Http\Controllers\Api;

use App\Enums\Difficulty;
use App\Enums\PlaceType;
use App\Enums\RouteType;
use App\Enums\TrailFeature;
use App\Enums\TrailStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DistrictResource;
use App\Http\Resources\EconomicRegionResource;
use App\Http\Resources\GuideResource;
use App\Models\District;
use App\Models\EconomicRegion;
use App\Models\Guide;
use App\Models\Place;
use App\Models\Review;
use App\Models\Trail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MetaController extends Controller
{
    public function regions(): AnonymousResourceCollection
    {
        return EconomicRegionResource::collection(
            EconomicRegion::query()
                ->withCount(['trails' => fn ($q) => $q->where('status', TrailStatus::Published), 'places'])
                ->orderBy('sort')->get()
        );
    }

    public function districts(): AnonymousResourceCollection
    {
        return DistrictResource::collection(District::with('region')->orderBy('slug')->get());
    }

    public function guides(): AnonymousResourceCollection
    {
        return GuideResource::collection(Guide::query()->orderBy('is_sample')->orderByDesc('is_verified')->orderBy('name')->get());
    }

    public function stats(): JsonResponse
    {
        $published = Trail::query()->published();

        return response()->json(['data' => [
            'trails' => (clone $published)->count(),
            'places' => Place::count(),
            'regions' => EconomicRegion::query()->whereHas('places')->orWhereHas('trails')->count(),
            'total_km' => (int) round((float) (clone $published)->where('source', 'osm')->sum('distance_km')),
            'reviews' => Review::where('status', 'published')->count(),
            'guides' => Guide::where('is_sample', false)->count(),
        ]]);
    }

    public function options(): JsonResponse
    {
        $map = fn (array $cases) => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], $cases);

        return response()->json(['data' => [
            'difficulties' => $map(Difficulty::cases()),
            'route_types' => $map(RouteType::cases()),
            'features' => $map(TrailFeature::cases()),
            'place_types' => $map(PlaceType::cases()),
        ]]);
    }
}
