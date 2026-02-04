<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class CartController extends Controller
{
    /**
     * Get cart items for specific browser
     */
    public function index($browserId): JsonResponse
    {
        $cart = Cart::with('items')->where('browser_id', $browserId)->first();

        if (!$cart) {
            return response()->json([]);
        }

        $items = $cart->items->map(function ($item) {
            return [
                'id' => $item->id,
                'productId' => $item->product_id,
                'name' => $item->name,
                'price' => $item->price,
                'image' => $item->image,
                'color' => $item->color,
                'sizes' => $item->sizes,
                'height' => $item->height,
                'quantity' => $item->quantity,
                'addedAt' => $item->added_at ? $item->added_at->toISOString() : $item->created_at->toISOString(),
            ];
        });

        return response()->json($items);
    }

    /**
     * Add item to cart
     */
    public function store(Request $request, $browserId): JsonResponse
    {
        $this->validate($request, [
            'productId' => 'required|integer',
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'color' => 'nullable|string',
            'sizes' => 'nullable|array',
            'sizes.*' => 'string',
            'height' => 'nullable|string',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $cart = Cart::firstOrCreate(['browser_id' => $browserId]);

        $sizes = $request->input('sizes', []);
        $sizesJson = json_encode($sizes);

        // Check if item already exists with same options
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->input('productId'))
            ->where('color', $request->input('color'))
            ->whereRaw('JSON_CONTAINS(sizes, ?) AND JSON_CONTAINS(?, sizes)', [$sizesJson, $sizesJson])
            ->where('height', $request->input('height'))
            ->first();

        if ($existingItem) {
            $existingItem->quantity += $request->input('quantity', 1);
            $existingItem->save();
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->input('productId'),
                'name' => $request->input('name'),
                'price' => $request->input('price'),
                'image' => $request->input('image'),
                'color' => $request->input('color'),
                'sizes' => $sizes,
                'height' => $request->input('height'),
                'quantity' => $request->input('quantity', 1),
                'added_at' => Carbon::now(),
            ]);
        }

        return $this->index($browserId)->setStatusCode(201);
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $browserId, $id): JsonResponse
    {
        $cart = Cart::where('browser_id', $browserId)->first();

        if (!$cart) {
            return response()->json(['error' => 'Cart not found'], 404);
        }

        $item = CartItem::where('cart_id', $cart->id)->where('id', $id)->first();

        if (!$item) {
            return response()->json(['error' => 'Cart item not found'], 404);
        }

        $this->validate($request, [
            'quantity' => 'sometimes|integer|min:1',
            'color' => 'sometimes|string',
            'sizes' => 'sometimes|array',
            'sizes.*' => 'string',
            'height' => 'sometimes|string',
            'image' => 'sometimes|string',
        ]);

        $updateData = $request->only(['quantity', 'color', 'height', 'image']);
        if ($request->has('sizes')) {
            $updateData['sizes'] = $request->input('sizes');
        }
        $item->update($updateData);

        return response()->json([
            'id' => $item->id,
            'productId' => $item->product_id,
            'name' => $item->name,
            'price' => $item->price,
            'image' => $item->image,
            'color' => $item->color,
            'sizes' => $item->sizes,
            'height' => $item->height,
            'quantity' => $item->quantity,
            'addedAt' => $item->added_at ? $item->added_at->toISOString() : $item->created_at->toISOString(),
        ]);
    }

    /**
     * Remove item from cart
     */
    public function destroy($browserId, $id): JsonResponse
    {
        $cart = Cart::where('browser_id', $browserId)->first();

        if (!$cart) {
            return response()->json(['error' => 'Cart not found'], 404);
        }

        $item = CartItem::where('cart_id', $cart->id)->where('id', $id)->first();

        if (!$item) {
            return response()->json(['error' => 'Cart item not found'], 404);
        }

        $deletedItem = [
            'id' => $item->id,
            'productId' => $item->product_id,
            'name' => $item->name,
        ];

        $item->delete();

        return response()->json(['message' => 'Item removed from cart', 'item' => $deletedItem]);
    }

    /**
     * Clear entire cart
     */
    public function clear($browserId): JsonResponse
    {
        $cart = Cart::where('browser_id', $browserId)->first();

        if ($cart) {
            CartItem::where('cart_id', $cart->id)->delete();
        }

        return response()->json(['message' => 'Cart cleared']);
    }
}
