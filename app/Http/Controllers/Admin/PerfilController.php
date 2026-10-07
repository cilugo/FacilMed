<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * Perfil do admin (01/10/2026): foto e senha. O nome e o e-mail do admin
 * vêm do AdminSeeder e não mudam por tela.
 */
class PerfilController extends Controller
{
    public function edit()
    {
        return view('admin.perfil');
    }
}
