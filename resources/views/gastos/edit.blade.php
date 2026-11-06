@extends('layouts.app')

@section('title', $ambito->esFamilia() ? 'Editar un gasto de la familia | Gastos Hormiga' : 'Editar gasto | Gastos Hormiga')

@section('content')
    @php
        $esFamilia = $ambito->esFamilia();
    @endphp

    <div class="pagina-form pagina-form--simple">
        <div class="pagina-form__principal">
            <a class="volver" href="{{ route('gastos.show', $gasto) }}">
                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false">
                    <path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                Volver al gasto
            </a>

            <h1 class="titulo-pagina">{{ $esFamilia ? 'Editar un gasto de la familia' : 'Editar gasto' }}</h1>
            <p class="bajada">
                @if ($esFamilia)
                    Corrige el monto, la fecha o la cuenta. El cambio lo verá todo el hogar ({{ $ambito->nombre() }}).
                @else
                    Corrige el monto, la fecha o lo que haga falta. El rastro se reacomoda solo.
                @endif
            </p>

            @include('gastos.partials.form')
        </div>
    </div>
@endsection
