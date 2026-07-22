<!-- resources/views/admin/purchases/show.blade.php -->
@extends('tyro-dashboard::layouts.app')

@section('title', 'Détails Livraison')

@section('content')
<!-- 🚀 MODAL OVERLAY STYLING (Standard CSS to support clean layout transitions) -->
<style>
    .pos-modal-overlay {
        display: none; /* Hidden by default */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5); /* Dimmed background */
        z-index: 9999;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    .pos-modal-overlay.active {
        display: flex; /* Shown when active */
    }
    .pos-modal-card {
        width: 100%;
        max-width: 500px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1) !important;
    }
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">📥 Réception de la Livraison : PO #{{ $order->po_number }}</h1>
            <p class="page-description">Fournisseur : {{ $order->supplier->name }} | Statut : {{ ucfirst($order->status) }}</p>
        </div>
        <div>
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-ghost">⬅️ Retour</a>
        </div>
    </div>
</div>

@if(session('error'))
    <div class="badge badge-danger" style="padding: 10px; font-size: 14px; width: 100%; margin-bottom: 20px; display: block; text-align: center;">
        {{ session('error') }}
    </div>
@endif

<!-- main form wrapping the entire grid -->
<form id="receive-form" action="{{ route('admin.purchases.receive', $order->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    
    <div class="grid-2">
        
        <!-- Left Column: Verification Checklist -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🔍 Vérification des Quantités & Tarifs</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ingrédient</th>
                                <th>Commandé</th>
                                <th>Livré (Vérifié)</th>
                                <th>Tarif Unitaire HT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $row)
                                <tr>
                                    <td style="font-weight: bold; color: var(--foreground);">{{ $row->ingredient->name }}</td>
                                    <td>
                                        <span class="badge badge-primary">
                                            {{ number_format($row->quantity_ordered, 2, ',', ' ') }} {{ $row->ingredient->unit }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($order->status === 'pending')
                                            <input type="number" step="0.01" name="received[{{ $row->id }}]" value="{{ $row->quantity_ordered }}" required class="form-control" style="width: 100px; padding: 5px; margin: 0;">
                                        @elseif($order->status === 'cancelled')
                                            <span class="badge badge-danger">Non Livré (0,00)</span>
                                        @else
                                            <span class="badge badge-success">
                                                {{ number_format($row->quantity_received, 2, ',', ' ') }} {{ $row->ingredient->unit }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->status === 'pending')
                                            <input type="number" step="0.01" name="prices[{{ $row->id }}]" value="{{ $row->unit_price }}" required class="form-control" style="width: 100px; padding: 5px; margin: 0;">
                                        @else
                                            <strong>{{ number_format($row->unit_price, 2, ',', ' ') }} €</strong>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Bill Meta & Camera Upload -->
        <div class="card" style="height: fit-content;">
            <div class="card-header">
                <h3 class="card-title">📄 Facturation & Archive</h3>
            </div>
            <div class="card-body">
                @if($order->status === 'pending')
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        
                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">Numéro de Facture (Fournisseur)</label>
                            <input type="text" name="invoice_number" placeholder="Ex: FACT-2026-99" required class="form-control">
                        </div>

                        <!-- Notes/Comments Textarea inside the main form -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">📝 Notes / Remarques (Optionnel)</label>
                            <textarea name="notes" placeholder="Ex: 1kg de frites abîmé, ou retard de livraison..." class="form-control" style="height: 80px; width: 100%;"></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">📸 Prendre en Photo la Facture / Ticket</label>
                            <input type="file" name="invoice_photo" accept="image/*" capture="environment" required class="form-control" style="background: none; border: none; padding: 0;">
                        </div>

                        <!-- Button 1: Validate -->
                        <button type="submit" class="btn btn-primary" style="font-weight: bold; padding: 12px; background-color: var(--success); border-color: var(--success); width: 100%;">
                            📥 Valider la Réception & Incrémenter le Stock
                        </button>
                        
                        <!-- 🚀 Button 2: Cancel (Triggers our beautiful custom modal!) -->
                        <button type="button" onclick="openCancelModal()" class="btn btn-danger" style="font-weight: bold; padding: 12px; width: 100%; color: white; background-color: var(--destructive); border-color: var(--destructive);">
                            ❌ Annuler la Livraison (Produits non-conformes)
                        </button>
                    </div>

                @elseif($order->status === 'cancelled')
                    <!-- 2. CANCELLED VIEW -->
                    <div style="display: flex; flex-direction: column; gap: 15px; text-align: center; padding: 20px 10px;">
                        <div style="font-size: 40px; margin-bottom: 10px;">❌</div>
                        <h3 style="font-size: 18px; font-weight: bold; color: var(--destructive); margin: 0;">Livraison Refusée</h3>
                        
                        @if($order->notes)
                            <div style="background: var(--muted); padding: 12px; border-radius: 6px; text-align: left; margin-top: 15px; border-left: 3px solid var(--destructive);">
                                <span style="font-size: 11px; color: var(--muted-foreground); text-transform: uppercase; font-weight: bold;">Motif de l'annulation :</span>
                                <p style="font-size: 13px; color: var(--foreground); margin-top: 5px; font-style: italic;">"{{ $order->notes }}"</p>
                            </div>
                        @endif

                        <p style="font-size: 12px; color: var(--muted-foreground); line-height: 18px; margin-top: 10px;">
                            Aucun ingrédient n'a été enregistré en stock, et aucune dépense n'a été imputée à votre comptabilité.
                        </p>
                    </div>

                @else
                    <!-- 3. RECEIVED VIEW -->
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div>
                            <span style="font-size: 12px; color: var(--muted-foreground); text-transform: uppercase;">Facture Enregistrée :</span>
                            <p style="font-size: 18px; font-weight: bold; color: var(--foreground); margin-top: 3px;">{{ $order->invoice_number }}</p>
                        </div>
                        <div>
                            <span style="font-size: 12px; color: var(--muted-foreground); text-transform: uppercase;">Coût Total de l'Approvisionnement :</span>
                            <p style="font-size: 22px; font-weight: bold; color: var(--primary); margin-top: 3px;">{{ number_format($order->total_cost, 2, ',', ' ') }} €</p>
                            <p style="font-size: 11px; color: var(--muted-foreground); margin-top: 3px;">
                                Livré le : {{ $order->received_at ? $order->received_at->format('d/m/Y H:i') : 'N/A' }}
                            </p>
                        </div>

                        @if($order->notes)
                            <div style="background: var(--muted); padding: 12px; border-radius: 6px; margin-top: 10px; border-left: 3px solid var(--primary);">
                                <span style="font-size: 11px; color: var(--muted-foreground); text-transform: uppercase; font-weight: bold;">Remarques de Livraison :</span>
                                <p style="font-size: 13px; color: var(--foreground); margin-top: 5px; font-style: italic;">"{{ $order->notes }}"</p>
                            </div>
                        @endif

                        @if($order->invoice_photo_path)
                            <div style="margin-top: 15px; border-top: 1px solid var(--border); padding-top: 15px;">
                                <span style="font-size: 12px; color: var(--muted-foreground); text-transform: uppercase; display: block; margin-bottom: 10px;">📷 Aperçu de la Facture :</span>
                                <a href="{{ asset('storage/' . $order->invoice_photo_path) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $order->invoice_photo_path) }}" alt="Facture" style="width: 100%; border-radius: 8px; border: 1px solid var(--border); max-height: 250px; object-fit: cover;">
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

    </div>
</form>

<!-- 🚀 THE BYPASS: Pure, non-nested, standards-compliant HTML5 cancel form at the bottom -->
<form id="cancel-form" action="{{ route('admin.purchases.cancel', $order->id) }}" method="POST">
    @csrf
    <input type="hidden" name="notes" id="cancel-reason-input">
</form>

<!-- 🚀 NEW: BEAUTIFUL MODAL POPUP COMPONENT (Uses Tyro's native classes!) -->
<div id="pos-cancel-modal" class="pos-modal-overlay">
    <div class="pos-modal-card card">
        <div class="card-header">
            <h3 class="card-title">⚠️ Motif du Refus de la Livraison</h3>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; gap: 15px;">
            <div class="form-group">
                <label for="modal-notes-input" class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">Veuillez expliquer pourquoi vous refusez cette livraison :</label>
                <!-- Nice, spacious rich-styled textarea -->
                <textarea id="modal-notes-input" class="form-control" style="height: 120px; width: 100%;" placeholder="Ex: Livreur en retard de 4h, les pains burger étaient tous rassis ou écrasés..."></textarea>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 15px;">
                <button type="button" class="btn btn-ghost" onclick="closeCancelModal()">Annuler</button>
                <button type="button" class="btn btn-danger" style="background-color: var(--destructive); border-color: var(--destructive); color: white;" onclick="submitCancellationWithModal()">
                    Confirmer l'Annulation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 🚀 JavaScript to handle the modal cleanly -->
<script>
const modal = document.getElementById('pos-cancel-modal');
const modalTextarea = document.getElementById('modal-notes-input');
const hiddenInput = document.getElementById('cancel-reason-input');
const cancelForm = document.getElementById('cancel-form');

function openCancelModal() {
    // Show the modal cleanly
    modal.classList.add('active');
    modalTextarea.focus();
}

function closeCancelModal() {
    // Hide the modal cleanly and reset the text
    modal.classList.remove('active');
    modalTextarea.value = '';
}

function submitCancellationWithModal() {
    const reason = modalTextarea.value;

    if (!reason || reason.trim() === '') {
        alert("Vous devez saisir un motif pour annuler la livraison.");
        return;
    }

    // Write the custom textarea value to the hidden input and submit
    hiddenInput.value = reason;
    cancelForm.submit();
}
</script>
@endsection