<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasCrud;

    // protected $resourceRoles = ['admin', 'manager'];
    // protected $readonly = true;
    // The 'editor' role can only read/view this resource, but cannot create/update/destroy it
    // protected $resourceReadonly = ['editor'];
    protected $fillable = [
        'name',
    ];

    // protected $resourceFieldOverrides = [

    //     'name' => [
    //         'type' => 'text',
    //         'label' => 'Category Name',
    //         'rules' => 'required|min:5|max:200|unique:categories,name', // Laravel validation rules
    //         'default' => 'Burgers',         // Default value shown on create screens
    //         'placeholder' => 'Enter category name here',  // Standard placeholder
    //         'help_text' => 'type category name here', // Help text shown below the field
    //         'hide_in_index' => false,             // Hides field specifically on the table view
    //         'hide_in_create' => false,            // Hides on the "create" mode
    //         'hide_in_edit' => false,              // Hides on the "update/edit" mode
    //         'hide_in_form' => false,              // Hides completely across ALL forms (create + edit)
    //         'hide_in_single_view' => false,       // Hide from detail/show view
    //         // 'readonly' => true,                  // Forces HTML `readonly` state 
    //         'searchable' => true,
    //     ],

    

    // ];

       // 2. Field Customization Overrides
    public function resourceFieldOverrides(): array
    {
        return [
            'name' => [
                'type' => 'text',
                'label' => 'Nom de la Catégorie',
                'rules' => 'required|max:255',
            ],
        ];
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class);
    }
}
