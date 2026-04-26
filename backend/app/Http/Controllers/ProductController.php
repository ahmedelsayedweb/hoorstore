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
     * Get all products (with optional pagination and category filter)
     * Query params: page, per_page, category
     * Without page param: returns all products (backward compatible)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::orderBy('created_at', 'desc');

        // Category filter
        // if ($request->has('category') && strtolower($request->input('category')) !== 'all') {
        //     $category = ucfirst(strtolower($request->input('category')));
        //     $query->where(function ($q) use ($category) {
        //         $q->whereJsonContains('category', $category)
        //           ->orWhere('category', 'LIKE', '%"' . $category . '"%');
        //     });
        // }
        if ($request->filled('category') && strtolower($request->input('category')) !== 'all') {
            $category = strtolower($request->input('category')); // ✅ lowercase only
            $query->whereJsonContains('category', $category);
        }

        // If no page param, return all (for admin)
        if (!$request->has('page')) {
            return response()->json($query->get());
        }

        if (!($request->filled('type') && strtolower($request->input('type')) === 'admin')) {
            $query->where('inStock', true);
        }

        // Paginated response
        $perPage = (int) $request->input('per_page', 8);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
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
            'code' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'salePrice' => 'nullable|numeric|min:0',
            'category' => 'required|array|min:1',
            'category.*' => 'string|max:100',
            'image' => 'nullable|string',
            'images' => 'nullable|array',
            'sizes' => 'nullable|array',
            'colors' => 'nullable|array',
            'heights' => 'nullable|array',
            'weights' => 'nullable|array',
            'variants' => 'nullable|array',
            'description' => 'nullable|string',
            'inStock' => 'nullable|boolean',
        ]);

        $product = Product::create([
            'code' => $request->input('code', ''),
            'name' => $request->input('name'),
            'price' => $request->input('price'),
            'salePrice' => $request->input('salePrice'),
            'category' => $request->input('category', []),
            'image' => $request->input('image', ''),
            'images' => $request->input('images', []),
            'sizes' => $request->input('sizes', []),
            'colors' => $request->input('colors', []),
            'heights' => $request->input('heights', []),
            'weights' => $request->input('weights', []),
            'variants' => $request->input('variants'),
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
            'code' => 'nullable|string|max:100',
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'salePrice' => 'nullable|numeric|min:0',
            'category' => 'sometimes|array|min:1',
            'category.*' => 'string|max:100',
            'image' => 'nullable|string',
            'images' => 'nullable|array',
            'sizes' => 'nullable|array',
            'colors' => 'nullable|array',
            'heights' => 'nullable|array',
            'weights' => 'nullable|array',
            'variants' => 'nullable|array',
            'description' => 'nullable|string',
            'inStock' => 'nullable|boolean',
        ]);

        $product->update($request->only([
            'code',
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
            'variants',
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
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi,wmv,webm|max:20480',
        ]);

        if (!$request->hasFile('image')) {
            return response()->json(['error' => 'No file provided'], 400);
        }

        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = time() . '_' . uniqid() . '.' . $extension;

        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'webm'];
        $isVideo = in_array($extension, $videoExtensions);

        // Organize files in folders by type and date (year/month/day)
        $datePath = date('Y/m/d');
        $path = 'uploads/' . ($isVideo ? 'videos' : 'images') . '/' . $datePath;

        // Create directory if it doesn't exist
        $fullPath = storage_path('app/public/' . $path);
        if (!File::isDirectory($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        $file->move($fullPath, $filename);

        $baseUrl = $request->getSchemeAndHttpHost();
        $url = $baseUrl . '/storage/' . $path . '/' . $filename;

        return response()->json([
            'message' => ($isVideo ? 'Video' : 'Image') . ' uploaded successfully',
            'filename' => $filename,
            'url' => $url,
            'type' => $isVideo ? 'video' : 'image',
        ], 201);
    }

    /**
     * Cleanup unused uploaded files (images/videos not referenced by any product)
     */
    public function cleanupUnusedFiles(): JsonResponse
    {
        // 1. Collect all URLs referenced by products
        $usedUrls = [];
        $products = Product::all();

        foreach ($products as $product) {
            // Main image
            if ($product->image) {
                $usedUrls[] = $product->image;
            }

            // Album images
            if ($product->images && is_array($product->images)) {
                foreach ($product->images as $imageUrl) {
                    $usedUrls[] = $imageUrl;
                }
            }

            // Color images
            if ($product->colors && is_array($product->colors)) {
                foreach ($product->colors as $color) {
                    if (is_array($color) && !empty($color['image'])) {
                        $usedUrls[] = $color['image'];
                    }
                }
            }

            // Variant color images
            if ($product->variants && is_array($product->variants)) {
                foreach ($product->variants as $variant) {
                    if (is_array($variant) && !empty($variant['image'])) {
                        $usedUrls[] = $variant['image'];
                    }
                }
            }
        }

        // 2. Extract relative paths from URLs
        $usedPaths = [];
        foreach ($usedUrls as $url) {
            $pattern = '/\/storage\/(.+)$/';
            if (preg_match($pattern, $url, $matches)) {
                $usedPaths[] = $matches[1];
            }
        }

        // 3. Scan all files on disk
        $basePath = storage_path('app/public/uploads');
        $allFiles = [];

        if (File::isDirectory($basePath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = 'uploads' . str_replace($basePath, '', $file->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $allFiles[] = $relativePath;
                }
            }
        }

        // 4. Find and delete unused files
        $deleted = [];
        $failed = [];
        $totalSize = 0;

        foreach ($allFiles as $filePath) {
            if (!in_array($filePath, $usedPaths)) {
                $fullPath = storage_path('app/public/' . $filePath);
                $fileSize = File::size($fullPath);

                if (File::delete($fullPath)) {
                    $deleted[] = $filePath;
                    $totalSize += $fileSize;
                } else {
                    $failed[] = $filePath;
                }
            }
        }

        // 5. Clean up empty directories
        if (File::isDirectory($basePath)) {
            $this->removeEmptyDirectories($basePath);
        }

        return response()->json([
            'message' => 'تم تنظيف الملفات غير المستخدمة',
            'total_files_on_disk' => count($allFiles),
            'used_files' => count($usedPaths),
            'deleted_count' => count($deleted),
            'deleted_files' => $deleted,
            'failed_count' => count($failed),
            'freed_space' => $this->formatBytes($totalSize),
        ]);
    }

    /**
     * Remove empty directories recursively
     */
    private function removeEmptyDirectories($path)
    {
        $dirs = File::directories($path);
        foreach ($dirs as $dir) {
            $this->removeEmptyDirectories($dir);
            if (count(File::files($dir)) === 0 && count(File::directories($dir)) === 0) {
                File::deleteDirectory($dir);
            }
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes)
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
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
