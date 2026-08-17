<?php

namespace App\Controllers;

use Cronos\Http\Controller;


class SpaController extends Controller
{
    public function index()
    {
        return view('spa/index');
    }
}
