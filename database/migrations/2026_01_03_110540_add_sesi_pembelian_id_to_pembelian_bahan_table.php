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
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            $table->foreignId('sesi_pembelian_id')->nullable()->after('id')
                  ->constrained('sesi_pembelian')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            $table->dropForeign(['sesi_pembelian_id']);
            $table->dropColumn('sesi_pembelian_id');
        });
    }
};
