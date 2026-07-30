<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class AuthController extends BaseController
{
    public function login(): string|RedirectResponse
    {
        if (session()->get('auth_user_id')) {
            return redirect()->to(route_to('dashboard'));
        }

        return view('auth/login', ['title' => 'Iniciar sesión']);
    }

    public function attempt(): RedirectResponse
    {
        $rules = [
            'email' => 'required|valid_email|max_length[190]',
            'password' => 'required|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model = new UserModel();
        $user = $model->findActiveByEmail((string) $this->request->getPost('email'));

        if ($user === null || ! password_verify((string) $this->request->getPost('password'), $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Credenciales incorrectas.');
        }

        session()->regenerate(true);
        session()->set([
            'auth_user_id' => (int) $user['id'],
            'auth_user_name' => $user['name'],
            'auth_user_email' => $user['email'],
            'is_logged_in' => true,
        ]);

        $model->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        return redirect()->to(route_to('dashboard'))->with('success', 'Bienvenido a ERP TraceOPX.');
    }

    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to(route_to('login'));
    }
}
