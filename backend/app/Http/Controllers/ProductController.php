<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

        $product->delete();

        return response()->json(['message' => 'Product deleted', 'product' => $product]);
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
        $path = 'uploads/images';

        $file->move(storage_path('app/public/' . $path), $filename);

        $baseUrl = $request->getSchemeAndHttpHost();
        $url = $baseUrl . '/storage/' . $path . '/' . $filename;

        return response()->json([
            'message' => 'Image uploaded successfully',
            'filename' => $filename,
            'url' => $url,
        ], 201);
    }
}
