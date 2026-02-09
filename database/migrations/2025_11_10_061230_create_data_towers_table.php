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
        Schema::create('data_tower', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('nama_pelanggan_id')
            //     ->constrained('pelanggan')
            //     ->cascadeOnDelete();
            $table->string('nama_tower', 50);
            $table->string('alamat_tower')->nullable();
            $table->integer('tinggi_tower'); // in meters
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('keterangan', 255)->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_towers');
    }
};
