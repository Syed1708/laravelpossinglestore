<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Ingredient;
use App\Models\Supplier;
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
     * Store the draft order. Performs strict two-pass validation checks on CSV contents [1, 2].
     */


    /**
     * Store the draft order. Performs strict two-pass validation and defensive binary checks [1, 2].
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'po_number' => 'required|integer|unique:purchase_orders,po_number',
            'import_file' => 'required|file|extensions:csv,txt|max:2048',
        ]);

        $file = $request->file('import_file');
        $filePath = $file->getRealPath();

        // 🚀 THE FIX 1: Detect binary ZIP/Excel files immediately
        // Excel .xlsx files are ZIP archives and ALWAYS start with the 'PK' binary magic bytes!
        $magicBytes = file_get_contents($filePath, false, null, 0, 2);
        if ($magicBytes === 'PK') {
            return redirect()->back()
                ->withInput()
                ->with('error', "Format de fichier invalide. Vous avez téléversé un fichier Excel binaire (.xlsx). Veuillez d'abord l'exporter au format CSV (Séparateur: point-virgule) depuis Google Sheets ou Excel avant de l'importer.");
        }

        // Strip out hidden UTF-8 BOM characters from Google Sheets if present
        $fileContent = file_get_contents($filePath);
        if (str_starts_with($fileContent, "\xef\xbb\xbf")) {
            $fileContent = substr($fileContent, 3);
            file_put_contents($filePath, $fileContent);
        }

        // Automatically detect separators (comma or semicolon)
        $firstLine = file_get_contents($filePath, false, null, 0, 500);
        $separator = (strpos($firstLine, ';') !== false) ? ';' : ',';

        $handle = fopen($filePath, 'r');

        // Skip header row
        fgetcsv($handle, 1000, $separator);

        $rowsToProcess = [];
        $missingIngredients = [];
        $hasFormattingError = false;

        // 🚀 PASS 1: PRE-SCAN & DEFENSIVE VALIDATION
        while (($row = fgetcsv($handle, 1000, $separator)) !== false) {
            // 🚀 THE FIX 2: If a row has fewer than 3 columns, it's poorly formatted
            if (count($row) < 3) {
                $hasFormattingError = true;
                break;
            }

            // Convert French Latin-1/ISO-8859-1 encodings to UTF-8
            $ingredientName = mb_convert_encoding(trim($row[0]), 'UTF-8', 'UTF-8, ISO-8859-1, ASCII');

            // 🚀 THE FIX 3: Defensive string length & non-printable character checks
            // If the name is ridiculously long, or contains binary controls, it's a corrupted file!
            if (strlen($ingredientName) > 100 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $ingredientName)) {
                $hasFormattingError = true;
                break;
            }

            $qtyOrdered = floatval(str_replace(',', '.', trim($row[1])));
            $unitPrice = floatval(str_replace(',', '.', trim($row[2])));

            // Case-insensitive SQL fuzzy matching (LIKE)
            $ingredient = Ingredient::where('name', 'LIKE', '%' . $ingredientName . '%')->first();

            if ($ingredient) {
                $rowsToProcess[] = [
                    'ingredient_id' => $ingredient->id,
                    'quantity_ordered' => $qtyOrdered,
                    'unit_price' => $unitPrice,
                ];
            } else {
                $missingIngredients[] = $ingredientName;
            }
        }
        fclose($handle);

        // 🚀 THE FIX 4: Throw clean, human-readable error pages!
        if ($hasFormattingError) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Le fichier CSV importé semble corrompu ou mal formaté. Veuillez vérifier que votre fichier utilise des lignes de texte lisibles et un séparateur valide (virgule ou point-virgule).");
        }

        if (!empty($missingIngredients)) {
            $missingList = implode(', ', $missingIngredients);
            return redirect()->back()
                ->withInput()
                ->with('error', "Échec de l'importation. Les ingrédients suivants dans votre fichier n'existent pas en stock : [ {$missingList} ]. Veuillez d'abord les créer ou corriger l'orthographe dans votre fichier.");
        }

        // 🚀 PASS 2: DATABASE EXECUTION (Runs only if all validation passed)
        DB::beginTransaction();
        try {
            $order = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'po_number' => $request->po_number,
                'status' => 'pending',
            ]);

            foreach ($rowsToProcess as $itemData) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'ingredient_id' => $itemData['ingredient_id'],
                    'quantity_ordered' => $itemData['quantity_ordered'],
                    'quantity_received' => 0.00,
                    'unit_price' => $itemData['unit_price'],
                ]);
            }

            DB::commit();
            return redirect()->route('admin.purchases.index')->with('success', 'Bon de commande créé avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors de la création : ' . $e->getMessage());
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
            'invoice_photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'received' => 'required|array',
            'prices' => 'required|array',
            'notes' => 'nullable|string|max:1000', // 🚀 Validate the notes field

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
                    'unit_price' => $unitPrice,
                ]);

                $totalCost += floatval($qtyReceived) * $unitPrice;

                // Increment stocks
                $ingredient = Ingredient::findOrFail($item->ingredient_id);
                $ingredient->increment('stock_level', floatval($qtyReceived));
            }

            $order->update([
                'invoice_number' => $request->invoice_number,
                'total_cost' => $totalCost,
                'notes' => $request->notes, // 🚀 Save the manager's notes

                'status' => 'received',
                'received_at' => Carbon::now(),
            ]);

            // 🚀 THE AUTOMATION: Automatically log this delivered purchase as a Food Cost expense!
            \App\Models\Expense::create([
                'category' => 'food_cost',
                'description' => "Approvisionnement PO #{$order->po_number} (Facture #{$order->invoice_number})",
                'amount' => $totalCost,
                'payment_method' => 'bank_transfer',
                'purchase_order_id' => $order->id,
                'paid_at' => Carbon::now(),
            ]);

            DB::commit();
            return redirect()->route('admin.purchases.index')->with('success', "Livraison enregistrée ! Stock mis à jour (+{$totalCost} €).");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Échec de la réception : ' . $e->getMessage());
        }
    }

    /**
     * 🚀 NEW: Delete a draft Purchase Order cleanly before it is received
     */
    public function destroy($id)
    {
        $order = PurchaseOrder::findOrFail($id);

        if ($order->status === 'received') {
            return redirect()->back()->with('error', 'Impossible de supprimer un bon de livraison déjà clôturé et enregistré en stock.');
        }

        $order->delete();
        return redirect()->route('admin.purchases.index')->with('success', 'Bon de commande supprimé avec succès.');
    }


    /**
     * 🚀 NEW: Mark a pending delivery as Cancelled/Rejected.
     * The stock levels remain completely unchanged [1].
     */
    public function cancel(Request $request, $id)
    {
        $order = PurchaseOrder::findOrFail($id);

        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 'Impossible d\'annuler une livraison déjà clôturée.');
        }

        // Update the order status to cancelled
        $order->update([
            'status' => 'cancelled',
            'notes' => $request->notes, // 🚀 Save the manager's notes
            'received_at' => null // Ensure no arrival timestamp is saved
        ]);

        return redirect()->route('admin.purchases.index')
            ->with('success', 'La livraison a été annulée et marquée comme "Annulée". Le stock de vos ingrédients est resté inchangé.');
    }
}
