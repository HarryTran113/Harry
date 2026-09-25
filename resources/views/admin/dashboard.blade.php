@extends('layouts.admin')

@section('title', 'Tổng quan & Thống kê')

@section('content')
<div class="container-fluid py-4">
    <!-- Tiêu đề trang -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1">Báo Cáo Doanh Thu & Thống Kê</h3>
            <p class="text-muted small mb-0">Hệ thống số liệu kinh doanh Cụm Rạp Cinego</p>
        </div>
        <div>
            <span class="badge bg-success-subtle text-success border border-success-subtle p-2">
                <i class="bi bi-clock-history me-1"></i> Số liệu Realtime (Eloquent ORM)
            </span>
        </div>
    </div>

    <!-- 4 Thẻ KPI Tài chính -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4 p-3 bg-white">
                <small class="text-muted fw-bold">TỔNG DOANH THU</small>
                <h3 class="fw-bold text-primary mb-0 mt-1">{{ number_format($totalRevenue, 0, ',', '.') }} đ</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-success border-4 p-3 bg-white">
                <small class="text-muted fw-bold">TỔNG VÉ ĐÃ BÁN</small>
                <h3 class="fw-bold text-success mb-0 mt-1">{{ $totalTickets }} Vé</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-warning border-4 p-3 bg-white">
                <small class="text-muted fw-bold">PHIM ĐANG CHIẾU</small>
                <h3 class="fw-bold text-warning mb-0 mt-1">{{ $activeMoviesCount }} Phim</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-info border-4 p-3 bg-white">
                <small class="text-muted fw-bold">LƯỢNG KHÁCH HÀNG</small>
                <h3 class="fw-bold text-info mb-0 mt-1">{{ $totalCustomers }} Khách</h3>
            </div>
        </div>
    </div>

    <!-- Khu vực 2 Biểu đồ & Đơn đặt gần nhất -->
    <div class="row g-3 mb-4">
        <!-- Biểu đồ cột: Top phim doanh thu cao -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Top Phim Doanh Thu Cao Nhất</h6>
                    <span class="badge bg-light text-dark border">Đơn vị: VNĐ</span>
                </div>
                <div style="height: 280px;">
                    <canvas id="barChartMovies"></canvas>
                </div>
            </div>
        </div>

        <!-- 5 Giao dịch đặt vé mới nhất -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-3 bg-white h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-danger"></i>Giao Dịch Gần Nhất</h6>
                <div class="list-group list-group-flush">
                    @forelse($recentBookings as $b)
                        <div class="list-group-item px-0 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-primary small">#{{ $b->code }}</span>
                                <span class="badge bg-light text-dark border">{{ $b->status }}</span>
                            </div>
                            <div class="small fw-bold text-dark">{{ $b->user->name ?? 'Khách vãng lai' }}</div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>{{ $b->showtime->movie->title ?? 'N/A' }}</span>
                                <span class="fw-bold text-danger">{{ number_format($b->total_price, 0, ',', '.') }} đ</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small text-center py-4 mb-0">Chưa có giao dịch nào.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng chi tiết doanh thu theo từng phim -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold"><i class="bi bi-trophy me-2 text-warning"></i>Bảng Chi Tiết Doanh Thu Từng Phim</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 60px;">Hạng</th>
                            <th>Tên Phim</th>
                            <th>Thể Loại</th>
                            <th class="text-center">Số Vé Đã Bán</th>
                            <th class="text-end pe-3">Doanh Thu Thu Được</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($moviesStat as $index => $m)
                            <tr>
                                <td class="ps-3 fw-bold text-muted">#{{ $index + 1 }}</td>
                                <td class="fw-bold">{{ $m->title }}</td>
                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $m->genre }}</span></td>
                                <td class="text-center fw-bold text-primary">{{ $m->tickets_sold ?? 0 }} vé</td>
                                <td class="text-end pe-3 fw-bold text-danger">{{ number_format($m->total_revenue, 0, ',', '.') }} đ</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Chưa có dữ liệu thống kê.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Thẻ ẩn chứa dữ liệu an toàn cho Chart.js (TUÂN THỦ 100% CHỐNG XSS, KHÔNG DÙNG {!! !!}) --}}
<div id="chartDataContainer" 
     data-titles="{{ json_encode($chartTitles) }}" 
     data-revenues="{{ json_encode($chartRevenues) }}" 
     class="d-none">
</div>
@endsection

@push('scripts')
{{-- Nạp thư viện Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var dataEl = document.getElementById('chartDataContainer');
    if (!dataEl) return;

    var chartTitles = JSON.parse(dataEl.getAttribute('data-titles') || '[]');
    var chartRevenues = JSON.parse(dataEl.getAttribute('data-revenues') || '[]');

    var ctxBar = document.getElementById('barChartMovies').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: chartTitles,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: chartRevenues,
                backgroundColor: 'rgba(99, 102, 241, 0.85)',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false } 
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { 
                            return value.toLocaleString('vi-VN') + ' đ'; 
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush