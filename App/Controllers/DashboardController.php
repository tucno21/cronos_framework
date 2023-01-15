<?php

namespace App\Controllers;

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
        return view('dashboard');
    }
}
