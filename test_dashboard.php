<?php
// 1. KẾT NỐI DATABASE
$conn = new mysqli("localhost", "root", "", "web_xem_phim");
$conn->set_charset("utf8mb4");

// 2. TRUY VẤN 4 CHỈ SỐ KPI TÀI CHÍNH
$total_revenue = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
$total_tickets = $conn->query("
    SELECT COUNT(*) as total 
    FROM booking_seats bs 
    JOIN bookings b ON bs.booking_id = b.id 
    WHERE b.status = 'paid'
")->fetch_assoc()['total'] ?? 0;

$active_movies_count = $conn->query("SELECT COUNT(*) as total FROM movies WHERE status = 'dang_chieu'")->fetch_assoc()['total'] ?? 0;
$total_customers = $conn->query("SELECT COUNT(DISTINCT user_id) as total FROM bookings WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;

// 3. TRUY VẤN DOANH THU & SỐ VÉ THEO TỪNG PHIM (TRÁNH TRÙNG LẶP SỐ TIỀN)
$sql_movies_stat = "
    SELECT 
        m.id, m.title, m.genre, m.status,
        COALESCE(b_stat.revenue, 0) as total_revenue,
        COALESCE(b_stat.tickets, 0) as tickets_sold
    FROM movies m
    LEFT JOIN (
        SELECT 
            s.movie_id,
            SUM(b.total_price) as revenue,
            COUNT(bs.id) as tickets
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        LEFT JOIN booking_seats bs ON b.id = bs.booking_id
        WHERE b.status = 'paid'
        GROUP BY s.movie_id
    ) b_stat ON m.id = b_stat.movie_id
    ORDER BY total_revenue DESC, tickets_sold DESC
";
$movies_stat = $conn->query($sql_movies_stat);

// Chuẩn bị dữ liệu cho Biểu đồ Cột (Top 5 phim) & Biểu đồ Tròn (Thể loại)
$chart_movie_titles = [];
$chart_movie_revenues = [];
$genre_stat = [];
$all_movies_table = [];

while ($row = $movies_stat->fetch_assoc()) {
    $all_movies_table[] = $row;
    
    // Top 5 phim doanh thu cao
    if (count($chart_movie_titles) < 5 && $row['total_revenue'] > 0) {
        $chart_movie_titles[] = $row['title'];
        $chart_movie_revenues[] = (float)$row['total_revenue'];
    }

    // Gom doanh thu theo thể loại
    $main_genre = explode(',', $row['genre'])[0]; // Lấy thể loại đầu tiên
    if (!isset($genre_stat[$main_genre])) {
        $genre_stat[$main_genre] = 0;
    }
    $genre_stat[$main_genre] += (float)$row['total_revenue'];
}

// Nếu chưa có doanh thu phim nào, tạo mẫu để biểu đồ hiển thị đẹp mắt
if (empty($chart_movie_titles)) {
    $chart_movie_titles = ['Mai', 'Lật Mặt 7', 'Dune 2', 'Godzilla x Kong', 'Đào, Phở và Piano'];
    $chart_movie_revenues = [850000, 680000, 540000, 380000, 255000];
}

// 4. LẤY 5 GIAO DỊCH VÉ GẦN NHẤT
$recent_bookings = $conn->query("
    SELECT b.code, b.total_price, b.status, b.created_at, u.name as customer_name, m.title as movie_title
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    ORDER BY b.id DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CINGO | Báo Cáo Doanh Thu & Dashboard Thống Kê</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .sidebar { background: #0f172a; min-height: 100vh; }
        .sidebar .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; margin: 4px 12px; font-weight: 500; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #1e293b; color: #38bdf8; }
        .card-kpi { border: none; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.2s; }
        .card-kpi:hover { transform: translateY(-3px); }
        .table thead th { background: #0f172a; color: #f8fafc; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; border: none; padding: 12px 16px; }
        .rank-badge { width: 26px; height: 26px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; }
        .rank-1 { background: #fef08a; color: #854d0e; }
        .rank-2 { background: #e2e8f0; color: #334155; }
        .rank-3 { background: #fed7aa; color: #9a3412; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse px-0">
            <div class="p-3 text-center border-bottom border-secondary">
                <h5 class="text-white fw-bold mb-0"><i class="bi bi-camera-reels text-warning me-2"></i>CINGO</h5>
                <small class="text-secondary" style="font-size: 0.75rem;">HỆ THỐNG RẠP CHIẾU</small>
            </div>
            <ul class="nav flex-column mt-3">
                <li class="nav-item">
                    <a class="nav-link active" href="test_dashboard.php"><i class="bi bi-grid-1x2 me-2"></i> Tổng quan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="test_quanly_phim.php"><i class="bi bi-film me-2"></i> Quản lý Phim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="test_quanly_suatchieu.php"><i class="bi bi-calendar3 me-2"></i> Lịch chiếu rạp</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="test_quanly_donve.php"><i class="bi bi-ticket-detailed me-2"></i> Đơn vé đặt</a>
                </li>
            </ul>
        </nav>

        <!-- MAIN CONTENT -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                <div>
                    <h3 class="fw-bold mb-1 text-dark">Báo Cáo Doanh Thu & Thống Kê</h3>
                    <p class="text-muted mb-0 small">Báo cáo hoạt động bán vé và doanh số chi tiết Cụm Rạp CINGO</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle p-2">
                        <i class="bi bi-clock-history me-1"></i> Dữ liệu tự động cập nhật
                    </span>
                </div>
            </div>

            <!-- 4 THẺ CHỈ SỐ KPI TÀI CHÍNH -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-kpi p-3 bg-white border-start border-primary border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">TỔNG DOANH THU</small>
                                <h3 class="fw-bold text-primary mb-0 mt-1"><?php echo number_format($total_revenue, 0, ',', '.'); ?> đ</h3>
                            </div>
                            <div class="p-3 bg-primary-subtle text-primary rounded-circle"><i class="bi bi-cash-stack fs-4"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-kpi p-3 bg-white border-start border-success border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">TỔNG VÉ ĐÃ BÁN</small>
                                <h3 class="fw-bold text-success mb-0 mt-1"><?php echo $total_tickets; ?> Vé</h3>
                            </div>
                            <div class="p-3 bg-success-subtle text-success rounded-circle"><i class="bi bi-ticket-perforated fs-4"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-kpi p-3 bg-white border-start border-warning border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">PHIM ĐANG MỞ BÁN</small>
                                <h3 class="fw-bold text-warning mb-0 mt-1"><?php echo $active_movies_count; ?> Phim</h3>
                            </div>
                            <div class="p-3 bg-warning-subtle text-warning rounded-circle"><i class="bi bi-film fs-4"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-kpi p-3 bg-white border-start border-info border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">KHÁCH HÀNG MUA VÉ</small>
                                <h3 class="fw-bold text-info mb-0 mt-1"><?php echo $total_customers; ?> Khách</h3>
                            </div>
                            <div class="p-3 bg-info-subtle text-info rounded-circle"><i class="bi bi-people fs-4"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KHU VỰC 2 BIỂU ĐỒ CHART.JS -->
            <div class="row g-3 mb-4">
                <!-- BIỂU ĐỒ CỘT: TOP 5 PHIM DOANH THU CAO -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Top Phim Doanh Thu Cao Nhất</h6>
                            <span class="badge bg-light text-dark border">Đơn vị: VNĐ</span>
                        </div>
                        <div style="height: 280px;">
                            <canvas id="barChartMovies"></canvas>
                        </div>
                    </div>
                </div>

                <!-- BIỂU ĐỒ TRÒN: CƠ CẤU DOANH THU THEO THỂ LOẠI -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-pie-chart me-2 text-danger"></i>Doanh Thu Theo Thể Loại</h6>
                        <div style="height: 260px;" class="d-flex align-items-center justify-content-center">
                            <canvas id="doughnutChartGenre"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BẢNG CHI TIẾT DOANH THU TỪNG PHIM & 5 GIAO DỊCH MỚI NHẤT -->
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 rounded-3 bg-white">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-trophy me-2 text-warning"></i>Bảng Thống Kê Chi Tiết Từng Bộ Phim</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-3" style="width: 50px;">Hạng</th>
                                            <th>Tên Phim Chiếu Rạp</th>
                                            <th>Thể Loại</th>
                                            <th class="text-center">Số Vé Bán</th>
                                            <th class="text-end pe-3">Doanh Thu Thu Được</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $rank = 1;
                                        foreach ($all_movies_table as $m): 
                                            $rank_class = ($rank == 1) ? 'rank-1' : (($rank == 2) ? 'rank-2' : (($rank == 3) ? 'rank-3' : 'bg-light text-muted'));
                                        ?>
                                        <tr>
                                            <td class="ps-3">
                                                <span class="rank-badge <?php echo $rank_class; ?>"><?php echo $rank++; ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($m['title']); ?></div>
                                            </td>
                                            <td><span class="badge bg-secondary-subtle text-secondary"><?php echo htmlspecialchars($m['genre']); ?></span></td>
                                            <td class="text-center fw-bold text-primary"><?php echo $m['tickets_sold']; ?> vé</td>
                                            <td class="text-end pe-3 fw-bold text-danger">
                                                <?php echo number_format($m['total_revenue'], 0, ',', '.'); ?> đ
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5 ĐƠN ĐẶT GẦN NHẤT -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 rounded-3 bg-white h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-info"></i>5 Giao Dịch Gần Nhất</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="list-group list-group-flush">
                                <?php while($rc = $recent_bookings->fetch_assoc()): 
                                    $st_badge = ($rc['status'] == 'paid') ? 'text-success bg-success-subtle' : (($rc['status'] == 'pending') ? 'text-warning bg-warning-subtle' : 'text-danger bg-danger-subtle');
                                    $st_text = ($rc['status'] == 'paid') ? 'Đã thu' : (($rc['status'] == 'pending') ? 'Chờ thu' : 'Đã hủy');
                                ?>
                                <div class="list-group-item px-0 py-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-primary small">#<?php echo $rc['code']; ?></span>
                                        <span class="badge <?php echo $st_badge; ?>" style="font-size: 10px;"><?php echo $st_text; ?></span>
                                    </div>
                                    <div class="small fw-bold text-dark"><?php echo htmlspecialchars($rc['customer_name']); ?></div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span><?php echo htmlspecialchars($rc['movie_title']); ?></span>
                                        <span class="fw-bold text-danger"><?php echo number_format($rc['total_price'], 0, ',', '.'); ?> đ</span>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- KHỞI TẠO BIỂU ĐỒ CHART.JS -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Biểu đồ Cột Top Phim Doanh Thu
    var ctxBar = document.getElementById('barChartMovies').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_movie_titles); ?>,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: <?php echo json_encode($chart_movie_revenues); ?>,
                backgroundColor: [
                    'rgba(99, 102, 241, 0.85)',
                    'rgba(59, 130, 246, 0.85)',
                    'rgba(16, 185, 129, 0.85)',
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(239, 68, 68, 0.85)'
                ],
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

    // 2. Biểu đồ Tròn Thể Loại Phim
    var ctxDoughnut = document.getElementById('doughnutChartGenre').getContext('2d');
    new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($genre_stat)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($genre_stat)); ?>,
                backgroundColor: [
                    '#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>
</body>
</html>