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
        Schema::table('abouts', function (Blueprint $table) {
            if (! Schema::hasColumn('abouts', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (! Schema::hasColumn('abouts', 'facebook')) {
                $table->string('facebook')->nullable();
            }
            if (! Schema::hasColumn('abouts', 'messenger')) {
                $table->string('messenger')->nullable();
            }
            if (! Schema::hasColumn('abouts', 'website')) {
                $table->string('website')->nullable();
            }
            if (! Schema::hasColumn('abouts', 'linkedin')) {
                $table->string('linkedin')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abouts', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('abouts', 'phone')) {
                $columns[] = 'phone';
            }
            if (Schema::hasColumn('abouts', 'facebook')) {
                $columns[] = 'facebook';
            }
            if (Schema::hasColumn('abouts', 'messenger')) {
                $columns[] = 'messenger';
            }
            if (Schema::hasColumn('abouts', 'website')) {
                $columns[] = 'website';
            }
            if (Schema::hasColumn('abouts', 'linkedin')) {
                $columns[] = 'linkedin';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
