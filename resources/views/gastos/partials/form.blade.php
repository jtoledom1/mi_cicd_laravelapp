@php
    $esEdicion = (bool) ($gasto->exists ?? false);

    $fechaValor = old('fecha');

    if ($fechaValor === null) {
        $fechaValor = $gasto->fecha instanceof \DateTimeInterface
            ? $gasto->fecha->format('Y-m-d')
            : (is_string($gasto->fecha) && $gasto->fecha !== '' ? $gasto->fecha : now()->format('Y-m-d'));
    }

    $categoriaActual = old('categoria', $gasto->categoria);
@endphp

<form class="form-gasto" method="POST" action="{{ $esEdicion ? route('gastos.update', $gasto) : route('gastos.store') }}">
    @csrf
    @if ($esEdicion)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="errores" role="alert">
            <p class="errores__titulo">
                @if ($errors->count() === 1)
                    Revisa este campo antes de guardar.
                @else
                    Revisa estos {{ $errors->count() }} campos antes de guardar.
                @endif
            </p>
            <ul class="errores__lista">
                @foreach ($errors->all() as $mensaje)
                    <li>{{ $mensaje }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="campo @error('descripcion') campo--mal @enderror">
        <label class="campo__etiqueta" for="descripcion">¿Qué gastaste?</label>
        <input
            class="campo__control"
            id="descripcion"
            name="descripcion"
            type="text"
            maxlength="120"
            value="{{ old('descripcion', $gasto->descripcion) }}"
            placeholder="Café de la esquina"
            required
            autocomplete="off"
            @error('descripcion') aria-invalid="true" aria-describedby="descripcion-error" @enderror
        >
        @error('descripcion')
            <p class="campo__error" id="descripcion-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-gasto__rejilla">
        <div class="campo @error('monto') campo--mal @enderror">
            <label class="campo__etiqueta" for="monto">Monto</label>
            <div class="campo__dinero">
                <span class="campo__signo" aria-hidden="true">$</span>
                <input
                    class="campo__control campo__control--dinero"
                    id="monto"
                    name="monto"
                    type="number"
                    step="0.01"
                    min="0.01"
                    value="{{ old('monto', $gasto->monto) }}"
                    placeholder="45.00"
                    inputmode="decimal"
                    required
                    @error('monto') aria-invalid="true" aria-describedby="monto-error" @enderror
                >
                <span class="campo__unidad">MXN</span>
            </div>
            @error('monto')
                <p class="campo__error" id="monto-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo @error('fecha') campo--mal @enderror">
            <label class="campo__etiqueta" for="fecha">Fecha</label>
            <input
                class="campo__control"
                id="fecha"
                name="fecha"
                type="date"
                value="{{ $fechaValor }}"
                required
                @error('fecha') aria-invalid="true" aria-describedby="fecha-error" @enderror
            >
            @error('fecha')
                <p class="campo__error" id="fecha-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="campo @error('categoria') campo--mal @enderror">
        <label class="campo__etiqueta" for="categoria">Categoría</label>
        <select
            class="campo__control campo__control--select"
            id="categoria"
            name="categoria"
            required
            @error('categoria') aria-invalid="true" aria-describedby="categoria-error" @enderror
        >
            <option value="" disabled @selected($categoriaActual === null || $categoriaActual === '')>Elige una categoría</option>
            @foreach ($categorias as $opcion)
                <option value="{{ $opcion }}" @selected((string) $categoriaActual === (string) $opcion)>{{ $opcion }}</option>
            @endforeach
        </select>
        @error('categoria')
            <p class="campo__error" id="categoria-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="campo @error('notas') campo--mal @enderror">
        <label class="campo__etiqueta" for="notas">Nota (opcional)</label>
        <textarea
            class="campo__control campo__control--area"
            id="notas"
            name="notas"
            rows="3"
            maxlength="500"
            @error('notas') aria-invalid="true" aria-describedby="notas-error" @enderror
        >{{ old('notas', $gasto->notas) }}</textarea>
        @error('notas')
            <p class="campo__error" id="notas-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-gasto__acciones">
        <button class="boton boton--lleno" type="submit">{{ $esEdicion ? 'Guardar cambios' : 'Guardar gasto' }}</button>
        <a class="boton boton--fantasma" href="{{ route('gastos.index') }}">Cancelar</a>
    </div>
</form>
