<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Purchase::orderBy('date', 'desc')->orderBy('transaction_number', 'desc');

        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                  ->orWhere('product', 'like', "%{$search}%");
            });
        }

        if ($request->has('date_from') && $request->input('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to') && $request->input('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        $purchases = $query->get();
        $totalAmount = $purchases->sum('total');

        return response()->json([
            'data' => $purchases,
            'total_amount' => $totalAmount,
            'count' => $purchases->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $purchase = Purchase::create([
            'transaction_number' => $request->input('transaction_number'),
            'date'               => $request->input('date'),
            'supplier_name'      => $request->input('supplier_name'),
            'product'            => $request->input('product'),
            'quantity'           => $request->input('quantity'),
            'total'              => $request->input('total'),
            'notes'              => $request->input('notes'),
        ]);

        return response()->json($purchase, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $purchase = Purchase::find($id);
        if (!$purchase) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $purchase->update([
            'transaction_number' => $request->input('transaction_number', $purchase->transaction_number),
            'date'               => $request->input('date', $purchase->date),
            'supplier_name'      => $request->input('supplier_name', $purchase->supplier_name),
            'product'            => $request->input('product', $purchase->product),
            'quantity'           => $request->input('quantity', $purchase->quantity),
            'total'              => $request->input('total', $purchase->total),
            'notes'              => $request->input('notes', $purchase->notes),
        ]);

        return response()->json($purchase);
    }

    public function destroy($id): JsonResponse
    {
        $purchase = Purchase::find($id);
        if (!$purchase) {
            return response()->json(['error' => 'Not found'], 404);
        }
        $purchase->delete();
        return response()->json(['success' => true]);
    }
}
