<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metas de ahorro (antes "lukapp_goals_v2") y su historial de abonos/retiros.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 70);
            $table->string('icon', 16)->default('🎯');
            $table->decimal('target', 15, 2);
            $table->decimal('saved', 15, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->boolean('completed_notified')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('goal_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['abonar', 'retirar']);
            $table->decimal('amount', 15, 2);
            $table->string('comment', 50)->nullable();
            $table->timestamps();

            $table->index('goal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_contributions');
        Schema::dropIfExists('goals');
    }
};
