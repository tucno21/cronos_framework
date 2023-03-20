<?php

namespace App\Controllers;

use App\Models\User;
use Cronos\Http\Request;
use Cronos\Crypto\Hasher;
use Cronos\Http\Controller;
use App\Middlewares\AuthMiddleware;

class RegisterController extends Controller
{
    public function __construct()
    {
        // $this->middleware(AuthMiddleware::class);
    }

    public function index()
    {
        return view('home/register');
    }

    public function create(Request $request, Hasher $hasher)
    {
        $valid = $this->validate($request->all(), [
            'name' => 'required|string|min:3|max:15',
            'email' => 'required|email|unique:User,email',
            'password' => 'required|min:2|max:10|matches:confirm_password',
            'confirm_password' => 'required|matches:password',
        ]);

        if ($valid !== true) {
            return back()->withErrors($request->all(), $valid);
        }

        $data = $request->all();
        $data->password = $hasher->hash($data->password);
        //eliminar repetir_password
        unset($data->confirm_password);

        $user = User::create($data);

        return redirect()->route('login.index')->with('message', "{$user->name} se registro correctamente");
    }
}
