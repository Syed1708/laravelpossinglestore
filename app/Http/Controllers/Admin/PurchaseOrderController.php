<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Ingredient;
use App\Models\Supplier;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::with('supplier')->orderBy('created_at', 'desc')->get();
        $isAdmin = auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin');
        return view('admin.purchases.index', compact('orders', 'isAdmin'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $ingredients = Ingredient::orderBy('name', 'asc')->get();
        $isAdmin = auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin');

        // 🚀 AUTO-GENERATE PO NUMBER: Automatically find the highest PO number and add 1
        $nextPoNumber = (PurchaseOrder::max('po_number') ?? 1000) + 1;

        return view('admin.purchases.create', compact('suppliers', 'ingredients', 'isAdmin', 'nextPoNumber'));
    }

    /**
     * Store draft purchase order with CSV pre-scanning and defensive validation
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'po_number'   => 'required|integer|unique:purchase_orders,po_number',
            'import_file' => 'required|file|extensions:csv,txt|max:2048',
        ]);

        $file = $request->file('import_file');
        $filePath = $file->getRealPath();

        // Detect binary ZIP/Excel files immediately (PK magic bytes)
        $magicBytes = file_get_contents($filePath, false, null, 0, 2);
        if ($magicBytes === 'PK') {
            return redirect()->back()
                ->withInput()
                ->with('error', "Invalid file format. You uploaded a binary Excel file (.xlsx). Please export it as a CSV file (Semicolon or Comma separator) before importing.");
        }

        // Strip out hidden UTF-8 BOM characters if present
        $fileContent = file_get_contents($filePath);
        if (str_starts_with($fileContent, "\xef\xbb\xbf")) {
            $fileContent = substr($fileContent, 3);
            file_put_contents($filePath, $fileContent);
        }

        // Automatically detect separators (comma or semicolon)
        $firstLine = file_get_contents($filePath, false, null, 0, 500);
        $separator = (strpos($firstLine, ';') !== false) ? ';' : ',';

        $handle = fopen($filePath, 'r');
        fgetcsv($handle, 1000, $separator); // Skip header row

        $rowsToProcess = []; 
        $missingIngredients = [];
        $hasFormattingError = false;

        // PASS 1: PRE-SCAN & DEFENSIVE VALIDATION
        while (($row = fgetcsv($handle, 1000, $separator)) !== false) {
            if (count($row) < 3) {
                $hasFormattingError = true;
                break;
            }

            $ingredientName = mb_convert_encoding(trim($row[0]), 'UTF-8', 'UTF-8, ISO-8859-1, ASCII');

            if (strlen($ingredientName) > 100 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $ingredientName)) {
                $hasFormattingError = true;
                break;
            }

            $qtyOrdered = floatval(str_replace(',', '.', trim($row[1])));
            $unitPrice = floatval(str_replace(',', '.', trim($row[2])));

            $ingredient = Ingredient::where('name', 'LIKE', '%' . $ingredientName . '%')->first();

            if ($ingredient) {
                $rowsToProcess[] = [
                    'ingredient_id'    => $ingredient->id,
                    'quantity_ordered' => $qtyOrdered,
                    'unit_price'       => $unitPrice,
                ];
            } else {
                $missingIngredients[] = $ingredientName;
            }
        }
        fclose($handle);

        if ($hasFormattingError) {
            return redirect()->back()
                ->withInput()
                ->with('error', "The imported CSV file appears corrupted or improperly formatted. Please verify that your file uses readable text lines and a valid separator.");
        }

        if (!empty($missingIngredients)) {
            $missingList = implode(', ', $missingIngredients);
            return redirect()->back()
                ->withInput()
                ->with('error', "Import failed. The following ingredients do not exist in stock: [ {$missingList} ]. Please create them first or fix spelling in your file.");
        }

        // PASS 2: DATABASE EXECUTION
        DB::beginTransaction();
        try {
            $order = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'po_number'   => $request->po_number,
                'status'      => 'pending',
            ]);

            foreach ($rowsToProcess as $itemData) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'ingredient_id'     => $itemData['ingredient_id'],
                    'quantity_ordered'  => $itemData['quantity_ordered'],
                    'quantity_received' => 0.00,
                    'unit_price'        => $itemData['unit_price'],
                ]);
            }

            DB::commit();
            return redirect()->route('admin.purchases.index')->with('success', 'Purchase Order created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Creation error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $order = PurchaseOrder::with(['supplier', 'items.ingredient'])->findOrFail($id);
        $isAdmin = auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin');

        return view('admin.purchases.show', compact('order', 'isAdmin'));
    }

    public function receive(Request $request, $id)
    {
        $order = PurchaseOrder::findOrFail($id);

        $request->validate([
            'invoice_number' => 'required|string|max:255',
            'invoice_photo'  => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'received'       => 'required|array',
            'prices'         => 'required|array',
            'notes'          => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('invoice_photo')) {
                $photo = $request->file('invoice_photo');
                $photoPath = $photo->store('factures', 'public');
                $order->invoice_photo_path = $photoPath;
            }

            $totalCost = 0;

            foreach ($request->received as $itemId => $qtyReceived) {
                $item = PurchaseOrderItem::findOrFail($itemId);
                $unitPrice = floatval($request->prices[$itemId]);

                $item->update([
                    'quantity_received' => floatval($qtyReceived),
                    'unit_price'        => $unitPrice,
                ]);

                $totalCost += floatval($qtyReceived) * $unitPrice;

                // Increment stocks
                $ingredient = Ingredient::findOrFail($item->ingredient_id);
                $ingredient->increment('stock_level', floatval($qtyReceived));
            }

            $order->update([
                'invoice_number' => $request->invoice_number,
                'total_cost'     => $totalCost,
                'notes'          => $request->notes,
                'status'         => 'received',
                'received_at'    => Carbon::now(),
            ]);

            // 🚀 INTEGRATION FIX: Connect to ExpenseCategory and reference_type for P&L Ledger!
            $foodCostCategory = ExpenseCategory::where('code', 'food_cost')->first();

            Expense::create([
                'expense_category_id' => $foodCostCategory ? $foodCostCategory->id : null,
                'category'            => 'food_cost',
                'description'         => "Supplier Purchase PO #{$order->po_number} (Invoice #{$order->invoice_number})",
                'amount'              => $totalCost,
                'payment_method'      => 'bank_transfer',
                'reference_type'      => 'purchase_order',
                'reference_id'        => $order->id,
                'paid_at'             => Carbon::now(),
            ]);

            DB::commit();
            return redirect()->route('admin.purchases.index')->with('success', "Delivery recorded! Ingredient stocks updated and Food Cost (+€{$totalCost}) logged to P&L.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Reception error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $order = PurchaseOrder::findOrFail($id);

        if ($order->status === 'received') {
            return redirect()->back()->with('error', 'Cannot delete a purchase order that has already been received and recorded into stock.');
        }

        $order->delete();
        return redirect()->route('admin.purchases.index')->with('success', 'Purchase order deleted successfully.');
    }

    public function cancel(Request $request, $id)
    {
        $order = PurchaseOrder::findOrFail($id);

        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 'Cannot cancel an order that has already been closed.');
        }

        $order->update([
            'status'      => 'cancelled',
            'notes'       => $request->notes,
            'received_at' => null
        ]);

        return redirect()->route('admin.purchases.index')
            ->with('success', 'Purchase order cancelled. Stock levels remain unchanged.');
    }
}