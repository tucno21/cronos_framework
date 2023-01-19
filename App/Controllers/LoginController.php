<?php

namespace App\Controllers;

use App\Models\User;
use Cronos\Http\Request;
use Cronos\Http\Controller;
use App\Middlewares\AuthMiddleware;

class LoginController extends Controller
{
    public function __construct()
    {
        // $this->middleware(AuthMiddleware::class);
    }

    public function index()
    {
        return view('login');
    }

    public function store(Request $request)
    {
        $valid = $this->validate($request->all(), [
            'email' => 'required|email|not_unique:User,email',
            'password' => 'required|password_verify:User,email',
        ]);

        if ($valid !== true) {
            return back()->withErrors($request->all(), $valid);
        }

        $user = User::where('email', $request->input('email'))->first();
        unset($user->password);

        session()->attempt($user);

        return redirect()->route('dashboard.index')->with('message', 'Bienvenido');
    }

    public function logout()
    {
        session()->logout();
        return redirect()->route('home.index')->with('message', 'Sesion cerrada');
    }
}
