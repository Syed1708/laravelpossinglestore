<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasCrud;

    protected $fillable = ['category_id', 'name', 'price', 'vat_rate', 'is_active'];

        // Ensure floats don't lose precision
    protected $casts = [
        'price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }



    // 2. Advanced Field Overrides (Relationships, Dropdowns & Controls)
    public function resourceFieldOverrides(): array
    {
        return [
            'name' => [
                'type' => 'text',
                'label' => 'Nom du Produit',
                'rules' => 'required|max:255',
            ],

            // 🚀 RELATIONAL DROPDOWN: Links to category() method and displays name
            'category_id' => [
                'type' => 'select',
                'label' => 'Catégorie de Nourriture',
                'relationship' => 'category', // Name of the relationship method above
                'option_label' => 'name',      // Column from Category table to display
                'rules' => 'required',
            ],

            'price' => [
                'type' => 'number',
                'label' => 'Prix de Vente TTC (€)',
                'rules' => 'required|numeric|min:0',
            ],

            // 🚀 FRENCH VAT SELECTOR
            'vat_rate' => [
                'type' => 'select',
                'label' => 'Taux de TVA (Tax)',
                'options' => [
                    '10.00' => '10,0% (Plats Chauds / Sur Place)',
                    '5.50' => '5,5% (Plats Froids / À Emporter / Eaux)',
                    '20.00' => '20,0% (Sodas / Alcools)',
                ],
                'rules' => 'required',
            ],

            'is_active' => [
                'type' => 'boolean',
                'label' => 'Disponible en Caisse (Actif)',
                'default' => true,
            ],
        ];
    }

    // 3. Full Access Roles (Can Create, Edit, and Delete)
    public function resourceRoles(): array
    {
        return ['admin', 'superadmin'];
    }

    // 4. 🚀 READ-ONLY ROLES: Cashiers can see the list, but cannot edit or delete!
    public function resourceReadonlyRoles(): array
    {
        return ['cashier'];
    }
    
}