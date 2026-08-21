<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OptionGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'selection_type',
        'is_required',
        'min_selections',
        'max_selections',
        'free_choice_limit',
    ];

    protected $casts = [
        'is_required'       => 'boolean',
        'min_selections'    => 'integer',
        'max_selections'    => 'integer',
        'free_choice_limit' => 'integer',
    ];

    /**
     * Active options inside this group
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->where('is_active', true);
    }

    /**
     * Products linked to this option group step
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_option_group')
                    ->withPivot('step_order', 'free_choice_limit_override')
                    ->withTimestamps();
    }
}