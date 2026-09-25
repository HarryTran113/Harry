<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Showtime;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // Danh sách đơn đặt vé
    public function index(Request $request)
    {
        $q = $request->input('q');

        $bookings = Booking::with(['user', 'showtime.movie', 'showtime.room'])
            ->when($q, function ($query, $q) {
                $query->where('code', 'like', "%{$q}%")
                      ->orWhereHas('user', function ($sub) use ($q) {
                          $sub->where('phone', 'like', "%{$q}%")
                              ->orWhere('name', 'like', "%{$q}%");
                      });
            })
            ->latest('id')
            ->paginate(15);

        return view('admin.bookings.index', compact('bookings', 'q'));
    }

    // Cập nhật trạng thái đơn vé
    public function quickStatus(Request $request)
    {
        $booking = Booking::findOrFail($request->id);
        $booking->update(['status' => $request->status]);

        return response()->json(['status' => 'success']);
    }
}