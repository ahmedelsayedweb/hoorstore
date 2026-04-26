<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::orderBy('date', 'desc')->orderBy('transaction_number', 'desc');

        if ($request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('product', 'like', "%{$search}%");
            });
        }

        if ($request->input('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }
        if ($request->input('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        // debtor filter: 'has' = only rows with debtor > 0, 'none' = only rows without debtor
        if ($request->input('debtor') === 'has') {
            $query->whereNotNull('debtor')->where('debtor', '>', 0);
        } elseif ($request->input('debtor') === 'none') {
            $query->where(function ($q) {
                $q->whereNull('debtor')->orWhere('debtor', 0);
            });
        }

        // creditor filter
        if ($request->input('creditor') === 'has') {
            $query->whereNotNull('creditor')->where('creditor', '>', 0);
        } elseif ($request->input('creditor') === 'none') {
            $query->where(function ($q) {
                $q->whereNull('creditor')->orWhere('creditor', 0);
            });
        }

        // Totals on the full filtered set (before pagination)
        $totalsQuery = clone $query;
        $totals = $totalsQuery->selectRaw('
            COUNT(*) as total_count,
            SUM(total)    as total_amount,
            SUM(debtor)   as total_debtor,
            SUM(creditor) as total_creditor
        ')->first();

        // Pagination
        $perPage = max(1, (int) $request->input('per_page', 20));
        $page    = max(1, (int) $request->input('page', 1));
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'            => $paginated->items(),
            'total_amount'    => (float) $totals->total_amount,
            'total_debtor'    => (float) $totals->total_debtor,
            'total_creditor'  => (float) $totals->total_creditor,
            'count'           => (int)   $totals->total_count,
            'current_page'    => $paginated->currentPage(),
            'last_page'       => $paginated->lastPage(),
            'per_page'        => $paginated->perPage(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $sale = Sale::create([
            'transaction_number' => $request->input('transaction_number'),
            'date'               => $request->input('date'),
            'customer_name'      => $request->input('customer_name'),
            'product'            => $request->input('product'),
            'quantity'           => $request->input('quantity'),
            'unit_price'         => $request->input('unit_price'),
            'total'              => $request->input('total'),
            'debtor'             => $request->input('debtor'),
            'creditor'           => $request->input('creditor'),
            'notes'              => $request->input('notes'),
        ]);

        return response()->json($sale, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $sale = Sale::find($id);
        if (!$sale) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $sale->update([
            'transaction_number' => $request->input('transaction_number', $sale->transaction_number),
            'date'               => $request->input('date', $sale->date),
            'customer_name'      => $request->input('customer_name', $sale->customer_name),
            'product'            => $request->input('product', $sale->product),
            'quantity'           => $request->input('quantity', $sale->quantity),
            'unit_price'         => $request->input('unit_price', $sale->unit_price),
            'total'              => $request->input('total', $sale->total),
            'debtor'             => $request->input('debtor', $sale->debtor),
            'creditor'           => $request->input('creditor', $sale->creditor),
            'notes'              => $request->input('notes', $sale->notes),
        ]);

        return response()->json($sale);
    }

    public function destroy($id): JsonResponse
    {
        $sale = Sale::find($id);
        if (!$sale) {
            return response()->json(['error' => 'Not found'], 404);
        }
        $sale->delete();
        return response()->json(['success' => true]);
    }
}
