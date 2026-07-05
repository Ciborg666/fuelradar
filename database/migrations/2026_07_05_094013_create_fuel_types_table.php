<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // АИ-95
            $table->string('short_name'); // 95
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_types');
    }
};