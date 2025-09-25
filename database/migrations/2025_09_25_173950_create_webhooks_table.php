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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10)->index(); // POST, PUT, GET, etc - com índice
            $table->text('url'); // URL completa da requisição
            $table->string('ip_address', 45)->index(); // IP de origem - com índice, suporta IPv6
            $table->integer('size')->default(0)->index(); // Tamanho da requisição em bytes - com índice
            $table->json('headers'); // Headers da requisição
            $table->longText('body')->nullable(); // Corpo da requisição
            $table->timestamps();
            
            // Índice composto para consultas por data
            $table->index(['created_at', 'method']);
            $table->index(['created_at', 'ip_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
