<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Renderiza las vistas Blade (diseño original). Los datos se cargan desde la API REST
 * con los controladores JavaScript existentes.
 */
class PageController extends Controller
{
    public function home(Request $request): RedirectResponse
    {
        $user = $request->user();

        return redirect()->route(! $user ? 'login' : ($user->isAdmin() ? 'admin' : 'dashboard'));
    }

    public function login(): View
    {
        return view('auth.login');
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function resetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function dashboard(): View
    {
        return view('pages.dashboard');
    }

    public function movements(): View
    {
        return view('pages.movements');
    }

    public function statistics(): View
    {
        return view('pages.statistics');
    }

    public function goals(): View
    {
        return view('pages.goals');
    }

    public function settings(): View
    {
        return view('pages.settings');
    }

    public function admin(): View
    {
        return view('pages.admin');
    }
}
