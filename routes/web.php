<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FamiliaController;
use App\Http\Controllers\GastoHormigaController;
use App\Http\Controllers\ResumenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Entrada y registro
|--------------------------------------------------------------------------
|
| Solo para quien todavía no tiene sesión. Si ya entró, el middleware
| "guest" lo manda directo a sus gastos.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Zona privada
|--------------------------------------------------------------------------
|
| Los gastos se pueden ver en dos ámbitos: personales (por defecto) o los
| del hogar, agregando ?ambito=familia.
|
*/

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // La raíz manda al listado de gastos hormiga.
    Route::get('/', fn () => redirect()->route('gastos.index'));

    // CRUD de gastos. El parámetro de ruta se llama "gasto".
    Route::resource('gastos', GastoHormigaController::class)
        ->parameters(['gastos' => 'gasto']);

    // El hogar: crear, unirse con código, salir o eliminarlo.
    Route::get('familia', [FamiliaController::class, 'index'])->name('familia.index');
    Route::post('familia', [FamiliaController::class, 'store'])->name('familia.store');
    Route::post('familia/unirse', [FamiliaController::class, 'unirse'])->name('familia.unirse');
    Route::delete('familia/salir', [FamiliaController::class, 'salir'])->name('familia.salir');
    Route::delete('familia', [FamiliaController::class, 'destroy'])->name('familia.destroy');
    Route::post('familia/codigo', [FamiliaController::class, 'regenerarCodigo'])->name('familia.codigo');

    // Cuentas: el dinero de la abuelita, el gas, la general...
    Route::get('cuentas', [CuentaController::class, 'index'])->name('cuentas.index');
    Route::post('cuentas', [CuentaController::class, 'store'])->name('cuentas.store');
    Route::patch('cuentas/{cuenta}', [CuentaController::class, 'update'])->name('cuentas.update');
    Route::delete('cuentas/{cuenta}', [CuentaController::class, 'destroy'])->name('cuentas.destroy');

    // Resúmenes: el mes en curso y el año completo.
    Route::get('resumen/mensual', [ResumenController::class, 'mensual'])->name('resumen.mensual');
    Route::get('resumen/anual', [ResumenController::class, 'anual'])->name('resumen.anual');

    // Exportaciones con los mismos filtros del listado.
    Route::get('exportar/pdf', [ExportController::class, 'pdf'])->name('exportar.pdf');
    Route::get('exportar/excel', [ExportController::class, 'excel'])->name('exportar.excel');
});
