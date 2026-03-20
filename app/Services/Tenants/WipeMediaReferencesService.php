<?php

namespace App\Services\Tenants;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WipeMediaReferencesService
{
    public function handle(): array
    {
        return [
            'abouts.photo' => $this->clearColumn('abouts', 'photo'),
            'profiles.photo' => $this->clearColumn('profiles', 'photo'),
            'products.hero_images' => $this->clearColumn('products', 'hero_images'),
            'purchasings.image' => $this->clearColumn('purchasings', 'image'),
            'stock_opname_items.attachment' => $this->clearColumn('stock_opname_items', 'attachment'),
            'product_images' => $this->deleteAllRows('product_images'),
            'uploaded_files' => $this->deleteAllRows('uploaded_files'),
        ];
    }

    private function clearColumn(string $table, string $column): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->update([$column => null]);
    }

    private function deleteAllRows(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $count = DB::table($table)->count();

        if ($count === 0) {
            return 0;
        }

        DB::table($table)->delete();

        return $count;
    }
}
