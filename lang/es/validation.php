<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Líneas de validación
    |--------------------------------------------------------------------------
    |
    | Mensajes en español para las reglas que usa la aplicación. Solo están
    | traducidas las reglas presentes en GastoRequest; el resto cae al inglés
    | del framework, que no se usa en este CRUD.
    |
    */

    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'array' => 'El campo :attribute debe ser una lista.',
    'date' => 'El campo :attribute debe ser una fecha válida.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'unique' => 'El valor de :attribute ya está registrado.',
    'exists' => 'El valor de :attribute no existe.',

    'in' => 'El valor de :attribute no es una opción válida.',
    'not_in' => 'El valor de :attribute no está permitido.',

    'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a :date.',

    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
    ],

    'max' => [
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
    ],

    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Nombres legibles de los campos
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'descripcion' => 'descripción',
        'monto' => 'monto',
        'categoria' => 'categoría',
        'fecha' => 'fecha',
        'notas' => 'nota',
    ],

    'custom' => [],

];
