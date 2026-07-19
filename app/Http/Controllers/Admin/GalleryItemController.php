<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Services\ImagePipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryItemController extends Controller
{
    public function store(Request $request, ImagePipeline $pipeline): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:8192', 'mimes:'.implode(',', config('images.allowed_mimes'))],
            'category' => ['required', 'string', 'max:80'],
        ]);

        $file = $request->file('image');
        $paths = $pipeline->store($file, 'uploads/gallery');

        $item = GalleryItem::create([
            'image_url' => Storage::disk('public')->url($paths['original']),
            'category' => $request->input('category'),
            'file_size' => $file->getSize(),
            'uploaded_at' => now(),
            'is_visible' => true,
        ]);

        return response()->json($item, 201);
    }

    public function destroy(GalleryItem $galleryItem): JsonResponse
    {
        $galleryItem->delete();

        return response()->json(null, 204);
    }
}
