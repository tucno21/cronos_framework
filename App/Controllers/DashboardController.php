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

        $blogs = Blog::select('blogs.*', 'users.name')
            ->join('users', 'users.id', '=', 'blogs.user_id')
            ->get();

        return view('dashboard/index', [
            'blogs' => $blogs,
            'pageTitle' => 'Dashboard'
        ]);
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
            return back()->withErrors($request->all(), $valid);
        }
        $data = $request->all();
        $data->user_id = session()->user()->id;
        Blog::create($data);

        return redirect()->route('dashboard.index');
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
        return view('dashboard/edit', [
            'blog' => $blog,
            'pageTitle' => 'Edit Blog'
        ]);
    }

    public function update(Request $request, Blog $blog)
    {
        $valid = $this->validate($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'slug' => 'required|slug|unique:Blog,slug',
            'content' => 'required|string|min:3|max:1000',
        ]);

        if ($valid !== true) {
            return back()->withErrors($request->all(), $valid);
        }
        $data = $request->all();

        Blog::update($blog->id, $data);

        return redirect()->route('dashboard.index');
    }

    public function destroy(Blog $blog)
    {
        Blog::delete($blog->id);

        return redirect()->route('dashboard.index');
    }
}
