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
        Schema::create('input_lokasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50);
            $table->integer('tinggi_tower');
            $table->text('alamat');
            $table->string('koordinat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('input_lokasi');
    }
};
