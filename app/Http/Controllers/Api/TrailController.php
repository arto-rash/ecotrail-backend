<?php

namespace App\Http\Controllers\Api;

use App\Enums\Difficulty;
use App\Enums\TrailFeature;
use App\Enums\TrailStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrailRequest;
use App\Http\Resources\TrailDetailResource;
use App\Http\Resources\TrailResource;
use App\Models\Trail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TrailController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['nullable', 'array'],
            'difficulty.*' => [Rule::enum(Difficulty::class)],
            'feature' => ['nullable', 'array'],
            'feature.*' => [Rule::enum(TrailFeature::class)],
            'max_duration' => ['nullable', 'integer', 'min:1'],
            'max_distance' => ['nullable', 'numeric', 'min:0'],
            'featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['popular', 'rating', 'distance', 'duration', 'name'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Trail::query()->published()->withRating()->with('region');

        if ($q = $filters['q'] ?? null) {
            $like = '%'.mb_strtolower($q).'%';
            $query->where(fn ($w) => $w
                ->whereRaw('lower(name) like ?', [$like])
                ->orWhereRaw('lower(village) like ?', [$like])
                ->orWhereRaw('lower(summary) like ?', [$like])
                ->orWhereHas('region', fn ($r) => $r->whereRaw('lower(name) like ?', [$like])));
        }

        $query
            ->when($filters['region'] ?? null, fn ($w, $slug) => $w->whereHas('region', fn ($r) => $r->where('slug', $slug)))
            ->when($filters['difficulty'] ?? null, fn ($w, $d) => $w->whereIn('difficulty', $d))
            ->when($filters['max_duration'] ?? null, fn ($w, $m) => $w->where('duration_minutes', '<=', $m))
            ->when($filters['max_distance'] ?? null, fn ($w, $km) => $w->where('distance_km', '<=', $km))
            ->when($request->boolean('featured'), fn ($w) => $w->where('is_featured', true));

        foreach ($filters['feature'] ?? [] as $feature) {
            $query->whereJsonContains('features', $feature);
        }

        match ($filters['sort'] ?? 'popular') {
            'rating' => $query->orderByDesc('reviews_avg_rating'),
            'distance' => $query->orderBy('distance_km'),
            'duration' => $query->orderBy('duration_minutes'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderByDesc('reviews_count'),
        };

        return TrailResource::collection(
            $query->paginate($filters['per_page'] ?? 24)->withQueryString()
        );
    }

    public function show(Trail $trail): TrailDetailResource
    {
        abort_unless($trail->status === TrailStatus::Published, 404);

        $trail->load(['region', 'reviews'])->loadAvg('reviews', 'rating')->loadCount('reviews');

        return new TrailDetailResource($trail);
    }

    public function nearby(Trail $trail): AnonymousResourceCollection
    {
        abort_unless($trail->status === TrailStatus::Published, 404);

        // Equirectangular approximation is plenty for ranking within one country.
        $nearby = Trail::query()->published()->withRating()->with('region')
            ->whereKeyNot($trail->id)
            ->get()
            ->sortBy(fn (Trail $t) => (($t->lat - $trail->lat) ** 2) + ((($t->lng - $trail->lng) * cos(deg2rad($trail->lat))) ** 2))
            ->take(3)
            ->values();

        return TrailResource::collection($nearby);
    }

    public function store(StoreTrailRequest $request): JsonResponse
    {
        $data = $request->validated();

        $gallery = collect($request->file('photos', []))
            ->map(fn ($file) => '/storage/'.$file->store('trail-submissions', 'public'))
            ->all();

        $trail = Trail::create([
            ...collect($data)->except(['photos', 'region'])->all(),
            'region_id' => $data['region_id'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
            'summary' => Str::limit($data['description'], 270),
            'cover_image' => $gallery[0] ?? null,
            'gallery' => $gallery,
            'status' => TrailStatus::Pending,
        ]);

        return response()->json([
            'message' => 'Thanks! Your trail was submitted for review.',
            'data' => ['slug' => $trail->slug, 'status' => $trail->status],
        ], 201);
    }
}
