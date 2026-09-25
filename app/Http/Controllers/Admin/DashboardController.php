<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Booking;
use App\Models\BookingSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Hiển thị Dashboard tổng quan và thống kê doanh thu
     */
    public function index()
    {
        // 1. Các chỉ số KPI chính
        $totalRevenue = Booking::where('status', 'paid')->sum('total_price');
        
        $totalTickets = BookingSeat::whereHas('booking', function ($q) {
            $q->where('status', 'paid');
        })->count();

        $activeMoviesCount = Movie::where('status', 'showing')->count();
        $totalCustomers = Booking::where('status', 'paid')->distinct('user_id')->count('user_id');

        // 2. Thống kê doanh thu theo từng phim
        $moviesStat = Movie::withCount(['showtimes as tickets_sold' => function ($q) {
            $q->join('bookings', 'showtimes.id', '=', 'bookings.showtime_id')
              ->join('booking_seats', 'bookings.id', '=', 'booking_seats.booking_id')
              ->where('bookings.status', 'paid');
        }])->get()->map(function ($movie) {
            $revenue = Booking::whereHas('showtime', function ($q) use ($movie) {
                $q->where('movie_id', $movie->id);
            })->where('status', 'paid')->sum('total_price');

            $movie->total_revenue = $revenue;
            return $movie;
        })->sortByDesc('total_revenue');

        // 3. Chuẩn bị dữ liệu cho biểu đồ Chart.js
        $chartTitles = $moviesStat->take(5)->pluck('title')->toArray();
        $chartRevenues = $moviesStat->take(5)->pluck('total_revenue')->toArray();

        // 4. Lấy 5 đơn vé gần nhất
        $recentBookings = Booking::with(['user', 'showtime.movie'])
            ->latest('id')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'totalTickets',
            'activeMoviesCount',
            'totalCustomers',
            'moviesStat',
            'chartTitles',
            'chartRevenues',
            'recentBookings'
        ));
    }
}