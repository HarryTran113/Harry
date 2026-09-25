<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    // Hiển thị danh sách phim kèm tìm kiếm
    public function index(Request $request)
    {
        $keyword = $request->input('q');

        $movies = Movie::query()
            ->when($keyword, function ($query, $keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                      ->orWhere('genre', 'like', "%{$keyword}%");
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.movies.index', compact('movies', 'keyword'));
    }

    // Kiểm tra tên phim qua Ajax khi rời chuột (.blur)
    public function checkName(Request $request)
    {
        $title = trim($request->input('tenphim', ''));
        $exists = Movie::where('title', $title)->exists();

        if ($exists) {
            return "<span class='text-danger'><i class='bi bi-x-circle'></i> Tên phim này đã tồn tại trong CSDL!</span>";
        }
        return "<span class='text-success'><i class='bi bi-check-circle'></i> Tên phim hợp lệ, có thể thêm!</span>";
    }

    // Thêm phim qua Ajax
    public function storeAjax(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'genre'        => 'required|string|max:100',
            'duration'     => 'required|integer|min:1',
            'release_date' => 'required|date',
            'status'       => 'nullable|string',
            'description'  => 'nullable|string',
        ]);

        if (empty($data['status'])) {
            $data['status'] = 'showing';
        }

        $movie = Movie::create($data);

        return response()->json([
            'status' => 'success',
            'movie'  => $movie
        ]);
    }

    // Đổi trạng thái nhanh
    public function quickStatus(Request $request)
    {
        $movie = Movie::findOrFail($request->id);
        $movie->update(['status' => $request->status]);

        return response()->json(['status' => 'success']);
    }

    // Xóa phim
    public function destroy(Movie $movie)
    {
        $movie->delete();

        return redirect()->route('admin.movies.index')->with('success', 'Đã xóa phim thành công!');
    }
}