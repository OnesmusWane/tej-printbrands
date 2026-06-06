<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SiteSection::query();

        if ($request->filled('page_slug')) {
            $query->whereHas('page', fn ($q) => $q->where('slug', $request->page_slug));
        }

        return response()->json($query->orderBy('sort_order')->get());
    }
}
