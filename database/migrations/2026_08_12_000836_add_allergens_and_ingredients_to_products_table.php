<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('allergens')->nullable()->after('description');       // ['gluten', 'dairy', 'nuts', 'eggs', 'soy']
            $table->json('ingredients')->nullable()->after('allergens');       // ['Beef Patty', 'Brioche Bun', 'Aged Cheddar']
            $table->json('dietary_flags')->nullable()->after('ingredients');   // ['halal', 'vegetarian', 'spicy']
            $table->string('calories')->nullable()->after('dietary_flags');    // e.g. "650 kcal"
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['allergens', 'ingredients', 'dietary_flags', 'calories']);
        });
    }
};