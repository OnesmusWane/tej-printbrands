<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryItemController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image'    => ['required', 'file', 'image', 'max:8192'],
            'category' => ['required', 'string', 'max:80'],
        ]);

        $file = $request->file('image');
        $path = $file->store('uploads/gallery', 'public');

        $item = GalleryItem::create([
            'image_url'   => asset('storage/' . $path),
            'category'    => $request->input('category'),
            'file_size'   => $file->getSize(),
            'uploaded_at' => now(),
            'is_visible'  => true,
        ]);

        return response()->json($item, 201);
    }

    public function destroy(GalleryItem $galleryItem): JsonResponse
    {
        $galleryItem->delete();
        return response()->json(null, 204);
    }
}
