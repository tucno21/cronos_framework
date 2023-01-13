<?php

namespace App\Controllers;

use Cronos\Http\Request;
use Cronos\Http\Controller;

class HomeController extends Controller
{
    public function index()
    {
        //ver las sesiones creadas
        // session()->flash('message', 'Hola mundo');

        $data = [
            'id' => 2,
            'name' => 'Cronos',
            'email' => 'cc@cc',
            'categories' => [
                'php',
                'javascript',
                'css',
                'html',
            ],
        ];

        $data = (object) $data;

        session()->put('user', $data);
        // session()->put('competencia', $data);
        // session()->push('user.profesion', 'programador');
        // session()->forget('user');
        // session()->flush();
        // $sesion = $_SESSION;
        $sesion = session()->user();
        dd($sesion);

        return view('home');
    }

    public function form()
    {
        return view('form');
    }

    public function store(Request $request)
    {
        $valid = $this->validate($request->all(), [
            'name' => 'required|min:3|max:10',
            'email' => 'required|email',
        ]);

        if ($valid !== true) {
            return json($valid, 422);
        }

        return json([
            'message' => 'Datos validados correctamente',
        ]);
    }

    public function user(string $user)
    {
        return json([
            'message' => "user: $user",
        ]);
    }
}
