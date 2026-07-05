<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->enum('status', ['has_fuel', 'queue', 'low_fuel', 'no_fuel']);
            $table->json('fuel_types');
            $table->string('queue_size')->nullable();
            $table->integer('confidence_score')->default(0);
            $table->integer('verified_count')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index('expires_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};