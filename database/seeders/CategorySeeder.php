<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Plomería',
            'Electricidad',
            'Limpieza',
            'Carpintería',
            'Pintura',
            'Cerrajería',
            'Jardinería',
            'Mecánica a domicilio',
            'Reparación de electrodomésticos',
            'Mudanzas',
        ];

        foreach($categories as $name){
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]);
        }
    }
}
