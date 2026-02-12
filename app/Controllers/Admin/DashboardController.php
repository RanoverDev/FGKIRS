<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Helpers\Auth;

/**
 * DashboardController - Admin dashboard routing
 */
class DashboardController extends Controller
{
    /**
     * Main dashboard - redirects based on role
     */
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        // Redirect based on role
        if (Auth::isAdmin()) {
            $this->redirect('/fgkirs-admin/dashboard/president');
        } elseif (Auth::isSensei()) {
            $this->redirect('/fgkirs-admin/dashboard/sensei');
        } else {
            // Student/Colaborador - show basic dashboard
            $this->view('admin/dashboard');
        }
    }

    /**
     * President Dashboard
     */
    public function president(): void
    {
        if (!Auth::isAdmin()) {
            $this->redirect('/fgkirs-admin');
        }
        $this->view('admin/dashboard_president');
    }

    /**
     * Sensei Dashboard
     */
    public function sensei(): void
    {
        if (!Auth::isSensei() && !Auth::isAdmin()) {
            $this->redirect('/fgkirs-admin');
        }
        $this->view('admin/dashboard_sensei');
    }
}
