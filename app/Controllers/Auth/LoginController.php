<?php

namespace Controllers\Auth;

use Controllers\Controller;
use Helpers\Auth;

/**
 * LoginController - Handle authentication
 */
class LoginController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm(): void
    {
        // If already authenticated, redirect to dashboard
        if (Auth::check()) {
            $this->redirect('/fgkirs-admin');
        }

        $this->view('auth/login');
    }

    /**
     * Handle login attempt
     */
    public function login(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (Auth::login($email, $password)) {
            $this->redirect('/fgkirs-admin');
        } else {
            $_SESSION['error'] = 'Email ou senha inválidos.';
            $this->redirect('/login');
        }
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
