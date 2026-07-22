<!-- resources/views/admin/purchases/index.blade.php -->
@extends('tyro-dashboard::layouts.app')

@section('title', 'Livraisons Stock')

@section('breadcrumb')
<span>Livraisons Stock</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">📦 Commandes Fournisseurs & Livraisons</h1>
            <p class="page-description">Suivez, importez et réceptionnez vos matières premières.</p>
        </div>
        <div>
            <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary" style="font-weight: bold;">
                ➕ Créer une Commande
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Suivi des Approvisionnements</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Bon Commande (PO)</th>
                        <th>Fournisseur</th>
                        <th>N° Facture</th>
                        <th>Montant TTC</th>
                        <th>Statut</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $row)
                        <tr>
                            <td style="font-weight: bold; color: var(--foreground);">PO #{{ $row->po_number }}</td>
                            <td>{{ $row->supplier->name ?? 'N/A' }}</td>
                            <td style="color: var(--muted-foreground); font-family: monospace;">
                                {{ $row->invoice_number ?? 'Non reçu' }}
                            </td>
                            <td style="font-weight: bold;">
                                {{ number_format($row->total_cost, 2, ',', ' ') }} €
                            </td>
                            <td>
                                @if($row->status === 'received')
                                    <span class="badge badge-success">Livré & Enregistré</span>
                                @elseif($row->status === 'cancelled')
                                    <span class="badge badge-danger">Annulé</span>
                                @else
                                    <span class="badge badge-primary">En cours d'expédition</span>
                                @endif
                            </td>


                            
                            <td style="text-align: right; display: flex; gap: 8px; justify-content: flex-end;">
                                @if($row->status === 'pending')
                                    <a href="{{ route('admin.purchases.show', $row->id) }}" class="btn btn-sm btn-primary">
                                        📥 Réceptionner
                                    </a>
                                    
                                    <!-- 🚀 NEW: DELETE BUTTON (Only available for pending draft orders) -->
                                    <form action="{{ route('admin.purchases.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Supprimer ce bon de commande ?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--destructive); font-weight: bold;">
                                            🗑️ Supprimer
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.purchases.show', $row->id) }}" class="btn btn-sm btn-ghost">
                                        👁️ Consulter
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection