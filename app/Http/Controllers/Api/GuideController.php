<?php

namespace App\Http\Controllers\Api;

use App\Enums\GuideLanguage;
use App\Enums\GuideSpecialty;
use App\Http\Controllers\Controller;
use App\Http\Resources\GuideDetailResource;
use App\Http\Resources\GuideResource;
use App\Models\Guide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class GuideController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', Rule::enum(GuideLanguage::class)],
            'specialty' => ['nullable', Rule::enum(GuideSpecialty::class)],
            'kind' => ['nullable', Rule::in(['guide', 'agency'])],
        ]);

        $guides = Guide::query()->published()->withRating()->withCount('activeTours')
            ->when($filters['region'] ?? null, fn ($q, $v) => $q->whereJsonContains('regions', $v))
            ->when($filters['language'] ?? null, fn ($q, $v) => $q->whereJsonContains('languages', $v))
            ->when($filters['specialty'] ?? null, fn ($q, $v) => $q->whereJsonContains('specialties', $v))
            ->when($filters['kind'] ?? null, fn ($q, $v) => $q->where('kind', $v))
            ->orderBy('is_sample')->orderByDesc('is_verified')->orderByDesc('reviews_count')->orderBy('name')
            ->get();

        return GuideResource::collection($guides);
    }

    public function show(Guide $guide): GuideDetailResource
    {
        abort_unless($guide->is_published, 404);

        $guide->load([
            'activeTours.trails' => fn ($q) => $q->published()->with(['district', 'region']),
            'reviews',
        ])->loadAvg('reviews', 'rating')->loadCount(['reviews', 'activeTours']);

        return new GuideDetailResource($guide);
    }

    /** Reveals every contact channel the guide has, and counts the reveal. */
    public function contact(Guide $guide): JsonResponse
    {
        abort_unless($guide->is_published, 404);

        $guide->increment('contact_reveals');
        $handle = fn (?string $v) => $v ? ltrim(preg_replace('#^https?://(www\.)?(instagram\.com|t\.me)/#i', '', $v), '@/') : null;
        $digits = fn (?string $v) => $v ? preg_replace('/\D+/', '', $v) : null;

        return response()->json(['data' => [
            'phone' => $guide->phone,
            'whatsapp' => $guide->whatsapp,
            'whatsapp_url' => $guide->whatsapp ? 'https://wa.me/'.$digits($guide->whatsapp) : null,
            'email' => $guide->email,
            'website' => $guide->website,
            'instagram' => $guide->instagram ? '@'.$handle($guide->instagram) : null,
            'instagram_url' => $guide->instagram ? 'https://instagram.com/'.$handle($guide->instagram) : null,
            'telegram' => $guide->telegram ? '@'.$handle($guide->telegram) : null,
            'telegram_url' => $guide->telegram ? 'https://t.me/'.$handle($guide->telegram) : null,
            'is_sample' => $guide->is_sample,
        ]]);
    }
}
