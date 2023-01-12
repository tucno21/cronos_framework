<?php

use Cronos\Routing\Route;
use App\Controllers\UserController;
use Cronos\Http\Request;
use Cronos\Http\Response;

Route::get('/', function (Request $request) {
    return view('producto/index', ['data' => $request->all()]);
});

Route::get('/cambio', function (Request $request) {
    return view('home');
});

Route::get('/route/{param}/users/{users}', function (string $param, string $users) {
    return Response::json(['param' => $param, 'users' => $users]);
});

Route::get('/redirect', function () {
    return redirect('/cambio?name=juan');
});

Route::get('/get', function (Request $request) {
    // dd($request->all());
    return Response::json($request->all());
});

Route::post('/post', function (Request $request) {
    dd($request->only(['name', 'username']));
    // dd($request->all());
});


Route::put('/put', function (Request $request) {
    dd($request->input('email'));
});

Route::patch('/patch', function (Request $request) {
    dd($request->all());
});

Route::delete('/delete', function (Request $request) {
    dd($request->all());
});

Route::get('/get_controller', [UserController::class, 'index']);
Route::post('/post_controller', [UserController::class, 'store']);
Route::put('/put_controller', [UserController::class, 'update']);
Route::patch('/patch_controller', [UserController::class, 'update']);
Route::delete('/delete_controller', [UserController::class, 'destroy']);
