<?php

namespace App\Controllers;

use App\Models\User;
use Cronos\Http\Request;
use Cronos\Http\Controller;
use App\Middlewares\AuthMiddleware;
use App\Models\Blog;

class ApiController extends Controller
{
    public function __construct()
    {
        // $this->middleware(AuthMiddleware::class);
    }

    public function index()
    {
        $users = Blog::all();
        $data = [
            'status' => 'success',
            'users' => $users
        ];

        return json($data);
    }

    public function create(Request $request,)
    {
        //validar los datos
        $data = $request->all();
        $user = Blog::create($data);
        $data = [
            'status' => 'success',
            'user' => $user
        ];

        return json($data);
    }

    public function show(string $id)
    {
        $blog = Blog::find($id);
        $data = [
            'status' => 'success',
            'blog' => $blog
        ];

        return json($data);
    }

    public function update(Request $request, string $id)
    {

        $data = $request->all();
        $blog = Blog::update($id, $data);
        $data = [
            'status' => 'success',
            'blog' => $blog
        ];

        return json($data);
    }


    public function destroy(string $id)
    {
        $blog = Blog::delete($id);
        $data = [
            'status' => 'success',
            'blog' => $blog
        ];

        return json($data);
    }

    public function consultaJoin(Request $request)
    {
        // // Obtener todos los blogs de un usuario con email específico
        $blogs = Blog::select('blogs.*', 'users.name as author')
            ->join('users', 'blogs.user_id', '=', 'users.id')
            ->where('users.id', '1')
            ->orderBy('blogs.created_at', 'DESC')
            ->get();

        // // Obtener un blog específico con su autor
        // $blog = Blog::select('blogs.title', 'blogs.content', 'users.name as author')
        //     ->join('users', 'blogs.user_id', '=', 'users.id')
        //     ->where('blogs.slug', 'hola-peru')
        //     ->dd();

        // // Buscar blogs con contenido específico de un usuario
        // $posts = Blog::select('blogs.title', 'users.email')
        //     ->join('users', 'blogs.user_id', '=', 'users.id')
        //     ->where('blogs.content', 'LIKE', '%esta%')
        //     // ->andWhere('users.active', 1)
        //     ->limit(5)
        //     ->get();

        // $user = User::find(1);
        // $blogs = $user->blogs()->get();

        $data = [
            'status' => 'success',
            'blogs' => $blogs,
            // 'blog' => $blog,
            // 'posts' => $posts,
            // 'blogs' => $blogs
        ];



        return json($data);
    }
}
