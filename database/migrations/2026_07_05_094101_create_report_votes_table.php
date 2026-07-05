<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('ip_hash', 64);
            $table->enum('vote', ['up', 'down']);
            $table->timestamps();
            
            $table->unique(['report_id', 'ip_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_votes');
    }
};
