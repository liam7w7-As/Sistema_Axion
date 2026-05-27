<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Request de autenticación.
 * Usa el campo 'codigo' en lugar de 'email' para iniciar sesión.
 */
class LoginRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el inicio de sesión.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Intentar autenticar las credenciales de la solicitud.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = \App\Models\User::where('codigo', $this->codigo)->first();

        if ($user && $user->estado === 'deshabilitado') {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'codigo' => 'Su cuenta se encuentra inhabilitada. Contacte al administrador.',
            ]);
        }

        if (! Auth::attempt($this->only('codigo', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'codigo' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Asegurar que la solicitud de inicio de sesión no exceda el límite de intentos.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'codigo' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Obtener la clave de limitación de intentos.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('codigo')).'|'.$this->ip());
    }
}
