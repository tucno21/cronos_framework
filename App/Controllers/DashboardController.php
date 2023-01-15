<?php

namespace App\Controllers;

use Cronos\Http\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }
}
