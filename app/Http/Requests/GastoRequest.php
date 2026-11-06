<?php

namespace App\Http\Requests;

use App\Models\GastoHormiga;
use Illuminate\Foundation\Http\FormRequest;

class GastoRequest extends FormRequest
{
    /**
     * Determina si el usuario puede hacer esta petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para registrar o actualizar un gasto hormiga.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'descripcion' => ['required', 'string', 'max:120'],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'categoria' => ['required', 'in:'.implode(',', GastoHormiga::CATEGORIAS)],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'notas' => ['nullable', 'string', 'max:500'],
            'cuenta_id' => ['nullable', 'integer'],
            'ambito' => ['nullable', 'in:personal,familia'],
        ];
    }

    /**
     * Mensajes de validación en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'descripcion.required' => 'Escribe qué gastaste.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no puede pasar de 120 caracteres.',
            'monto.required' => 'Anota cuánto gastaste.',
            'monto.numeric' => 'El monto debe ser un número, por ejemplo 45.50.',
            'monto.min' => 'El monto mínimo es de $0.01.',
            'monto.max' => 'El monto máximo es de $99,999.99.',
            'categoria.required' => 'Elige una categoría.',
            'categoria.in' => 'Esa categoría no existe, elige una de la lista.',
            'fecha.required' => 'Indica la fecha del gasto.',
            'fecha.date' => 'La fecha no es válida.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'notas.string' => 'Las notas deben ser texto.',
            'notas.max' => 'Las notas no pueden pasar de 500 caracteres.',
        ];
    }

    /**
     * Normaliza los datos antes de validarlos: limpia el monto y, si el campo
     * de fecha viene vacío, lo llena con el día de hoy.
     */
    protected function prepareForValidation(): void
    {
        $datos = [];

        $monto = $this->input('monto');

        if (is_string($monto)) {
            $montoLimpio = str_replace(['$', ',', ' '], '', trim($monto));
            $datos['monto'] = $montoLimpio === '' ? null : $montoLimpio;
        }

        // Solo se rellena la fecha cuando el campo existe pero viene vacío
        // (por ejemplo, si el usuario borra la fecha del formulario). Si el
        // campo no viene en la petición, la regla "required" debe saltar.
        if ($this->has('fecha') && blank($this->input('fecha'))) {
            $datos['fecha'] = today()->toDateString();
        }

        if ($datos !== []) {
            $this->merge($datos);
        }
    }
}
