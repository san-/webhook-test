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
            $table->string('method'); // POST, PUT, GET, etc
            $table->text('url'); // URL completa da requisição
            $table->string('ip_address'); // IP de origem
            $table->integer('size')->default(0); // Tamanho da requisição em bytes
            $table->json('headers'); // Headers da requisição
            $table->longText('body')->nullable(); // Corpo da requisição
            $table->timestamps();
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
