@extends('layouts.app')

@section('title', 'Página no encontrada | Gastos Hormiga')

@section('content')
    <div class="pagina-error">
        <p class="pagina-error__codigo" aria-hidden="true">404</p>
        <h1 class="pagina-error__titulo">Perdimos el rastro</h1>
        <p class="pagina-error__texto">
            Esta página no existe. Puede que el gasto se haya borrado o que la dirección esté mal escrita.
        </p>
        <div class="pagina-error__acciones">
            <a class="boton boton--lleno" href="{{ route('gastos.index') }}">Ir a mis gastos</a>
            <a class="boton boton--fantasma" href="{{ route('gastos.create') }}">Anotar un gasto</a>
        </div>
    </div>
@endsection
