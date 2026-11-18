<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ingresos y gastos de cada usuario (antes "lukapp_movements_v2").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['ingreso', 'gasto']);
            $table->string('description', 80);
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->enum('status', ['completado', 'pendiente', 'cancelado'])->default('completado');
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
