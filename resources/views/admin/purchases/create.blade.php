<!-- resources/views/admin/purchases/create.blade.php -->
@extends('tyro-dashboard::layouts.app')

@section('title', 'Nouvelle Commande')

@section('breadcrumb')
<a href="{{ route('admin.purchases.index') }}">Livraisons Stock</a>
<span style="margin: 0 8px;">/</span>
<span>Créer</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg p-6 shadow-sm border border-gray-200">
    <h2 class="text-xl font-bold text-slate-800 mb-6" style="color: var(--foreground);">➕ Créer un Bon de Commande (PO)</h2>
    
    <form action="{{ route('admin.purchases.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Supplier Selector -->
        <div class="form-group">
            <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">Fournisseur</label>
            <select name="supplier_id" required class="form-control">
                <option value="">Sélectionnez un fournisseur...</option>
                @foreach($suppliers as $sup)
                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- 🚀 UPDATED: Pre-filled PO Number field -->
        <div class="form-group">
            <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">Numéro de Commande (PO #)</label>
            <input type="number" name="po_number" value="{{ $nextPoNumber }}" readonly class="form-control" style="background-color: var(--muted); cursor: not-allowed;">
            <p style="font-size: 11px; color: var(--muted-foreground); margin-top: 5px;">Le numéro de bon de commande est généré de manière séquentielle automatiquement.</p>
        </div>

        <!-- 🚀 EXCEL / CSV IMPORTER FILE INPUT -->
        <div class="form-group" style="background: var(--muted); padding: 15px; border-radius: 8px; border: 1px dashed var(--border);">
            <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">📈 Importer depuis un fichier Excel (CSV)</label>
            <input type="file" name="import_file" accept=".csv" class="form-control" style="background: none; border: none; padding: 0;">
            <p style="font-size: 11px; color: var(--muted-foreground); margin-top: 5px; line-height: 15px;">
                Le fichier doit être au format <strong>.csv</strong>. <br>
                Structure des colonnes requise : <code>Nom de l'ingrédient, Quantité Commandée, Prix unitaire HT</code>
            </p>
        </div>

        <button type="submit" class="btn btn-primary" style="font-weight: bold; padding: 12px; width: 100%;">
            Enregistrer le Bon de Commande
        </button>
    </form>
</div>
@endsection