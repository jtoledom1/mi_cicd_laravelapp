@extends('layouts.app')

@section('title', 'Editar gasto | Gastos Hormiga')

@section('content')
    <div class="pagina-form pagina-form--simple">
        <div class="pagina-form__principal">
            <a class="volver" href="{{ route('gastos.show', $gasto) }}">
                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false">
                    <path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                Volver al gasto
            </a>

            <h1 class="titulo-pagina">Editar gasto</h1>
            <p class="bajada">Corrige el monto, la fecha o lo que haga falta. El rastro se reacomoda solo.</p>

            @include('gastos.partials.form')
        </div>
    </div>
@endsection
