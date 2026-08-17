<?php

namespace App\Controllers;

use App\Library\JWT\JWTAuth;
use App\Models\Blog;
use App\Models\User;
use Cronos\Http\Controller;
use Cronos\Http\Request;

class BlogController extends Controller
{
    protected function authUser(Request $request): ?User
    {
        $token = $request->headers('X-Token');

        if (empty($token)) {
            return null;
        }

        $jwt = new JWTAuth();
        $decoded = $jwt->decodeToken($token);

        if (!$decoded) {
            return null;
        }

        return User::find($decoded->sub);
    }

    public function index()
    {
        $blogs = Blog::select('posts.*', 'users.name')
            ->join('users', 'users.id', '=', 'posts.user_id')
            ->orderBy('posts.created_at', 'DESC')
            ->get();

        return json([
            'status' => 'success',
            'blogs' => $blogs ? $blogs->toArray() : [],
        ]);
    }

    public function show(Blog $blog)
    {
        $blog->name = User::find($blog->user_id)->name ?? null;

        return json([
            'status' => 'success',
            'blog' => $blog->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $valid = $this->validate($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'slug' => 'required|slug|unique:Blog,slug',
            'content' => 'required|string|min:3|max:1000',
        ]);

        if ($valid !== true) {
            return json([
                'status' => 'error',
                'message' => $valid,
            ], 422);
        }

        $user = $this->authUser($request);
        if (!$user) {
            return json([
                'status' => 'error',
                'message' => 'No autenticado',
            ], 401);
        }

        $data = $request->all();
        $data->user_id = $user->id;

        $blog = Blog::create($data);

        return json([
            'status' => 'success',
            'message' => 'Blog creado correctamente',
            'blog' => $blog ? $blog->toArray() : null,
        ], 201);
    }

    public function update(Request $request, Blog $blog)
    {
        $slugRule = $request->slug === $blog->slug
            ? 'required|slug'
            : 'required|slug|unique:Blog,slug';

        $valid = $this->validate($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'slug' => $slugRule,
            'content' => 'required|string|min:3|max:1000',
        ]);

        if ($valid !== true) {
            return json([
                'status' => 'error',
                'message' => $valid,
            ], 422);
        }

        $updated = Blog::update($blog->id, $request->all());

        return json([
            'status' => 'success',
            'message' => 'Blog actualizado correctamente',
            'blog' => $updated ? $updated->toArray() : null,
        ]);
    }

    public function destroy(Blog $blog)
    {
        $deleted = Blog::delete($blog->id);

        return json([
            'status' => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Blog eliminado correctamente' : 'No se pudo eliminar el blog',
        ], $deleted ? 200 : 400);
    }
}
