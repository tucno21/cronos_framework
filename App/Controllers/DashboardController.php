<?php

namespace App\Controllers;

use App\Models\Blog;
use App\Models\User;
use Cronos\Http\Request;
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
        return view('dashboard/index', [
            'pageTitle' => 'Dashboard'
        ]);
    }

    public function blogs()
    {
        $blogs = Blog::select('blogs.*', 'users.name')
            ->join('users', 'users.id', '=', 'blogs.user_id')
            ->get();

        return json($blogs);
    }

    public function create()
    {
        return view('dashboard/create', [
            'pageTitle' => 'Create Blog'
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $valid = $this->validate($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'slug' => 'required|slug|unique:Blog,slug',
            'content' => 'required|string|min:3|max:1000',
        ]);

        if ($valid !== true) {
            $data = [
                'status' => 'error',
                'message' => $valid
            ];
            return json($data);
        }

        $data = $request->all();
        $data->user_id = session()->user()->id;

        $blog = Blog::create($data);

        $data = [
            'status' => 'success',
            'message' => 'Blog created successfully',
            'blog' => $blog
        ];

        return json($data);
    }

    public function show(Blog $blog)
    {
        $user = User::find($blog->user_id);
        $blog->name = $user->name;
        // dd($blog);

        return view('dashboard/show', [
            'blog' => $blog,
            'pageTitle' => $blog->name
        ]);
    }

    public function edit(Blog $blog)
    {
        return json($blog);
    }

    public function update(Request $request, Blog $blog)
    {
        $valid = $this->validate($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'slug' => 'required|slug|unique:Blog,slug',
            'content' => 'required|string|min:3|max:1000',
        ]);

        if ($valid !== true) {
            $data = [
                'status' => 'error',
                'message' => $valid
            ];
            return json($data);
        }
        $data = $request->all();

        $blog = Blog::update($blog->id, $data);

        $data = [
            'status' => 'success',
            'message' => 'Blog update successfully',
            'blog' => $blog
        ];

        return json($data);
    }

    public function destroy(Blog $blog)
    {
        $blog = Blog::delete($blog->id);

        $data = [
            'status' => 'success',
            'message' => 'Blog deleted successfully',
            'blog' => $blog
        ];

        return json($data);
    }
}
