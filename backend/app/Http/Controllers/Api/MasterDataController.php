<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

class MasterDataController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => ItemCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function locations(): JsonResponse
    {
        return response()->json([
            'data' => Location::orderBy('name')->get(['id', 'name', 'description']),
        ]);
    }
}