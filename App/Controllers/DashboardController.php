<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Contac;
use Cronos\Http\Controller;
use App\Middlewares\AuthMiddleware;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(AuthMiddleware::class);
    }

    public function index()
    {
        $users = User::all();

        return view('dashboard', ['users' => $users]);
    }

    public function user(User $user)
    {
        return view('user', ['user' => $user]);
    }

    public function name(User $user)
    {
        return view('user', ['user' => $user]);
    }

    public function pruebas(User $user, Contac $contac)
    {
        // $user = User::find($user);
        // $contac = Contac::find($contac);

        dd($contac);
    }
}
