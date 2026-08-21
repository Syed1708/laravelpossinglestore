<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OptionGroup;
use App\Models\Option;
use App\Models\Product;
use App\Models\Category;

class KioskOptionSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. CREATE OPTION GROUPS (WIZARD STEPS)
        // ==========================================

        // Step Group 1: Cheese Sauce (Tacos Step)
        $cheeseSauceGroup = OptionGroup::firstOrCreate(['name' => 'Cheese Sauce (Sauce Fromagère)'], [
            'selection_type'    => 'single_select',
            'is_required'       => true,
            'min_selections'    => 1,
            'max_selections'    => 1,
            'free_choice_limit' => 1,
        ]);

        Option::firstOrCreate(['option_group_id' => $cheeseSauceGroup->id, 'name' => 'With Cheese Sauce (Included)'], ['extra_price' => 0.00]);
        Option::firstOrCreate(['option_group_id' => $cheeseSauceGroup->id, 'name' => 'Without Cheese Sauce'], ['extra_price' => 0.00]);


        // Step Group 2: French Fries Placement (Tacos Step)
        $friesGroup = OptionGroup::firstOrCreate(['name' => 'French Fries Option'], [
            'selection_type'    => 'single_select',
            'is_required'       => true,
            'min_selections'    => 1,
            'max_selections'    => 1,
            'free_choice_limit' => 1,
        ]);

        Option::firstOrCreate(['option_group_id' => $friesGroup->id, 'name' => 'Fries Inside Tacos (Standard)'], ['extra_price' => 0.00]);
        Option::firstOrCreate(['option_group_id' => $friesGroup->id, 'name' => 'Fries On the Side'], ['extra_price' => 0.50]);
        Option::firstOrCreate(['option_group_id' => $friesGroup->id, 'name' => 'No Fries'], ['extra_price' => 0.00]);


        // Step Group 3: Choice of Meats / Viandes (Tacos Step)
        $meatsGroup = OptionGroup::firstOrCreate(['name' => 'Choice of Meats (Viandes)'], [
            'selection_type'    => 'multi_select',
            'is_required'       => true,
            'min_selections'    => 1,
            'max_selections'    => 4,
            'free_choice_limit' => 1, // Default 1 free meat
        ]);

        Option::firstOrCreate(['option_group_id' => $meatsGroup->id, 'name' => 'Ground Beef (Bœuf)'], ['extra_price' => 1.50]);
        Option::firstOrCreate(['option_group_id' => $meatsGroup->id, 'name' => 'Chicken Fillet (Poulet)'], ['extra_price' => 1.50]);
        Option::firstOrCreate(['option_group_id' => $meatsGroup->id, 'name' => 'Cordon Bleu'], ['extra_price' => 1.50]);
        Option::firstOrCreate(['option_group_id' => $meatsGroup->id, 'name' => 'Chicken Tenders'], ['extra_price' => 1.50]);
        Option::firstOrCreate(['option_group_id' => $meatsGroup->id, 'name' => 'Merguez Sausage'], ['extra_price' => 1.50]);


        // Step Group 4: Sauces Selection
        $saucesGroup = OptionGroup::firstOrCreate(['name' => 'Select Sauces'], [
            'selection_type'    => 'multi_select',
            'is_required'       => false,
            'min_selections'    => 0,
            'max_selections'    => 3,
            'free_choice_limit' => 2, // 2 free sauces included
        ]);

        Option::firstOrCreate(['option_group_id' => $saucesGroup->id, 'name' => 'Algérienne'], ['extra_price' => 0.00]);
        Option::firstOrCreate(['option_group_id' => $saucesGroup->id, 'name' => 'Samurai (Spicy)'], ['extra_price' => 0.00]);
        Option::firstOrCreate(['option_group_id' => $saucesGroup->id, 'name' => 'Barbecue (BBQ)'], ['extra_price' => 0.00]);
        Option::firstOrCreate(['option_group_id' => $saucesGroup->id, 'name' => 'Mayonnaise'], ['extra_price' => 0.00]);
        Option::firstOrCreate(['option_group_id' => $saucesGroup->id, 'name' => 'Ketchup'], ['extra_price' => 0.00]);
        Option::firstOrCreate(['option_group_id' => $saucesGroup->id, 'name' => 'Truffle Mayo'], ['extra_price' => 0.80]);


        // Step Group 5: Extra Toppings / Suppléments
        $toppingsGroup = OptionGroup::firstOrCreate(['name' => 'Extra Toppings (Suppléments)'], [
            'selection_type'    => 'multi_select',
            'is_required'       => false,
            'min_selections'    => 0,
            'max_selections'    => 5,
            'free_choice_limit' => 0, // All extras are paid
        ]);

        Option::firstOrCreate(['option_group_id' => $toppingsGroup->id, 'name' => 'Extra Cheddar Cheese'], ['extra_price' => 1.00]);
        Option::firstOrCreate(['option_group_id' => $toppingsGroup->id, 'name' => 'Crispy Bacon Slices'], ['extra_price' => 1.50]);
        Option::firstOrCreate(['option_group_id' => $toppingsGroup->id, 'name' => 'Melted Raclette Cheese'], ['extra_price' => 1.50]);
        Option::firstOrCreate(['option_group_id' => $toppingsGroup->id, 'name' => 'Mozzarella Sticks'], ['extra_price' => 1.50]);
        Option::firstOrCreate(['option_group_id' => $toppingsGroup->id, 'name' => 'Jalapeños'], ['extra_price' => 0.80]);


        // Step Group 6: Pizza Crust Type (Pizza Step)
        $crustGroup = OptionGroup::firstOrCreate(['name' => 'Pizza Crust Type'], [
            'selection_type'    => 'single_select',
            'is_required'       => true,
            'min_selections'    => 1,
            'max_selections'    => 1,
            'free_choice_limit' => 1,
        ]);

        Option::firstOrCreate(['option_group_id' => $crustGroup->id, 'name' => 'Classic Thin Crust'], ['extra_price' => 0.00]);
        Option::firstOrCreate(['option_group_id' => $crustGroup->id, 'name' => 'Cheese Stuffed Crust'], ['extra_price' => 2.00]);
        Option::firstOrCreate(['option_group_id' => $crustGroup->id, 'name' => 'Pan Deep Dish Crust'], ['extra_price' => 1.50]);


        // Step Group 7: Pizza Base Sauce (Pizza Step)
        $pizzaBaseGroup = OptionGroup::firstOrCreate(['name' => 'Pizza Base Sauce'], [
            'selection_type'    => 'single_select',
            'is_required'       => true,
            'min_selections'    => 1,
            'max_selections'    => 1,
            'free_choice_limit' => 1,
        ]);

        Option::firstOrCreate(['option_group_id' => $pizzaBaseGroup->id, 'name' => 'Italian Tomato Sauce Base'], ['extra_price' => 0.00]);
        Option::firstOrCreate(['option_group_id' => $pizzaBaseGroup->id, 'name' => 'Crème Fraîche Base'], ['extra_price' => 0.00]);


        // Step Group 8: Cooking Preference (Burger Step)
        $cookingGroup = OptionGroup::firstOrCreate(['name' => 'Cooking Preference'], [
            'selection_type'    => 'single_select',
            'is_required'       => true,
            'min_selections'    => 1,
            'max_selections'    => 1,
            'free_choice_limit' => 1,
        ]);

        Option::firstOrCreate(['option_group_id' => $cookingGroup->id, 'name' => 'Medium (À Point)'], ['extra_price' => 0.00]);
        Option::firstOrCreate(['option_group_id' => $cookingGroup->id, 'name' => 'Rare (Saignant)'], ['extra_price' => 0.00]);
        Option::firstOrCreate(['option_group_id' => $cookingGroup->id, 'name' => 'Well Done (Bien Cuit)'], ['extra_price' => 0.00]);


        // ==========================================
        // 2. CREATE SAMPLE PRODUCTS & ATTACH STEPS
        // ==========================================

        $tacosCat  = Category::firstOrCreate(['name' => 'French Tacos']);
        $burgerCat = Category::firstOrCreate(['name' => 'Burgers']);
        $pizzaCat  = Category::firstOrCreate(['name' => 'Pizzas']);

        // A. TACOS M (Step sequence + 1 Free Meat Override)
        $tacosM = Product::firstOrCreate(['name' => 'French Tacos M'], [
            'category_id' => $tacosCat->id,
            'price'       => 7.50,
            'vat_rate'    => 10.00,
            'description' => 'Classic French Tacos with 1 meat choice, fries, and signature cheese sauce.',
            'is_active'   => true,
        ]);

        $tacosM->optionGroups()->syncWithoutDetaching([
            $cheeseSauceGroup->id => ['step_order' => 1, 'free_choice_limit_override' => 1],
            $friesGroup->id       => ['step_order' => 2, 'free_choice_limit_override' => 1],
            $meatsGroup->id       => ['step_order' => 3, 'free_choice_limit_override' => 1], // 🚀 1 Free Meat!
            $saucesGroup->id      => ['step_order' => 4, 'free_choice_limit_override' => 2], // 🚀 2 Free Sauces!
            $toppingsGroup->id    => ['step_order' => 5, 'free_choice_limit_override' => 0],
        ]);

        // B. TACOS XL (Step sequence + 3 Free Meats Override)
        $tacosXL = Product::firstOrCreate(['name' => 'French Tacos XL'], [
            'category_id' => $tacosCat->id,
            'price'       => 12.50,
            'vat_rate'    => 10.00,
            'description' => 'Giant French Tacos with 3 meat choices, fries, and signature cheese sauce.',
            'is_active'   => true,
        ]);

        $tacosXL->optionGroups()->syncWithoutDetaching([
            $cheeseSauceGroup->id => ['step_order' => 1, 'free_choice_limit_override' => 1],
            $friesGroup->id       => ['step_order' => 2, 'free_choice_limit_override' => 1],
            $meatsGroup->id       => ['step_order' => 3, 'free_choice_limit_override' => 3], // 🚀 3 Free Meats!
            $saucesGroup->id      => ['step_order' => 4, 'free_choice_limit_override' => 2],
            $toppingsGroup->id    => ['step_order' => 5, 'free_choice_limit_override' => 0],
        ]);

        // C. GOURMET BURGER (3 Steps)
        $burger = Product::firstOrCreate(['name' => 'Palace Bacon Burger'], [
            'category_id' => $burgerCat->id,
            'price'       => 11.50,
            'vat_rate'    => 10.00,
            'description' => 'Double beef patty, aged cheddar, crispy bacon, and house sauce.',
            'is_active'   => true,
        ]);

        $burger->optionGroups()->syncWithoutDetaching([
            $cookingGroup->id  => ['step_order' => 1, 'free_choice_limit_override' => 1],
            $saucesGroup->id   => ['step_order' => 2, 'free_choice_limit_override' => 1],
            $toppingsGroup->id => ['step_order' => 3, 'free_choice_limit_override' => 0],
        ]);

        // D. PIZZA (3 Steps)
        $pizza = Product::firstOrCreate(['name' => '4-Cheese Artisanal Pizza'], [
            'category_id' => $pizzaCat->id,
            'price'       => 12.00,
            'vat_rate'    => 10.00,
            'description' => 'Mozzarella, Gorgonzola, Parmesan, and Chevre on stone-baked crust.',
            'is_active'   => true,
        ]);

        $pizza->optionGroups()->syncWithoutDetaching([
            $crustGroup->id     => ['step_order' => 1, 'free_choice_limit_override' => 1],
            $pizzaBaseGroup->id => ['step_order' => 2, 'free_choice_limit_override' => 1],
            $toppingsGroup->id  => ['step_order' => 3, 'free_choice_limit_override' => 0],
        ]);
    }
}