@extends('layouts.app')

@section('title', 'Anotar un gasto | Gastos Hormiga')

@section('content')
    @php
        $diario = 30;
        $anual = $diario * 365;
    @endphp

    <div class="pagina-form">
        <div class="pagina-form__principal">
            <a class="volver" href="{{ route('gastos.index') }}">
                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false">
                    <path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                Mis gastos
            </a>

            <h1 class="titulo-pagina">Anotar un gasto</h1>
            <p class="bajada">Anota lo de hoy antes de que se te olvide. Los gastos chicos no avisan.</p>

            @include('gastos.partials.form')
        </div>

        <aside class="pagina-form__lado" aria-labelledby="cuenta-larga">
            <div class="nota-lateral">
                <h2 class="nota-lateral__titulo" id="cuenta-larga">La cuenta larga</h2>
                <p class="nota-lateral__texto">
                    Si repites <strong>${{ $diario }}</strong> todos los días, en un año son
                    <strong>${{ number_format($anual) }}</strong>. Ninguno de esos gastos se sintió
                    grande cuando lo pagaste.
                </p>
                <div class="nota-lateral__rastro" aria-hidden="true">
                    <span style="--peso: 1.1"></span>
                    <span style="--peso: 2.4"></span>
                    <span style="--peso: 0.8"></span>
                    <span style="--peso: 3.2"></span>
                    <span style="--peso: 1.7"></span>
                    <span style="--peso: 0.6"></span>
                    <span style="--peso: 2.9"></span>
                </div>
            </div>
        </aside>
    </div>
@endsection
