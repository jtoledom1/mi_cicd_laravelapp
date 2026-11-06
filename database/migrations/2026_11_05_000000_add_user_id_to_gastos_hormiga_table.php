<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade el dueño a cada gasto para que la app sea multiusuario.
     *
     * En instalaciones nuevas la columna ya viene en la migración de creación
     * (como NOT NULL); en bases que ya existían se agrega aquí y los gastos
     * huérfanos se asignan al primer usuario registrado.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('gastos_hormiga', 'user_id')) {
            Schema::table('gastos_hormiga', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        $primerUsuario = DB::table('users')->orderBy('id')->value('id');

        if ($primerUsuario !== null) {
            DB::table('gastos_hormiga')
                ->whereNull('user_id')
                ->update(['user_id' => $primerUsuario]);
        }
    }

    /**
     * Quita el dueño de los gastos.
     */
    public function down(): void
    {
        if (Schema::hasColumn('gastos_hormiga', 'user_id')) {
            Schema::table('gastos_hormiga', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
