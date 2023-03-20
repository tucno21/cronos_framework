<?php

namespace App\Controllers;

use Cronos\Http\Controller;

class HomeController extends Controller
{
    public function index()
    {
        // $env = [];
        // foreach ($_ENV as $key => $value) {
        //     $env[$key] = $value;
        // }
        // dd($env);
        return view('home/index');
    }
}
