<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EconomicRegion;
use App\Models\GuideApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuideApplicationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'kind' => ['required', Rule::in(['guide', 'agency'])],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:190'],
            'regions' => ['nullable', 'array', 'max:14'],
            'regions.*' => [Rule::in(EconomicRegion::pluck('slug')->all())],
            'languages' => ['nullable', 'array', 'max:10'],
            'languages.*' => ['string', 'max:40'],
            'message' => ['required', 'string', 'min:20', 'max:3000'],
            'company' => ['prohibited'], // honeypot
        ]);

        GuideApplication::create($data);

        return response()->json(['message' => __('Thanks! We will get back to you soon.')], 201);
    }
}
