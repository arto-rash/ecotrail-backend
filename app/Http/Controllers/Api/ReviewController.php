<?php

namespace App\Http\Controllers\Api;

use App\Enums\TrailStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Guide;
use App\Models\Place;
use App\Models\Trail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function storeForTrail(Request $request, Trail $trail): JsonResponse
    {
        abort_unless($trail->status === TrailStatus::Published, 404);

        return $this->store($request, $trail);
    }

    public function storeForGuide(Request $request, Guide $guide): JsonResponse
    {
        abort_unless($guide->is_published, 404);

        return $this->store($request, $guide);
    }

    public function storeForPlace(Request $request, Place $place): JsonResponse
    {
        return $this->store($request, $place);
    }

    private function store(Request $request, Model $reviewable): JsonResponse
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'min:2', 'max:80'],
            'rating' => ['required', 'numeric', 'between:0.5,5', 'multiple_of:0.5'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            // Honeypot: real visitors never see or fill this field.
            'website' => ['prohibited'],
        ]);

        $review = $reviewable->reviews()->create([
            ...$data,
            'locale' => app()->getLocale(),
            'status' => 'published',
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
        ]);

        return (new ReviewResource($review))->response()->setStatusCode(201);
    }
}
