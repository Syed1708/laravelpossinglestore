<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasCrud;

    protected $fillable = [
        'name',
        'description', // 🚀 ADD THIS
        'allergens',
        'ingredients',
        'dietary_flags',
        'calories',
        'image_path',  // 🚀 ADD THIS
        'category_id',
        'price',
        'vat_rate',
        'is_active'
    ];


    // 🚀 THE FIX: Automatically cast decimal strings to native float numbers in JSON!
    protected $casts = [
        'price' => 'float',     // Converts "8.50" to 8.50
        'vat_rate' => 'float',  // Converts "10.00" to 10.0
        'is_active' => 'boolean',
    ];

    // 🚀 MUTATORS & ACCESSORS HANDLE JSON ENCODING/DECODING
    public function setAllergensAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['allergens'] = json_encode($value);
        } else {
            $array = array_values(array_filter(array_map('trim', explode(',', $value ?? ''))));
            $this->attributes['allergens'] = json_encode($array);
        }
    }

    public function getAllergensAttribute($value)
    {
        if (is_array($value)) {
            return implode(', ', $value);
        }
        $decoded = json_decode($value ?? '[]', true);
        return is_array($decoded) ? implode(', ', $decoded) : ($value ?? '');
    }

    public function setIngredientsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['ingredients'] = json_encode($value);
        } else {
            $array = array_values(array_filter(array_map('trim', explode(',', $value ?? ''))));
            $this->attributes['ingredients'] = json_encode($array);
        }
    }

    public function getIngredientsAttribute($value)
    {
        if (is_array($value)) {
            return implode(', ', $value);
        }
        $decoded = json_decode($value ?? '[]', true);
        return is_array($decoded) ? implode(', ', $decoded) : ($value ?? '');
    }

    public function setDietaryFlagsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['dietary_flags'] = json_encode($value);
        } else {
            $array = array_values(array_filter(array_map('trim', explode(',', $value ?? ''))));
            $this->attributes['dietary_flags'] = json_encode($array);
        }
    }

    public function getDietaryFlagsAttribute($value)
    {
        if (is_array($value)) {
            return implode(', ', $value);
        }
        $decoded = json_decode($value ?? '[]', true);
        return is_array($decoded) ? implode(', ', $decoded) : ($value ?? '');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 🚀 RECIPES RELATIONSHIP (Fixes "Call to undefined relationship [recipes]")
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    /**
     * Direct Ingredients relationship via recipes
     */
    public function recipeIngredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipes')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * 🚀 Dynamic Step-by-Step Customization Option Groups for Kiosk & Web
     * Returns option groups sorted in exact step sequence (Step 1, 2, 3...)
     */
    public function optionGroups(): BelongsToMany
    {
        return $this->belongsToMany(OptionGroup::class, 'product_option_group')
            ->withPivot('step_order', 'free_choice_limit_override')
            ->orderBy('product_option_group.step_order', 'asc')
            ->withTimestamps();
    }
}
