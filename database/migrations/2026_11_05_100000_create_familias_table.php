<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el hogar (familia) donde se comparten gastos.
     */
    public function up(): void
    {
        Schema::create('familias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->foreignId('propietario_id')->constrained('users')->cascadeOnDelete();
            $table->string('codigo_invitacion', 12)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('familias');
    }
};
