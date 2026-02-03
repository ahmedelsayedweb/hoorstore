<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    /**
     * Get all products
     */
    public function index(): JsonResponse
    {
        $products = Product::all();
        return response()->json($products);
    }

    /**
     * Get single product by ID
     */
    public function show($id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    /**
     * Create new product
     */
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'salePrice' => 'nullable|numeric|min:0',
            'category' => 'required|string|max:100',
            'image' => 'nullable|string',
            'images' => 'nullable|array',
            'sizes' => 'nullable|array',
            'colors' => 'nullable|array',
            'heights' => 'nullable|array',
            'weights' => 'nullable|array',
            'description' => 'nullable|string',
            'inStock' => 'nullable|boolean',
        ]);

        $product = Product::create([
            'name' => $request->input('name'),
            'price' => $request->input('price'),
            'salePrice' => $request->input('salePrice'),
            'category' => $request->input('category'),
            'image' => $request->input('image', ''),
            'images' => $request->input('images', []),
            'sizes' => $request->input('sizes', []),
            'colors' => $request->input('colors', []),
            'heights' => $request->input('heights', []),
            'weights' => $request->input('weights', []),
            'description' => $request->input('description', ''),
            'inStock' => $request->input('inStock', true),
        ]);

        return response()->json($product, 201);
    }

    /**
     * Update product
     */
    public function update(Request $request, $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $this->validate($request, [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'salePrice' => 'nullable|numeric|min:0',
            'category' => 'sometimes|string|max:100',
            'image' => 'nullable|string',
            'images' => 'nullable|array',
            'sizes' => 'nullable|array',
            'colors' => 'nullable|array',
            'heights' => 'nullable|array',
            'weights' => 'nullable|array',
            'description' => 'nullable|string',
            'inStock' => 'nullable|boolean',
        ]);

        $product->update($request->only([
            'name',
            'price',
            'salePrice',
            'category',
            'image',
            'images',
            'sizes',
            'colors',
            'heights',
            'weights',
            'description',
            'inStock',
        ]));

        return response()->json($product);
    }

    /**
     * Delete product
     */
    public function destroy($id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Delete main image
        if ($product->image) {
            $this->deleteImageFromUrl($product->image);
        }

        // Delete all additional images
        if ($product->images && is_array($product->images)) {
            foreach ($product->images as $imageUrl) {
                $this->deleteImageFromUrl($imageUrl);
            }
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted', 'product' => $product]);
    }

    /**
     * Delete image file from URL
     */
    private function deleteImageFromUrl($url): bool
    {
        if (empty($url)) {
            return false;
        }

        // Extract the path from URL (after /storage/)
        $pattern = '/\/storage\/(.+)$/';
        if (preg_match($pattern, $url, $matches)) {
            $relativePath = $matches[1];
            $fullPath = storage_path('app/public/' . $relativePath);

            if (File::exists($fullPath)) {
                return File::delete($fullPath);
            }
        }

        return false;
    }

    /**
     * Upload image
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if (!$request->hasFile('image')) {
            return response()->json(['error' => 'No image file provided'], 400);
        }

        $file = $request->file('image');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Organize images in folders by date (year/month/day)
        $datePath = date('Y/m/d');
        $path = 'uploads/images/' . $datePath;

        // Create directory if it doesn't exist
        $fullPath = storage_path('app/public/' . $path);
        if (!File::isDirectory($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        $file->move($fullPath, $filename);

        $baseUrl = $request->getSchemeAndHttpHost();
        $url = $baseUrl . '/storage/' . $path . '/' . $filename;

        return response()->json([
            'message' => 'Image uploaded successfully',
            'filename' => $filename,
            'url' => $url,
        ], 201);
    }

    /**
     * Delete a single image
     */
    public function deleteImage(Request $request): JsonResponse
    {
        $this->validate($request, [
            'url' => 'required|string',
        ]);

        $url = $request->input('url');
        $deleted = $this->deleteImageFromUrl($url);

        if ($deleted) {
            return response()->json(['message' => 'Image deleted successfully']);
        }

        return response()->json(['error' => 'Image not found or could not be deleted'], 404);
    }
}
