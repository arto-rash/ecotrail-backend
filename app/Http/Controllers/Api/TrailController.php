<?php

namespace App\Http\Controllers\Api;

use App\Catalogue\DistrictLocator;
use App\Enums\Difficulty;
use App\Enums\TrailFeature;
use App\Enums\TrailStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrailRequest;
use App\Http\Resources\TrailDetailResource;
use App\Http\Resources\TrailResource;
use App\Models\District;
use App\Models\Guide;
use App\Models\Trail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            'district' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['nullable', 'array'],
            'difficulty.*' => [Rule::enum(Difficulty::class)],
            'feature' => ['nullable', 'array'],
            'feature.*' => [Rule::enum(TrailFeature::class)],
            'max_duration' => ['nullable', 'integer', 'min:1'],
            'max_distance' => ['nullable', 'numeric', 'min:0'],
            'featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['popular', 'rating', 'distance', 'duration', 'name'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = Trail::query()->published()->withRating()->with(['district', 'region']);

        if ($q = $filters['q'] ?? null) {
            $query->where(fn (Builder $w) => self::searchName($w, $q)
                ->orWhereHas('district', fn ($d) => self::searchName($d, $q))
                ->orWhereHas('region', fn ($r) => self::searchName($r, $q)));
        }

        $query
            ->when($filters['region'] ?? null, fn ($w, $slug) => $w->whereHas('region', fn ($r) => $r->where('slug', $slug)))
            ->when($filters['district'] ?? null, fn ($w, $slug) => $w->whereHas('district', fn ($d) => $d->where('slug', $slug)))
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
            'name' => $query->orderBy('name->'.app()->getLocale()),
            default => $query->orderByDesc('is_featured')->orderByDesc('reviews_count')->orderBy('distance_km'),
        };

        return TrailResource::collection($query->paginate($filters['per_page'] ?? 24)->withQueryString());
    }

    public function show(Trail $trail): TrailDetailResource
    {
        abort_unless($trail->status === TrailStatus::Published, 404);

        $trail->load(['district', 'region', 'places.district', 'reviews'])->loadAvg('reviews', 'rating')->loadCount('reviews');
        $trail->setRelation('guides', Guide::query()->published()->withRating()->withCount('activeTours')
            ->whereHas('activeTours.trails', fn ($q) => $q->whereKey($trail->id))
            ->orderBy('is_sample')->orderByDesc('is_verified')->get());

        return new TrailDetailResource($trail);
    }

    public function nearby(Trail $trail): AnonymousResourceCollection
    {
        abort_unless($trail->status === TrailStatus::Published, 404);

        return TrailResource::collection(
            Trail::query()->published()->withRating()->with(['district', 'region'])->whereKeyNot($trail->id)->get()
                ->sortBy(fn (Trail $t) => self::distance2($t->lat, $t->lng, $trail->lat, $trail->lng))
                ->take(3)->values()
        );
    }

    /**
     * The mapped route as GPX 1.1, for Google My Maps, Organic Maps, OsmAnd, Wikiloc and
     * GPS devices. Each mapped section becomes a track segment; the start is a waypoint.
     */
    public function gpx(Trail $trail): Response
    {
        abort_unless($trail->status === TrailStatus::Published && $trail->route, 404);

        $e = fn (?string $s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $name = $e($trail->localized('name', true));
        $segments = collect($trail->route['coordinates'])
            ->map(fn (array $line) => "      <trkseg>\n".collect($line)
                ->map(fn (array $p) => sprintf('        <trkpt lat="%.6f" lon="%.6f"/>', $p[1], $p[0]))
                ->implode("\n")."\n      </trkseg>")
            ->implode("\n");
        $sources = collect($trail->sources ?? [])->map(fn ($s) => "{$s['label']} ({$s['license']})")->implode('; ');

        $xml = <<<GPX
        <?xml version="1.0" encoding="UTF-8"?>
        <gpx version="1.1" creator="EcoTrail" xmlns="http://www.topografix.com/GPX/1/1">
          <metadata>
            <name>{$name}</name>
            <desc>{$e($sources)}</desc>
            <link href="{$e(config('app.frontend_url'))}"><text>EcoTrail</text></link>
          </metadata>
          <wpt lat="{$trail->lat}" lon="{$trail->lng}"><name>{$name} — start</name></wpt>
          <trk>
            <name>{$name}</name>
        {$segments}
          </trk>
        </gpx>
        GPX;

        return response($xml, 200, [
            'Content-Type' => 'application/gpx+xml',
            'Content-Disposition' => "attachment; filename=\"{$trail->slug}.gpx\"",
        ]);
    }

    public function store(StoreTrailRequest $request, DistrictLocator $locator): JsonResponse
    {
        $data = $request->validated();
        $locale = app()->getLocale();
        $found = $locator->locate((float) $data['lng'], (float) $data['lat']);
        $district = $found ? District::with('region')->where('pcode', $found['pcode'])->first() : null;

        $gallery = collect($request->file('photos', []))
            ->map(fn ($file) => '/storage/'.$file->store('trail-submissions', 'public'))
            ->all();

        $trail = Trail::create([
            ...collect($data)->except(['photos', 'name', 'description'])->all(),
            'name' => [$locale => $data['name']],
            'description' => [$locale => $data['description']],
            'summary' => [$locale => Str::limit($data['description'], 270)],
            'slug' => Str::slug(Str::ascii($data['name'])).'-'.Str::lower(Str::random(6)),
            'source' => 'community',
            'district_id' => $district?->id,
            'economic_region_id' => $district?->economic_region_id,
            'difficulty_basis' => 'submitter',
            'stats_estimated' => false,
            'image_url' => $gallery[0] ?? null,
            'gallery' => $gallery,
            'status' => TrailStatus::Pending,
        ]);

        return response()->json([
            'message' => __('Thanks! Your trail was submitted for review.'),
            'data' => ['slug' => $trail->slug, 'status' => $trail->status],
        ], 201);
    }

    /** Case-insensitive match on either language of a translatable name. */
    public static function searchName(Builder $query, string $term): Builder
    {
        $like = '%'.mb_strtolower($term).'%';

        return $query->where(fn ($w) => $w
            ->whereRaw('lower(name->>\'az\') like ?', [$like])
            ->orWhereRaw('lower(name->>\'en\') like ?', [$like]));
    }

    public static function distance2(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return (($lat1 - $lat2) ** 2) + ((($lng1 - $lng2) * cos(deg2rad($lat2))) ** 2);
    }
}
