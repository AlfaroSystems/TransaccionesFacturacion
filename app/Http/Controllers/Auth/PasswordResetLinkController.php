<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // El enlace solo llega si la cuenta existe, pero la respuesta es la misma en todos los
        // casos (sin cuenta, o con un enlace pedido hace poco): si no, el formulario serviría
        // para averiguar qué correos están registrados
        Password::sendResetLink($request->only('email'));

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}