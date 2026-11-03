<?php

use App\Http\Controllers\GastoHormigaController;
use Illuminate\Support\Facades\Route;

// La raíz manda directo al listado de gastos hormiga.
Route::get('/', fn () => redirect()->route('gastos.index'));

// CRUD completo. El parámetro de ruta se llama "gasto" para el route model binding.
Route::resource('gastos', GastoHormigaController::class)
    ->parameters(['gastos' => 'gasto']);
