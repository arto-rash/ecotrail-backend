<?php

namespace App\Http\Controllers\Api;

use App\Enums\PlaceType;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceDetailResource;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PlaceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'array'],
            'type.*' => [Rule::enum(PlaceType::class)],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'bbox' => ['nullable', 'regex:/^-?\d+(\.\d+)?(,-?\d+(\.\d+)?){3}$/'],
            'featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['popular', 'name', 'elevation'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = Place::query()->withRating()->with(['district', 'region'])
            ->when($filters['q'] ?? null, fn ($w, $q) => TrailController::searchName($w, $q))
            ->when($filters['type'] ?? null, fn ($w, $types) => $w->whereIn('type', $types))
            ->when($filters['region'] ?? null, fn ($w, $slug) => $w->whereHas('region', fn ($r) => $r->where('slug', $slug)))
            ->when($filters['district'] ?? null, fn ($w, $slug) => $w->whereHas('district', fn ($d) => $d->where('slug', $slug)))
            ->when($request->boolean('featured'), fn ($w) => $w->where('is_featured', true));

        if ($bbox = $filters['bbox'] ?? null) {
            [$minLng, $minLat, $maxLng, $maxLat] = array_map('floatval', explode(',', $bbox));
            $query->whereBetween('lng', [$minLng, $maxLng])->whereBetween('lat', [$minLat, $maxLat]);
        }

        match ($filters['sort'] ?? 'popular') {
            'name' => $query->orderBy('name->'.app()->getLocale()),
            'elevation' => $query->orderByDesc('ele_m'),
            default => $query->orderByDesc('is_featured')->orderByRaw('image_url is null')->orderByDesc('reviews_count')->orderBy('id'),
        };

        return PlaceResource::collection($query->paginate($filters['per_page'] ?? 24)->withQueryString());
    }

    public function show(Place $place): PlaceDetailResource
    {
        $place->load(['district', 'region', 'trails.district', 'trails.region', 'reviews'])
            ->loadAvg('reviews', 'rating')->loadCount('reviews');

        return new PlaceDetailResource($place);
    }

    public function nearby(Place $place): AnonymousResourceCollection
    {
        return PlaceResource::collection(
            Place::query()->withRating()->with(['district', 'region'])->whereKeyNot($place->id)
                ->whereBetween('lat', [$place->lat - 0.5, $place->lat + 0.5])
                ->whereBetween('lng', [$place->lng - 0.6, $place->lng + 0.6])
                ->get()
                ->sortBy(fn (Place $p) => TrailController::distance2($p->lat, $p->lng, $place->lat, $place->lng))
                ->take(4)->values()
        );
    }
}
