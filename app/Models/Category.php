<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasCrud;

 
    protected $fillable = [
        'name',
    ];

   

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class);
    }
}
