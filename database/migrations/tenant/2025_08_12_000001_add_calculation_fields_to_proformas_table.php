<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proformas', function (Blueprint $table) {
            $table->after('total_price', function (Blueprint $table) {
                $table->decimal('tax_price', 15, 2)->default(0);
                $table->decimal('discount_price', 15, 2)->default(0);
                $table->decimal('total_discount_per_item', 15, 2)->default(0);
                $table->decimal('tax', 5, 2)->default(0); // Porcentaje de impuesto
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proformas', function (Blueprint $table) {
            $table->dropColumn(['tax_price', 'discount_price', 'total_discount_per_item', 'tax']);
        });
    }
};