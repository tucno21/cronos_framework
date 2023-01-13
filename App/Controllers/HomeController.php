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
        // dd($_SESSION);
        return view('form');
    }

    public function store(Request $request)
    {
        $valid = $this->validate($request->all(), [
            'name' => 'required|min:3|max:10',
            'email' => 'required|email',
        ]);

        if ($valid !== true) {
            return back()->withErrors($request->all(), $valid);
        }

        return redirect()->route('home.index')->with('message', 'Formulario enviado correctamente 22');
    }

    public function user(string $user)
    {
        return json([
            'message' => "user: $user",
        ]);
    }
}
