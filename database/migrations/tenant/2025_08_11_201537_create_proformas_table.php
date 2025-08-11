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
        Schema::create('proformas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // El cajero que la creó
            $table->foreignId('member_id')->nullable()->constrained('members'); // El cliente
            $table->string('number')->unique(); // Un número único para la proforma
            $table->decimal('total_price', 15, 2);
            $table->enum('status', ['pending', 'converted', 'cancelled'])->default('pending'); // Estado de la proforma
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proformas');
    }
};
