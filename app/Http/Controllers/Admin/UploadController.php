<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUploadRequest;
use App\Services\ImagePipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function store(StoreUploadRequest $request, ImagePipeline $pipeline): JsonResponse
    {
        $paths = $pipeline->store($request->file('file'), 'uploads/content');

        return response()->json([
            'url' => Storage::disk('public')->url($paths['original']),
        ]);
    }
}
