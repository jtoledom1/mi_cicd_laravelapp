<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade la cuenta y la familia a cada gasto.
     *
     * Un gasto sin familia es personal; con familia, es compartido. La cuenta
     * es la que separa el dinero: la de la abuelita, la del gas, la general.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('gastos_hormiga', 'familia_id')) {
            Schema::table('gastos_hormiga', function (Blueprint $table) {
                $table->foreignId('familia_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('gastos_hormiga', 'cuenta_id')) {
            Schema::table('gastos_hormiga', function (Blueprint $table) {
                $table->foreignId('cuenta_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        $this->crearCuentasGenerales();
    }

    /**
     * Cada usuario y cada familia existente recibe su cuenta "General", y los
     * gastos que ya había se quedan ahí para no perder el histórico.
     */
    private function crearCuentasGenerales(): void
    {
        $ahora = now();

        foreach (DB::table('users')->pluck('id') as $usuarioId) {
            $existe = DB::table('cuentas')
                ->where('user_id', $usuarioId)
                ->whereNull('familia_id')
                ->exists();

            if (! $existe) {
                DB::table('cuentas')->insert([
                    'nombre' => 'General',
                    'user_id' => $usuarioId,
                    'familia_id' => null,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }

            $general = DB::table('cuentas')
                ->where('user_id', $usuarioId)
                ->whereNull('familia_id')
                ->value('id');

            DB::table('gastos_hormiga')
                ->where('user_id', $usuarioId)
                ->whereNull('cuenta_id')
                ->update(['cuenta_id' => $general]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('gastos_hormiga', 'cuenta_id')) {
            Schema::table('gastos_hormiga', function (Blueprint $table) {
                $table->dropConstrainedForeignId('cuenta_id');
            });
        }

        if (Schema::hasColumn('gastos_hormiga', 'familia_id')) {
            Schema::table('gastos_hormiga', function (Blueprint $table) {
                $table->dropConstrainedForeignId('familia_id');
            });
        }
    }
};
