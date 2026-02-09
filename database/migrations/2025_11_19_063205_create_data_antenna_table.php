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
        Schema::create('data_antenna', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('nama_pelanggan_id')
            //     ->constrained('pelanggan')
            //     ->cascadeOnDelete();
            $table->string('jenis_antenna', 50);
            $table->unsignedBigInteger('id_nama_tower');
            $table->foreign('id_nama_tower')->references('id')->on('data_tower')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('link_tower');
            $table->foreign('link_tower')->references('id')->on('data_tower')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_antenna');
    }
};
