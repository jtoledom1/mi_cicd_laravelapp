<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla donde se guardan los gastos hormiga.
     */
    public function up(): void
    {
        Schema::create('gastos_hormiga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('descripcion', 120);
            $table->decimal('monto', 10, 2);
            $table->string('categoria', 40);
            $table->date('fecha')->index();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de gastos hormiga.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos_hormiga');
    }
};
