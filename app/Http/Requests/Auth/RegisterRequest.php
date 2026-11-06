<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Cualquiera puede crear su cuenta.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe tu nombre.',
            'name.max' => 'Tu nombre no puede pasar de 80 caracteres.',
            'email.required' => 'Escribe tu correo.',
            'email.email' => 'Ese correo no tiene un formato válido.',
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.required' => 'Elige una contraseña.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    /**
     * Normaliza el correo antes de validarlo.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->input('email')) ? trim($this->input('email')) : $this->input('email'),
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
        ]);
    }
}
