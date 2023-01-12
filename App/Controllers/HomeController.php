<?php

namespace App\Controllers;

use Cronos\Http\Request;
use Cronos\Http\Controller;

class HomeController extends Controller
{
    public function index()
    {
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
