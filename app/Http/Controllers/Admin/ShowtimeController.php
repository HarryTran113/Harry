<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Models\Movie;
use App\Models\Room;
use Illuminate\Http\Request;

class ShowtimeController extends Controller
{
    // Danh sách suất chiếu
    public function index()
    {
        $showtimes = Showtime::with(['movie', 'room'])->latest('start_time')->paginate(15);
        return view('admin.showtimes.index', compact('showtimes'));
    }

    // Lưu suất chiếu mới
    public function store(Request $request)
    {
        $data = $request->validate([
            'movie_id'   => 'required|exists:movies,id',
            'room_id'    => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'price'      => 'required|numeric|min:0',
        ]);

        Showtime::create($data);

        return back()->with('success', 'Đã tạo suất chiếu mới thành công!');
    }

    // Xóa suất chiếu
    public function destroy(Showtime $showtime)
    {
        $showtime->delete();
        return back()->with('success', 'Đã xóa suất chiếu thành công!');
    }
}