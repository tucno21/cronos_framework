<?php

namespace App\Controllers;

use App\Models\User;
use Cronos\Http\Request;
use Cronos\Http\Controller;

class HomeController extends Controller
{
    public function index()
    {

        // $user = new User();
        // $user->name = 'Yovana';
        // $user->email = 'yovana@condor.com';
        // $user->save();

        $id = 6;

        $data = [
            'name' => 'pamela 5',
            // 'email' => 'pamela@pamela.com',
        ];

        $result = User::delete($id);
        dd($result);



        // $user = User::orderBy('id', 'DESC')
        //     ->get();

        // $user = User::select('id', 'serie', 'correlativo', 'total', 'forma_pago')
        //     // ->where('email', '=', $data['email'])
        //     ->orderBy('id', 'DESC')
        //     ->get();

        // $user = User::select('ventas.*', 'users.name as vendedor', 'clientes.nombre as cliente')
        //     ->join('users', 'users.id', '=', 'ventas.usuario_id')
        //     ->join('clientes', 'clientes.id', '=', 'ventas.cliente_id')
        //     ->orderBy('ventas.id', 'DESC')
        //     ->get();

        // $data = [
        //     'nombre_tipodoc' => 'BOLETA',
        //     'forma_pago' => 'Credito',
        // ];

        // $search = '7';
        // $estado = 1;
        // $estado_sunat = 1;

        // $user = User::select('ventas.*', 'clientes.nombre as cliente')
        //     ->join('clientes', 'clientes.id', '=', 'ventas.cliente_id')
        //     ->where('ventas.correlativo', 'LIKE', "%$search%")
        //     ->andWhere('ventas.estado', '=', $estado)
        //     ->andWhere('ventas.estado_sunat', '=', $estado_sunat)
        //     ->orderBy('ventas.id', 'DESC')
        //     ->get();


        // $search = '7';
        // $estado = 1;
        // $estado_sunat = 1;
        // $usuarioCaja = 1;
        // $fecha_apertura = '2022-12-09 09:44:41';
        // $fecha_cierre = '2022-12-09 10:57:58';

        // $user = User::select('ventas.*', 'clientes.nombre as cliente')
        //     ->join('clientes', 'clientes.id', '=', 'ventas.cliente_id')
        //     ->whereBetween('fecha_emision', $fecha_apertura, $fecha_cierre)
        //     ->andWhere('ventas.estado', '=', $estado)
        //     ->andWhere('ventas.usuario_id', '=', $usuarioCaja)
        //     ->orderBy('ventas.id', 'DESC')
        //     ->limit(5)
        //     ->get();

        // ==========================

        // $user = User::select('users.id, users.email, users.name, users.status, users.rol_id, roles.rol_name')
        //     ->join('roles', 'roles.id', '=', 'users.rol_id')
        //     ->where('users.email', '=', $data['email'])
        //     ->limit(1)
        //     ->get();

        // $user = User::select('ventas.*, users.name as vendedor, clientes.nombre as cliente')
        //     ->join('users', 'users.id', '=', 'ventas.usuario_id')
        //     ->join('clientes', 'clientes.id', '=', 'ventas.cliente_id')
        //     ->orderBy('ventas.id', 'DESC')
        //     ->limit(8)
        //     ->get();


        // $sql = "SELECT v.*, c.nombre as cliente
        // FROM ventas v
        // INNER JOIN clientes c ON c.id = v.cliente_id
        // WHERE fecha_emision BETWEEN '$fecha_apertura' AND '$fecha_cierre'
        // AND v.estado = 1
        // AND v.usuario_id = $usuarioCaja
        // ORDER BY v.id DESC";

        // WHERE fecha_emision BETWEEN '2022-12-21 07:24:30' AND '2022-12-21 07:38:22'  -1
        // WHERE fecha_emision BETWEEN '2022-12-09 09:44:41' AND '2022-12-09 10:57:58' -3

        $data = [
            'nombre_tipodoc' => 'BOLETA',
            'forma_pago' => 'Credito',
        ];

        $search = '7';
        $estado = 1;
        $estado_sunat = 1;
        $usuarioCaja = 1;
        $fecha_apertura = '2022-12-09 09:44:41';
        $fecha_cierre = '2022-12-09 10:57:58';

        // $user = User::select('id', 'serie', 'correlativo', 'total', 'forma_pago')
        // ->join('clientes', 'clientes.id', '=', 'ventas.cliente_id')
        // ->whereBetween('fecha_emision', $fecha_apertura, $fecha_cierre)
        // ->andWhere('ventas.estado', '=', $estado)
        // ->andWhere('ventas.usuario_id', '=', $usuarioCaja)
        // ->orderBy('ventas.id', 'DESC')
        // ->limit(5)
        // ->get();


        $search = '7';
        $estado = 1;
        $estado_sunat = 1;

        // $user = User::select('ventas.*', 'clientes.nombre as cliente')
        //     ->join('clientes', 'clientes.id', '=', 'ventas.cliente_id')
        //     ->where('ventas.correlativo', 'LIKE', "%$search%")
        //     ->andWhere('ventas.estado', '=', $estado)
        //     ->andWhere('ventas.estado_sunat', '=', $estado_sunat)
        //     ->orderBy('ventas.id', 'DESC')
        //     ->get();

        // $user = User::select('id', 'serie', 'correlativo', 'total', 'forma_pago')
        //     ->where('estado', 0)
        //     ->orderBy('id', 'DESC')
        //     ->get();

        $user = User::orderBy('id', 'DESC')
            ->get();

        dd($user);

        return json($user);
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
