<?php

namespace App\Http\Controllers\Api;

use App\Enums\Difficulty;
use App\Enums\RouteType;
use App\Enums\TrailFeature;
use App\Http\Controllers\Controller;
use App\Http\Resources\GuideResource;
use App\Http\Resources\RegionResource;
use App\Models\Guide;
use App\Models\Region;
use App\Models\Review;
use App\Models\Trail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MetaController extends Controller
{
    public function regions(): AnonymousResourceCollection
    {
        return RegionResource::collection(
            Region::query()->withCount(['trails' => fn ($q) => $q->published()])->orderBy('name')->get()
        );
    }

    public function guides(): AnonymousResourceCollection
    {
        return GuideResource::collection(
            Guide::query()->with('region')->orderByDesc('is_verified')->orderByDesc('rating')->get()
        );
    }

    public function stats(): JsonResponse
    {
        $published = Trail::query()->published();

        return response()->json(['data' => [
            'trails' => (clone $published)->count(),
            'regions' => (clone $published)->distinct('region_id')->count('region_id'),
            'total_km' => round((float) (clone $published)->sum('distance_km')),
            'reviews' => Review::count(),
            'guides' => Guide::count(),
        ]]);
    }

    public function options(): JsonResponse
    {
        $map = fn (array $cases) => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], $cases);

        return response()->json(['data' => [
            'difficulties' => $map(Difficulty::cases()),
            'route_types' => $map(RouteType::cases()),
            'features' => $map(TrailFeature::cases()),
        ]]);
    }
}
