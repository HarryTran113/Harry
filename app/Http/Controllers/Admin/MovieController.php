<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    // Liệt kê danh sách phim
    public function index()
    {
        $movies = Movie::latest('id')->get();
        return view('admin.movies.index', compact('movies'));
    }

    //Kiểm tra tên phim khi rời chuột (.blur)
    public function kiemTraTenPhim(Request $request)
    {
        $tenphim = trim($request->input('tenphim', ''));
        $phim = Movie::where('title', $tenphim)->first();

        if ($phim) {
            return "<span style='color: red;'><i class='bi bi-x-circle'></i> Tên phim này đã tồn tại trong CSDL!</span>";
        } else {
            return "<span style='color: green;'><i class='bi bi-check-circle'></i> Tên phim hợp lệ, có thể thêm!</span>";
        }
    }

    //Thêm phim mới vào database
    public function themPhimAjax(Request $request)
    {
        $movie = Movie::create([
            'title'        => $request->input('title'),
            'genre'        => $request->input('genre'),
            'duration'     => $request->input('duration'),
            'release_date' => $request->input('release_date'),
            'description'  => $request->input('description', ''),
            'is_active'    => 1
        ]);

        return response()->json([
            'status' => 'success',
            'movie'  => $movie
        ]);
    }

    // Xóa phim
    public function xoaPhim($id)
    {
        $movie = Movie::find($id);
        if ($movie) {
            $movie->delete();
        }
        return redirect()->route('admin.movies.index')->with('success', 'Đã xóa phim thành công!');
    }
}