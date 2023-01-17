<?php

namespace App\Controllers;

use App\Models\User;
use Cronos\Http\Request;
use Cronos\Crypto\Hasher;
use Cronos\Storage\Image;
use Cronos\Http\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('home');
    }

    public function login()
    {
        // dd($_SESSION);
        return view('login');
    }

    public function store(Request $request)
    {
        $valid = $this->validate($request->all(), [
            'email' => 'required|email|not_unique:User,email',
            'password' => 'required|password_verify:User,email',
        ]);

        if ($valid !== true) {
            return back()->withErrors($request->all(), $valid);
        }

        $user = User::where('email', $request->input('email'))->first();
        unset($user->password);

        session()->attempt($user);

        return redirect()->route('home.index')->with('message', 'Bienvenido');
    }

    public function register()
    {
        // dd($_SESSION);
        return view('register');
    }

    public function create(Request $request, Hasher $hasher)
    {
        $valid = $this->validate($request->all(), [
            'name' => 'required|string|min:3|max:15',
            'email' => 'required|email|unique:User,email',
            'password' => 'required|min:2|max:10|matches:repetir_password',
            'repetir_password' => 'required|matches:password',
        ]);

        if ($valid !== true) {
            return back()->withErrors($request->all(), $valid);
        }

        $data = $request->all();
        $data->password = $hasher->hash($data->password);
        //eliminar repetir_password
        unset($data->repetir_password);

        $user = User::create($data);

        return redirect()->route('home.index')->with('message', 'se registro correctamente');
    }

    public function logout()
    {
        session()->logout();
        return redirect()->route('home.index')->with('message', 'Sesion cerrada');
    }

    public function archivos()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {
        // $path = DIR_PUBLIC . '/img';

        // //crear carpeta si no existe
        // if (!is_dir($path)) {
        //     mkdir($path, 0777, true);
        // }

        // $nameImagen =  md5(uniqid(rand(), true)) . '.png';

        $image = Image::make($request->file('imagen'))->resize(200);

        $nombre = $image->save();

        dd($nombre);
    }
}
