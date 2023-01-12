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
        dd($request->all());
    }
}
