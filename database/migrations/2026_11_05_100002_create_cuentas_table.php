<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuentas con las que se separan los gastos: el dinero de la abuelita,
     * el presupuesto del gas, la cuenta general... Cada usuario crea las suyas
     * y cada familia tiene las propias.
     */
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('familia_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'nombre']);
            $table->index(['familia_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
