<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferencias del usuario (relación 1:1 con users).
 * Reemplaza la clave "lukapp_settings_v2" de localStorage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Apariencia
            $table->enum('theme', ['light', 'dark', 'auto'])->default('light');
            $table->enum('currency', ['COP', 'USD', 'EUR'])->default('COP');
            $table->enum('language', ['es', 'en'])->default('es');
            // Notificaciones
            $table->boolean('notif_email')->default(true);
            $table->boolean('notif_push')->default(true);
            $table->boolean('notif_weekly')->default(false);
            $table->boolean('notif_monthly')->default(true);
            $table->boolean('notif_alerts')->default(true);
            $table->boolean('notif_goals')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
