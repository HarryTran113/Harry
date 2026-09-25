<?php
// 1. KẾT NỐI DATABASE
$conn = new mysqli("localhost", "root", "", "movie_booking");
$conn->set_charset("utf8mb4");

// 2. AJAX: LẤY DANH SÁCH GHẾ ĐÃ ĐẶT CỦA 1 SUẤT CHIẾU ĐỂ BLOCK MÀU XÁM
if (isset($_GET['ajax_get_showtime_seats'])) {
    $st_id = (int)$_GET['showtime_id'];

    $st = $conn->query("
        SELECT s.*, m.title, r.name as room_name 
        FROM showtimes s 
        JOIN movies m ON s.movie_id=m.id 
        JOIN rooms r ON s.room_id=r.id 
        WHERE s.id = $st_id
    ")->fetch_assoc();

    // Lấy danh sách mã ghế đã đặt (loại trừ các đơn đã hủy)
    $booked_seats = [];
    $q = $conn->query("
        SELECT st.row_label, st.seat_number 
        FROM booking_seats bs 
        JOIN seats st ON bs.seat_id = st.id 
        JOIN bookings b ON bs.booking_id = b.id
        WHERE bs.showtime_id = $st_id AND b.status != 'cancelled'
    ");
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $booked_seats[] = strtoupper($r['row_label']) . (int)$r['seat_number'];
        }
    }

    echo json_encode([
        'status' => 'success',
        'price' => (float)$st['price'],
        'room_name' => $st['room_name'],
        'booked_seats' => $booked_seats
    ]);
    exit;
}

// 3. AJAX: ĐỔI TRẠNG THÁI ĐƠN VÉ (paid, pending, cancelled)
if (isset($_POST['ajax_update_booking_status'])) {
    $id = (int)$_POST['id'];
    $status = trim($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    
    echo json_encode(['status' => 'success', 'new_status' => $status]);
    exit;
}

// 4. AJAX: THÊM KHÁCH ĐẶT VÉ MỚI
if (isset($_POST['ajax_add_booking'])) {
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $showtime_id = (int)$_POST['showtime_id'];
    $seat_input = trim($_POST['seat_list']); 
    $status = trim($_POST['status'] ?? 'paid');

    // Tìm hoặc tạo khách hàng
    $stmtUser = $conn->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
    $stmtUser->bind_param("s", $customer_phone);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    
    if ($resUser->num_rows > 0) {
        $user_id = $resUser->fetch_assoc()['id'];
    } else {
        $email = 'guest_' . time() . '@cingo.vn';
        $pass = password_hash('123456', PASSWORD_DEFAULT);
        $insUser = $conn->prepare("INSERT INTO users (name, phone, email, password, role) VALUES (?, ?, ?, ?, 'user')");
        $insUser->bind_param("ssss", $customer_name, $customer_phone, $email, $pass);
        $insUser->execute();
        $user_id = $insUser->insert_id;
    }

    $stRes = $conn->query("
        SELECT s.*, m.title as movie_title, r.id as room_id, r.name as room_name 
        FROM showtimes s 
        JOIN movies m ON s.movie_id = m.id 
        JOIN rooms r ON s.room_id = r.id 
        WHERE s.id = $showtime_id
    ")->fetch_assoc();

    $seatsArr = array_filter(array_map('trim', explode(',', $seat_input)));
    $seat_count = max(1, count($seatsArr));
    $total_price = $stRes['price'] * $seat_count;
    $code = 'CG-' . rand(100000, 999999);

    // Lưu vào bảng bookings
    $stmtBook = $conn->prepare("INSERT INTO bookings (code, user_id, showtime_id, total_price, status) VALUES (?, ?, ?, ?, ?)");
    $stmtBook->bind_param("siids", $code, $user_id, $showtime_id, $total_price, $status);
    $stmtBook->execute();
    $booking_id = $stmtBook->insert_id;

    // Lưu vào bảng booking_seats
    foreach ($seatsArr as $seat_name) {
        preg_match('/([A-Za-z]+)([0-9]+)/', $seat_name, $matches);
        $row_label = !empty($matches[1]) ? strtoupper($matches[1]) : 'A';
        $seat_num = !empty($matches[2]) ? (int)$matches[2] : 1;

        $seatQuery = $conn->query("SELECT id FROM seats WHERE room_id = {$stRes['room_id']} AND row_label = '$row_label' AND seat_number = $seat_num LIMIT 1");
        if ($seatQuery->num_rows > 0) {
            $seat_id = $seatQuery->fetch_assoc()['id'];
        } else {
            $conn->query("INSERT INTO seats (room_id, row_label, seat_number, type) VALUES ({$stRes['room_id']}, '$row_label', $seat_num, 'standard')");
            $seat_id = $conn->insert_id;
        }
        $conn->query("INSERT INTO booking_seats (booking_id, showtime_id, seat_id) VALUES ($booking_id, $showtime_id, $seat_id)");
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => $booking_id,
            'code' => $code,
            'customer_name' => htmlspecialchars($customer_name),
            'customer_phone' => htmlspecialchars($customer_phone),
            'movie_title' => htmlspecialchars($stRes['movie_title']),
            'room_name' => htmlspecialchars($stRes['room_name']),
            'start_time' => date('H:i - d/m/Y', strtotime($stRes['start_time'])),
            'booking_time' => date('H:i d/m'),
            'seat_list' => implode(', ', $seatsArr),
            'total_price' => $total_price,
            'status' => $status
        ]
    ]);
    exit;
}

// 5. TRUY VẤN THỐNG KÊ & DỮ LIỆU
$total_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc()['total'];
$paid_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'paid'")->fetch_assoc()['total'];
$pending_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'")->fetch_assoc()['total'];
$total_revenue = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;

// Lấy danh sách phim có suất chiếu
$movies_with_showtimes = $conn->query("
    SELECT DISTINCT m.id, m.title 
    FROM movies m 
    JOIN showtimes s ON m.id = s.movie_id 
    ORDER BY m.title ASC
");

// Lấy toàn bộ suất chiếu để JavaScript lọc theo phim
$all_showtimes_raw = $conn->query("
    SELECT s.id, s.movie_id, s.start_time, s.price, r.name as room_name 
    FROM showtimes s 
    JOIN rooms r ON s.room_id = r.id 
    ORDER BY s.start_time ASC
");
$showtimes_json = [];
while ($st = $all_showtimes_raw->fetch_assoc()) {
    $showtimes_json[] = [
        'id' => $st['id'],
        'movie_id' => $st['movie_id'],
        'room_name' => $st['room_name'],
        'start_time' => date('H:i - d/m/Y', strtotime($st['start_time'])),
        'price' => (float)$st['price']
    ];
}

// Danh sách đơn vé hiển thị
$sql = "
    SELECT 
        b.id, b.code, b.total_price, b.status, b.created_at as booking_time,
        u.name as customer_name, u.phone as customer_phone, u.email as customer_email,
        m.title as movie_title,
        r.name as room_name,
        s.start_time,
        GROUP_CONCAT(CONCAT(st.row_label, st.seat_number) ORDER BY st.row_label, st.seat_number SEPARATOR ', ') as seat_list
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN rooms r ON s.room_id = r.id
    LEFT JOIN booking_seats bs ON b.id = bs.booking_id
    LEFT JOIN seats st ON bs.seat_id = st.id
    GROUP BY b.id
    ORDER BY b.id DESC
";
$bookings = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CINGO | Quản Lý Đơn Vé & Đặt Chỗ</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .sidebar { background: #0f172a; min-height: 100vh; }
        .sidebar .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; margin: 4px 12px; font-weight: 500; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #1e293b; color: #38bdf8; }
        .card-metric { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table thead th { background: #0f172a; color: #f8fafc; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; border: none; padding: 12px 16px; }
        .booking-code { font-family: 'Courier New', Courier, monospace; font-weight: 700; color: #4338ca; }
        
        .select-status { font-size: 0.82rem; font-weight: 600; border-radius: 20px; padding: 4px 10px; cursor: pointer; transition: all 0.2s; }
        .status-paid { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .status-pending { background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }
        .status-cancelled { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

        /* SƠ ĐỒ GHẾ NGỒI CHUYÊN NGHIỆP */
        .cinema-screen-box {
            background: #111827;
            border-radius: 12px;
            padding: 20px;
            color: #fff;
        }
        .screen-line {
            width: 80%;
            height: 4px;
            background: #38bdf8;
            margin: 0 auto 6px;
            border-radius: 4px;
            box-shadow: 0 0 10px #38bdf8;
        }
        .seat-row {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-bottom: 6px;
            align-items: center;
        }
        .row-name {
            width: 22px;
            font-size: 11px;
            font-weight: bold;
            color: #94a3b8;
            text-align: right;
            margin-right: 4px;
        }
        .seat {
            width: 32px;
            height: 28px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            user-select: none;
            transition: all 0.15s;
        }
        .seat:hover:not(.seat-booked) {
            transform: scale(1.15);
            filter: brightness(1.2);
        }
        /* Ghế thường: Tím */
        .seat-standard { background-color: #7c3aed; color: #fff; }
        /* Ghế VIP: Đỏ */
        .seat-vip { background-color: #dc2626; color: #fff; }
        /* Ghế Đôi: Hồng */
        .seat-couple { background-color: #db2777; color: #fff; width: 44px; }
        
        /* GHẾ ĐÃ ĐẶT: BLOCK MÀU XÁM (KHÔNG THỂ BẤM) */
        .seat-booked {
            background-color: #475569 !important;
            color: #94a3b8 !important;
            cursor: not-allowed !important;
            opacity: 0.55;
            pointer-events: none;
            text-decoration: line-through;
        }
        
        /* GHẾ ĐANG CHỌN: XANH LÁ SÁNG */
        .seat-selected {
            background-color: #22c55e !important;
            color: #ffffff !important;
            box-shadow: 0 0 10px #22c55e !important;
            transform: scale(1.1);
        }
    </style>
</head>
<body>

<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
    <div id="liveToast" class="toast align-items-center text-white bg-dark border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">
                <i class="bi bi-check-circle-fill text-success me-2"></i> Thao tác thành công!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

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
                    <a class="nav-link" href="test_dashboard.php"><i class="bi bi-grid-1x2 me-2"></i> Tổng quan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="test_quanly_phim.php"><i class="bi bi-film me-2"></i> Quản lý Phim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="test_quanly_suatchieu.php"><i class="bi bi-calendar3 me-2"></i> Lịch chiếu rạp</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="test_quanly_donve.php"><i class="bi bi-ticket-detailed me-2"></i> Đơn vé đặt</a>
                </li>
            </ul>
        </nav>

        <!-- MAIN -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                <div>
                    <h3 class="fw-bold mb-1 text-dark">Quản Lý Đơn Đặt Vé</h3>
                    <p class="text-muted mb-0 small">Tra cứu mã vé, số điện thoại và bán vé tại quầy CINGO</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary shadow-sm" id="btnOpenAddBooking">
                        <i class="bi bi-plus-circle me-1"></i> Đặt Vé Tại Quầy
                    </button>
                </div>
            </div>

            <!-- THỐNG KÊ -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-metric p-3 bg-white">
                        <small class="text-muted fw-bold">TỔNG ĐƠN ĐẶT</small>
                        <h3 class="fw-bold text-dark mb-0 mt-1" id="statTotal"><?php echo $total_bookings; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-metric p-3 bg-white border-start border-success border-4">
                        <small class="text-success fw-bold">🟢 ĐÃ THANH TOÁN</small>
                        <h3 class="fw-bold text-success mb-0 mt-1" id="statPaid"><?php echo $paid_bookings; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-metric p-3 bg-white border-start border-warning border-4">
                        <small class="text-warning fw-bold">🟡 CHỜ THANH TOÁN</small>
                        <h3 class="fw-bold text-warning mb-0 mt-1" id="statPending"><?php echo $pending_bookings; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-metric p-3 bg-white border-start border-primary border-4">
                        <small class="text-primary fw-bold">💰 DOANH THU ĐÃ THU</small>
                        <h4 class="fw-bold text-primary mb-0 mt-1" id="statRevenue"><?php echo number_format($total_revenue, 0, ',', '.'); ?> đ</h4>
                    </div>
                </div>
            </div>

            <!-- BẢNG ĐƠN VÉ -->
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-receipt me-2"></i>Danh Sách Đơn Đặt Vé</h6>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                <input type="text" id="liveSearch" class="form-control" placeholder="Tìm theo Mã vé, Tên khách hoặc SĐT...">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4">
                            <select id="filterStatus" class="form-select form-select-sm">
                                <option value="all">-- Tất cả trạng thái --</option>
                                <option value="paid">🟢 Đã Thanh Toán</option>
                                <option value="pending">🟡 Chờ Thanh Toán</option>
                                <option value="cancelled">🔴 Đã Hủy</option>
                            </select>
                        </div>
                        <div class="col-md-1 col-sm-2 text-end">
                            <button class="btn btn-sm btn-outline-secondary w-100" id="btnResetFilter" title="Đặt lại bộ lọc">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="bangDonVe">
                            <thead>
                                <tr>
                                    <th class="ps-3" style="width: 130px;">Mã Đơn Vé</th>
                                    <th>Khách Hàng (SĐT)</th>
                                    <th>Phim & Suất Chiếu</th>
                                    <th>Ghế Đặt</th>
                                    <th>Tổng Tiền</th>
                                    <th style="width: 170px;">Trạng Thái</th>
                                    <th class="text-center" style="width: 90px;">Chi Tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $bookings->fetch_assoc()): 
                                    $st = $row['status'];
                                    $seats = !empty($row['seat_list']) ? $row['seat_list'] : 'Chưa chọn';
                                ?>
                                <tr id="dong-<?php echo $row['id']; ?>" 
                                    data-code="<?php echo strtolower($row['code']); ?>"
                                    data-name="<?php echo strtolower($row['customer_name']); ?>"
                                    data-phone="<?php echo strtolower($row['customer_phone']); ?>"
                                    data-status="<?php echo $st; ?>"
                                    data-price="<?php echo $row['total_price']; ?>">
                                    
                                    <td class="ps-3">
                                        <span class="booking-code">#<?php echo htmlspecialchars($row['code']); ?></span>
                                        <div class="text-muted small"><?php echo date('H:i d/m', strtotime($row['booking_time'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark cell-customer"><?php echo htmlspecialchars($row['customer_name']); ?></div>
                                        <small class="text-primary cell-phone"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($row['customer_phone']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark mb-1">
                                            <i class="bi bi-film text-danger me-1"></i><?php echo htmlspecialchars($row['movie_title']); ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-check me-1"></i><?php echo date('H:i - d/m/Y', strtotime($row['start_time'])); ?> | 
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['room_name']); ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold">
                                            <i class="bi bi-grid-3x3-gap me-1"></i><?php echo $seats; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-danger"><?php echo number_format($row['total_price'], 0, ',', '.'); ?> đ</span>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm select-status status-<?php echo $st; ?>" data-id="<?php echo $row['id']; ?>">
                                            <option value="paid" <?php if($st == 'paid') echo 'selected'; ?>>🟢 Đã Thanh Toán</option>
                                            <option value="pending" <?php if($st == 'pending') echo 'selected'; ?>>🟡 Chờ Thanh Toán</option>
                                            <option value="cancelled" <?php if($st == 'cancelled') echo 'selected'; ?>>🔴 Đã Hủy</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary btn-view" 
                                                data-code="<?php echo $row['code']; ?>"
                                                data-name="<?php echo htmlspecialchars($row['customer_name']); ?>"
                                                data-phone="<?php echo htmlspecialchars($row['customer_phone']); ?>"
                                                data-movie="<?php echo htmlspecialchars($row['movie_title']); ?>"
                                                data-time="<?php echo date('H:i - d/m/Y', strtotime($row['start_time'])); ?>"
                                                data-room="<?php echo htmlspecialchars($row['room_name']); ?>"
                                                data-seats="<?php echo $seats; ?>"
                                                data-price="<?php echo number_format($row['total_price'], 0, ',', '.') . ' đ'; ?>"
                                                title="Xem chi tiết vé">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>

                                <tr id="noResultRow" class="d-none">
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-search fs-3 d-block mb-1"></i>
                                        Không tìm thấy đơn vé nào khớp với thông tin tìm kiếm.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- MODAL BÁN VÉ TẠI QUẦY (ĐÃ TÁCH CHỌN PHIM VÀ SUẤT CHIẾU + SƠ ĐỒ GHẾ NGỒI BLOCK XÁM) -->
<div class="modal fade" id="modalAddBooking" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6"><i class="bi bi-ticket-perforated me-2 text-warning"></i>Bán Vé Tại Quầy - Rạp CINGO</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddBooking">
                <div class="modal-body p-4">
                    <!-- KHÁCH HÀNG -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tên khách hàng <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control form-control-sm" placeholder="VD: Trần Hùng Huy" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="tel" name="customer_phone" class="form-control form-control-sm" placeholder="VD: 0903099999" required>
                        </div>
                    </div>

                    <!-- TÁCH RIÊNG: CHỌN PHIM VÀ CHỌN SUẤT CHIẾU -->
                    <div class="row g-2 mb-3">
                        <!-- 1. CHỌN PHIM -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">1. Chọn Phim Chiếu <span class="text-danger">*</span></label>
                            <select id="selectMovie" class="form-select form-select-sm" required>
                                <option value="">-- Bấm chọn phim --</option>
                                <?php while($m = $movies_with_showtimes->fetch_assoc()): ?>
                                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['title']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- 2. CHỌN SUẤT CHIẾU (TỰ LỌC THEO PHIM ĐÃ CHỌN) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">2. Chọn Suất Chiếu & Phòng <span class="text-danger">*</span></label>
                            <select id="selectShowtime" name="showtime_id" class="form-select form-select-sm" required disabled>
                                <option value="">-- Vui lòng chọn phim trước --</option>
                            </select>
                        </div>
                    </div>

                    <!-- SƠ ĐỒ GHẾ NGỒI TRỰC QUAN (BLOCK MÀU XÁM GHẾ ĐÃ ĐẶT) -->
                    <div class="mb-3" id="seatMapSection" style="display: none;">
                        <label class="form-label fw-bold small d-flex justify-content-between align-items-center">
                            <span>3. Click Chọn Ghế Ngồi:</span>
                            <span class="text-muted small">Đơn giá: <strong class="text-danger" id="seatUnitPrice">0 đ</strong>/vé</span>
                        </label>
                        
                        <div class="cinema-screen-box">
                            <!-- MÀN HÌNH -->
                            <div class="text-center mb-4">
                                <div class="screen-line"></div>
                                <small class="text-secondary tracking-widest fw-bold">MÀN HÌNH CHIẾU</small>
                            </div>

                            <!-- LƯỚI GHẾ NGỒI TỰ SINH -->
                            <div id="seatGridContainer" class="py-2">
                                <!-- JavaScript sẽ tự vẽ các hàng ghế vào đây -->
                            </div>

                            <!-- CHÚ THÍCH MÀU GHẾ -->
                            <div class="d-flex justify-content-center flex-wrap gap-3 mt-3 pt-3 border-top border-secondary small text-muted">
                                <div><span class="badge bg-secondary me-1" style="width: 14px; height: 14px;">&nbsp;</span> Ghế đã đặt (Khóa)</div>
                                <div><span class="badge me-1" style="background:#7c3aed; width: 14px; height: 14px;">&nbsp;</span> Ghế thường</div>
                                <div><span class="badge me-1" style="background:#dc2626; width: 14px; height: 14px;">&nbsp;</span> Ghế VIP</div>
                                <div><span class="badge me-1" style="background:#db2777; width: 14px; height: 14px;">&nbsp;</span> Ghế Đôi</div>
                                <div><span class="badge me-1" style="background:#22c55e; width: 14px; height: 14px;">&nbsp;</span> Đang chọn</div>
                            </div>
                        </div>
                    </div>

                    <!-- TÓM TẮT & TRẠNG THÁI THANH TOÁN -->
                    <div class="row g-2 align-items-center bg-light p-3 rounded border">
                        <div class="col-md-7">
                            <div class="small text-muted">Ghế đã click chọn:</div>
                            <h6 class="fw-bold text-primary mb-1" id="selectedSeatsText">Chưa chọn ghế nào</h6>
                            <input type="hidden" name="seat_list" id="seatListInput" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small mb-1">Trạng thái thanh toán:</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="paid" selected>🟢 Đã thanh toán</option>
                                <option value="pending">🟡 Chờ thanh toán</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small">Tổng thanh toán:</span>
                        <h5 class="fw-bold text-danger mb-0" id="totalPriceText">0 đ</h5>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" id="btnSubmitBooking" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i> Lưu Đơn Vé
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL CHI TIẾT ĐƠN VÉ (POPUP XEM VÉ) -->
<div class="modal fade" id="modalTicketDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6"><i class="bi bi-ticket-perforated me-2 text-warning"></i>Chi Tiết Vé Xem Phim CINGO</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center pb-3 border-bottom mb-3">
                    <h5 class="fw-bold text-primary mb-0" id="detailMovie">--</h5>
                    <span class="badge bg-light text-dark border mt-1" id="detailRoom">--</span>
                </div>
                
                <div class="row g-2 mb-2">
                    <div class="col-5 text-muted small">Mã đơn vé:</div>
                    <div class="col-7 fw-bold booking-code" id="detailCode">--</div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-5 text-muted small">Khách hàng:</div>
                    <div class="col-7 fw-bold" id="detailName">--</div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-5 text-muted small">Số điện thoại:</div>
                    <div class="col-7 fw-bold text-primary" id="detailPhone">--</div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-5 text-muted small">Thời gian chiếu:</div>
                    <div class="col-7 fw-bold" id="detailTime">--</div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-5 text-muted small">Ghế đã đặt:</div>
                    <div class="col-7"><span class="badge bg-primary text-white" id="detailSeats">--</span></div>
                </div>
                <div class="row g-2 mt-3 pt-3 border-top">
                    <div class="col-5 text-muted fw-bold">Tổng thanh toán:</div>
                    <div class="col-7 fw-bold fs-5 text-danger" id="detailPrice">--</div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
// Danh sách toàn bộ suất chiếu chuyển từ PHP sang JS
var allShowtimes = <?php echo json_encode($showtimes_json); ?>;
var currentUnitPrice = 0;
var selectedSeats = [];

$(document).ready(function(){
    var modalDetail = new bootstrap.Modal(document.getElementById('modalTicketDetail'));
    var modalAdd = new bootstrap.Modal(document.getElementById('modalAddBooking'));
    var toast = new bootstrap.Toast(document.getElementById('liveToast'));

    function showToast(msg) {
        $("#toastMessage").html("<i class='bi bi-check-circle-fill text-success me-2'></i> " + msg);
        toast.show();
    }

    $("#btnOpenAddBooking").click(function(){
        modalAdd.show();
    });

    // 1. TÁCH BIỆT: CHỌN PHIM -> TỰ ĐỘNG ĐỔ SUẤT CHIẾU CỦA PHIM ĐÓ
    $("#selectMovie").change(function(){
        var movieId = parseInt($(this).val());
        var stSelect = $("#selectShowtime");

        stSelect.empty().append('<option value="">-- Bấm chọn suất chiếu --</option>');
        $("#seatMapSection").hide();
        resetSeatSelection();

        if (movieId) {
            var filtered = allShowtimes.filter(s => s.movie_id == movieId);
            if (filtered.length > 0) {
                filtered.forEach(function(s){
                    stSelect.append(`<option value="${s.id}" data-price="${s.price}">${s.start_time} | ${s.room_name} (${s.price.toLocaleString('vi-VN')} đ)</option>`);
                });
                stSelect.prop('disabled', false);
            } else {
                stSelect.append('<option value="">Không có suất chiếu nào</option>').prop('disabled', true);
            }
        } else {
            stSelect.prop('disabled', true);
        }
    });

    // 2. CHỌN SUẤT CHIẾU -> TỰ ĐỘNG GỌI AJAX LẤY GHẾ ĐÃ ĐẶT VÀ VẼ SƠ ĐỒ GHẾ
    $("#selectShowtime").change(function(){
        var stId = $(this).val();
        if(!stId) {
            $("#seatMapSection").hide();
            return;
        }

        resetSeatSelection();

        // Gọi Ajax lấy danh sách ghế đã bị đặt của suất chiếu này
        $.get("test_quanly_donve.php", {
            ajax_get_showtime_seats: 1,
            showtime_id: stId
        }, function(res){
            var data = JSON.parse(res);
            if (data.status === 'success') {
                currentUnitPrice = data.price;
                $("#seatUnitPrice").text(currentUnitPrice.toLocaleString('vi-VN') + " đ");
                
                // Vẽ sơ đồ ghế với các ghế bị block xám
                renderSeatGrid(data.booked_seats);
                $("#seatMapSection").slideDown();
            }
        });
    });

    // 3. HÀM VẼ SƠ ĐỒ GHẾ NGỒI (Y HỆT ẢNH MẪU)
    function renderSeatGrid(bookedSeats) {
        var container = $("#seatGridContainer");
        container.empty();

        // Cấu hình các hàng ghế: A, B (thường), C, D, E (VIP), F (Đôi)
        var rowConfigs = [
            { label: 'A', count: 10, type: 'seat-standard' },
            { label: 'B', count: 10, type: 'seat-standard' },
            { label: 'C', count: 10, type: 'seat-vip' },
            { label: 'D', count: 10, type: 'seat-vip' },
            { label: 'E', count: 10, type: 'seat-vip' },
            { label: 'F', count: 8,  type: 'seat-couple' }
        ];

        rowConfigs.forEach(function(row){
            var rowHtml = `<div class="seat-row"><div class="row-name">${row.label}</div>`;
            for (var i = 1; i <= row.count; i++) {
                var seatCode = row.label + i;
                // KIỂM TRA: NẾU GHẾ NẰM TRONG DANH SÁCH ĐÃ ĐẶT -> BLOCK MÀU XÁM
                var isBooked = bookedSeats.includes(seatCode);
                var bookedClass = isBooked ? 'seat-booked' : '';

                rowHtml += `<div class="seat ${row.type} ${bookedClass}" data-seat="${seatCode}">${seatCode}</div>`;
            }
            rowHtml += `</div>`;
            container.append(rowHtml);
        });
    }

    // 4. BẮT SỰ KIỆN CLICK CHỌN GHẾ TRÊN SƠ ĐỒ
    $(document).on("click", ".seat:not(.seat-booked)", function(){
        var seatCode = $(this).data("seat");

        if ($(this).hasClass("seat-selected")) {
            $(this).removeClass("seat-selected");
            selectedSeats = selectedSeats.filter(s => s !== seatCode);
        } else {
            $(this).addClass("seat-selected");
            selectedSeats.push(seatCode);
        }

        updateSeatSummary();
    });

    function updateSeatSummary() {
        if (selectedSeats.length > 0) {
            $("#selectedSeatsText").text(selectedSeats.join(", ") + " (" + selectedSeats.length + " ghế)");
            $("#seatListInput").val(selectedSeats.join(", "));
            var total = selectedSeats.length * currentUnitPrice;
            $("#totalPriceText").text(total.toLocaleString('vi-VN') + " đ");
        } else {
            $("#selectedSeatsText").text("Chưa chọn ghế nào");
            $("#seatListInput").val('');
            $("#totalPriceText").text("0 đ");
        }
    }

    function resetSeatSelection() {
        selectedSeats = [];
        updateSeatSummary();
    }

    // 5. AJAX THÊM ĐƠN ĐẶT VÉ
    $("#formAddBooking").submit(function(e){
        e.preventDefault();

        if (selectedSeats.length === 0) {
            alert("Vui lòng click chọn ít nhất 1 ghế trên sơ đồ màn hình!");
            return;
        }

        var formData = $(this).serialize() + '&ajax_add_booking=1';

        $.post("test_quanly_donve.php", formData, function(res){
            var response = JSON.parse(res);
            if(response.status === 'success') {
                var b = response.data;
                modalAdd.hide();
                $("#formAddBooking")[0].reset();
                $("#seatMapSection").hide();
                resetSeatSelection();

                var optPaid = (b.status === 'paid') ? 'selected' : '';
                var optPending = (b.status === 'pending') ? 'selected' : '';

                var dongMoi = `
                    <tr id="dong-${b.id}" 
                        data-code="${b.code.toLowerCase()}"
                        data-name="${b.customer_name.toLowerCase()}"
                        data-phone="${b.customer_phone.toLowerCase()}"
                        data-status="${b.status}"
                        data-price="${b.total_price}"
                        class="table-success">
                        
                        <td class="ps-3">
                            <span class="booking-code">#${b.code}</span>
                            <div class="text-muted small">${b.booking_time}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark cell-customer">${b.customer_name}</div>
                            <small class="text-primary cell-phone"><i class="bi bi-telephone me-1"></i>${b.customer_phone}</small>
                        </td>
                        <td>
                            <div class="fw-bold text-dark mb-1">
                                <i class="bi bi-film text-danger me-1"></i>${b.movie_title}
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-calendar-check me-1"></i>${b.start_time} | 
                                <span class="badge bg-light text-dark border">${b.room_name}</span>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold">
                                <i class="bi bi-grid-3x3-gap me-1"></i>${b.seat_list}
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-danger">${b.total_price.toLocaleString('vi-VN')} đ</span>
                        </td>
                        <td>
                            <select class="form-select form-select-sm select-status status-${b.status}" data-id="${b.id}">
                                <option value="paid" ${optPaid}>🟢 Đã Thanh Toán</option>
                                <option value="pending" ${optPending}>🟡 Chờ Thanh Toán</option>
                                <option value="cancelled">🔴 Đã Hủy</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary btn-view" 
                                    data-code="${b.code}"
                                    data-name="${b.customer_name}"
                                    data-phone="${b.customer_phone}"
                                    data-movie="${b.movie_title}"
                                    data-time="${b.start_time}"
                                    data-room="${b.room_name}"
                                    data-seats="${b.seat_list}"
                                    data-price="${b.total_price.toLocaleString('vi-VN')} đ"
                                    title="Xem chi tiết vé">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $("#bangDonVe tbody").prepend(dongMoi);
                showToast("Đã bán vé thành công cho khách " + b.customer_name + "!");
                applyFilters();
            }
        });
    });

    // 6. AJAX ĐỔI TRẠNG THÁI VÉ
    $(document).on("change", ".select-status", function(){
        var selectEl = $(this);
        var id = selectEl.data("id");
        var newStatus = selectEl.val();
        var tr = $("#dong-" + id);

        $.post("test_quanly_donve.php", {
            ajax_update_booking_status: 1,
            id: id,
            status: newStatus
        }, function(res){
            var response = JSON.parse(res);
            if(response.status === 'success') {
                selectEl.removeClass("status-paid status-pending status-cancelled").addClass("status-" + newStatus);
                tr.attr("data-status", newStatus);

                var ten = (newStatus === 'paid') ? "Đã Thanh Toán" : ((newStatus === 'pending') ? "Chờ Thanh Toán" : "Đã Hủy");
                showToast("Đã cập nhật đơn vé #" + id + " sang: <strong>" + ten + "</strong>");
                applyFilters();
            }
        });
    });

    // 7. XEM CHI TIẾT VÉ
    $(document).on("click", ".btn-view", function(){
        var btn = $(this);
        $("#detailCode").text("#" + btn.data("code"));
        $("#detailName").text(btn.data("name"));
        $("#detailPhone").text(btn.data("phone"));
        $("#detailMovie").text(btn.data("movie"));
        $("#detailRoom").text(btn.data("room"));
        $("#detailTime").text(btn.data("time"));
        $("#detailSeats").text(btn.data("seats"));
        $("#detailPrice").text(btn.data("price"));

        modalDetail.show();
    });

    // 8. TÌM KIẾM ĐA NĂNG
    function applyFilters() {
        var keyword = $("#liveSearch").val().toLowerCase().trim();
        var selectedStatus = $("#filterStatus").val();

        var countTotal = 0;
        var countPaid = 0;
        var countPending = 0;
        var totalRevenue = 0;

        $("#bangDonVe tbody tr:not(#noResultRow)").each(function(){
            var row = $(this);
            var code = row.attr("data-code");
            var name = row.attr("data-name");
            var phone = row.attr("data-phone");
            var status = row.attr("data-status");
            var price = parseFloat(row.attr("data-price")) || 0;

            var matchKeyword = (keyword === '' || code.indexOf(keyword) > -1 || phone.indexOf(keyword) > -1 || name.indexOf(keyword) > -1);
            var matchStatus = (selectedStatus === 'all' || status === selectedStatus);

            if (matchKeyword && matchStatus) {
                row.show();
                countTotal++;
                if (status === 'paid') {
                    countPaid++;
                    totalRevenue += price;
                }
                if (status === 'pending') countPending++;
            } else {
                row.hide();
            }
        });

        $("#statTotal").text(countTotal);
        $("#statPaid").text(countPaid);
        $("#statPending").text(countPending);
        $("#statRevenue").text(totalRevenue.toLocaleString('vi-VN') + " đ");

        if (countTotal === 0) {
            $("#noResultRow").removeClass("d-none");
        } else {
            $("#noResultRow").addClass("d-none");
        }
    }

    $("#liveSearch").on("keyup", applyFilters);
    $("#filterStatus").on("change", applyFilters);

    $("#btnResetFilter").click(function(){
        $("#liveSearch").val('');
        $("#filterStatus").val('all');
        applyFilters();
        showToast("Đã đặt lại bộ lọc tìm kiếm.");
    });
});
</script>
</body>
</html>