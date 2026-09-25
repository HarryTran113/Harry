<?php
// 1. KẾT NỐI DATABASE
$conn = new mysqli("localhost", "root", "", "movie_booking");
$conn->set_charset("utf8mb4");

// 2. AJAX: THÊM SUẤT CHIẾU MỚI
if (isset($_POST['ajax_add_showtime'])) {
    $movie_id = (int)$_POST['movie_id'];
    $room_id = (int)$_POST['room_id'];
    $start_time = $_POST['start_time'];
    $price = (float)$_POST['price'];

    $stmt = $conn->prepare("INSERT INTO showtimes (movie_id, room_id, start_time, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisd", $movie_id, $room_id, $start_time, $price);
    $stmt->execute();
    $id = $stmt->insert_id;

    $sql = "SELECT s.*, m.title as movie_title, m.duration, m.status as movie_status, r.name as room_name 
            FROM showtimes s
            JOIN movies m ON s.movie_id = m.id
            JOIN rooms r ON s.room_id = r.id
            WHERE s.id = $id";
    $item = $conn->query($sql)->fetch_assoc();

    $raw_date = date('Y-m-d', strtotime($item['start_time']));
    $is_today = ($raw_date === date('Y-m-d')) ? 1 : 0;
    $is_future = (strtotime($item['start_time']) > time()) ? 1 : 0;

    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => $item['id'],
            'movie_title' => htmlspecialchars($item['movie_title']),
            'duration' => $item['duration'],
            'room_name' => htmlspecialchars($item['room_name']),
            'start_time' => date('H:i - d/m/Y', strtotime($item['start_time'])),
            'raw_date' => $raw_date,
            'is_today' => $is_today,
            'is_future' => $is_future,
            'price' => number_format($item['price'], 0, ',', '.') . ' đ',
            'movie_status' => $item['movie_status']
        ]
    ]);
    exit;
}

// 3. AJAX: XÓA SUẤT CHIẾU
if (isset($_POST['ajax_delete_showtime'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM showtimes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['status' => 'success']);
    exit;
}

// 4. TRUY VẤN DỮ LIỆU
$movies_list = $conn->query("SELECT id, title, duration FROM movies WHERE status != 'ngung_chieu' ORDER BY title ASC");
$rooms_list = $conn->query("SELECT id, name FROM rooms ORDER BY id ASC");

$total_showtimes = $conn->query("SELECT COUNT(*) as total FROM showtimes")->fetch_assoc()['total'];
$today_showtimes = $conn->query("SELECT COUNT(*) as total FROM showtimes WHERE DATE(start_time) = CURDATE()")->fetch_assoc()['total'];
$future_showtimes = $conn->query("SELECT COUNT(*) as total FROM showtimes WHERE start_time > NOW()")->fetch_assoc()['total'];

$showtimes = $conn->query("
    SELECT s.*, m.title as movie_title, m.duration, m.status as movie_status, r.name as room_name 
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.id
    JOIN rooms r ON s.room_id = r.id
    ORDER BY s.start_time DESC
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CINGO | Quản Lý Lịch Chiếu Rạp</title>
    
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
    </style>
</head>
<body>

<!-- Thông báo Toast -->
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
                    <a class="nav-link active" href="test_quanly_suatchieu.php"><i class="bi bi-calendar3 me-2"></i> Lịch chiếu rạp</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="test_quanly_donve.php"><i class="bi bi-ticket-detailed me-2"></i> Đơn vé đặt</a>
                </li>
            </ul>
        </nav>

        <!-- MAIN -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                <div>
                    <h3 class="fw-bold mb-1 text-dark">Lịch Chiếu & Suất Chiếu</h3>
                    <p class="text-muted mb-0 small">Sắp xếp khung giờ chiếu, phòng chiếu và giá vé tại rạp CINGO</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-primary shadow-sm" id="btnOpenModal">
                        <i class="bi bi-plus-circle me-1"></i> Tạo Suất Chiếu Mới
                    </button>
                </div>
            </div>

            <!-- 3 THẺ THỐNG KÊ (ĐÃ GẮN ID ĐỂ TỰ ĐỘNG NHẢY SỐ KHI LỌC) -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card card-metric p-3 bg-white">
                        <small class="text-muted fw-bold">TỔNG SUẤT CHIẾU HIỆN CÓ</small>
                        <h3 class="fw-bold text-dark mb-0 mt-1" id="statTotal"><?php echo $total_showtimes; ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-metric p-3 bg-white border-start border-primary border-4">
                        <small class="text-primary fw-bold">📅 SUẤT CHIẾU HÔM NAY</small>
                        <h3 class="fw-bold text-primary mb-0 mt-1" id="statToday"><?php echo $today_showtimes; ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-metric p-3 bg-white border-start border-success border-4">
                        <small class="text-success fw-bold">⏳ SUẤT SẮP TỚI</small>
                        <h3 class="fw-bold text-success mb-0 mt-1" id="statFuture"><?php echo $future_showtimes; ?></h3>
                    </div>
                </div>
            </div>

            <!-- BẢNG DANH SÁCH SUẤT CHIẾU VỚI BỘ LỌC ĐA NĂNG -->
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-funnel me-1"></i>Lịch Chiếu Cụm Rạp</h6>
                        </div>
                        
                        <!-- 1. Lọc theo Ngày -->
                        <div class="col-md-3 col-sm-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" id="filterDate" class="form-control" title="Chọn ngày để lọc suất chiếu">
                            </div>
                        </div>

                        <!-- 2. Lọc theo Trạng Thái Phim -->
                        <div class="col-md-3 col-sm-6">
                            <select id="filterStatus" class="form-select form-select-sm">
                                <option value="all">-- Tất cả trạng thái phim --</option>
                                <option value="dang_chieu">🟢 Đang Chiếu</option>
                                <option value="sap_chieu">🟡 Sắp Chiếu</option>
                                <option value="ngung_chieu">⚪ Ngừng Chiếu</option>
                            </select>
                        </div>

                        <!-- 3. Tìm theo từ khóa & Nút Reset -->
                        <div class="col-md-3">
                            <div class="input-group input-group-sm">
                                <input type="text" id="liveSearch" class="form-control" placeholder="🔍 Tên phim, phòng...">
                                <button class="btn btn-outline-secondary" type="button" id="btnResetFilter" title="Xóa bộ lọc">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="bangSuatChieu">
                            <thead>
                                <tr>
                                    <th class="ps-3" style="width: 70px;">Mã</th>
                                    <th>Phim Chiếu Rạp</th>
                                    <th>Phòng Chiếu</th>
                                    <th>Thời Gian Bắt Đầu</th>
                                    <th>Giá Vé Cơ Bản</th>
                                    <th>Trạng Thái Phim</th>
                                    <th class="text-center" style="width: 90px;">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $showtimes->fetch_assoc()): 
                                    $st = $row['movie_status'];
                                    $raw_date = date('Y-m-d', strtotime($row['start_time']));
                                    $is_today = ($raw_date === date('Y-m-d')) ? 1 : 0;
                                    $is_future = (strtotime($row['start_time']) > time()) ? 1 : 0;
                                ?>
                                <!-- GẮN THÊM data-today VÀ data-future ĐỂ JAVASCRIPT ĐẾM TỨC THÌ -->
                                <tr id="dong-<?php echo $row['id']; ?>" 
                                    data-date="<?php echo $raw_date; ?>" 
                                    data-status="<?php echo htmlspecialchars($st); ?>"
                                    data-today="<?php echo $is_today; ?>"
                                    data-future="<?php echo $is_future; ?>">
                                    
                                    <td class="ps-3 fw-bold text-muted">#<?php echo $row['id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark cell-movie">
                                            <i class="bi bi-film text-primary me-2"></i><?php echo htmlspecialchars($row['movie_title']); ?>
                                        </div>
                                        <small class="text-muted ps-4"><i class="bi bi-clock me-1"></i><?php echo $row['duration']; ?> phút</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border cell-room fw-bold">
                                            <i class="bi bi-door-open me-1"></i><?php echo htmlspecialchars($row['room_name']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary">
                                            <i class="bi bi-calendar-check me-1"></i><?php echo date('H:i - d/m/Y', strtotime($row['start_time'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-danger"><?php echo number_format($row['price'], 0, ',', '.'); ?> đ</span>
                                    </td>
                                    <td>
                                        <?php if($st == 'dang_chieu'): ?>
                                            <span class="badge bg-success-subtle text-success px-2 py-1">🟢 Đang Chiếu</span>
                                        <?php elseif($st == 'sap_chieu'): ?>
                                            <span class="badge bg-warning-subtle text-warning px-2 py-1">🟡 Sắp Chiếu</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1">⚪ Ngừng Chiếu</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-danger btn-xoa" data-id="<?php echo $row['id']; ?>" title="Xóa suất chiếu">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                
                                <tr id="noResultRow" class="d-none">
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-search fs-3 d-block mb-1"></i>
                                        Không tìm thấy suất chiếu nào khớp với bộ lọc đã chọn.
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

<!-- MODAL TẠO SUẤT CHIẾU MỚI -->
<div class="modal fade" id="modalThemSuatChieu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6"><i class="bi bi-calendar-plus me-2"></i>Thêm Suất Chiếu CINGO</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formThemSuatChieu">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Chọn phim <span class="text-danger">*</span></label>
                        <select name="movie_id" class="form-select" required>
                            <option value="">-- Bấm chọn bộ phim --</option>
                            <?php while($m = $movies_list->fetch_assoc()): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['title']); ?> (<?php echo $m['duration']; ?> phút)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Phòng chiếu <span class="text-danger">*</span></label>
                        <select name="room_id" class="form-select" required>
                            <?php while($r = $rooms_list->fetch_assoc()): ?>
                                <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold small">Thời gian bắt đầu <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="start_time" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime('+2 hours')); ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">Giá vé (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" value="85000" step="5000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnSubmitSuatChieu" class="btn btn-primary btn-sm">
                        <i class="bi bi-save me-1"></i> Lưu Lịch Chiếu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    var modalThem = new bootstrap.Modal(document.getElementById('modalThemSuatChieu'));
    var toast = new bootstrap.Toast(document.getElementById('liveToast'));

    function showToast(msg) {
        $("#toastMessage").html("<i class='bi bi-check-circle-fill text-success me-2'></i> " + msg);
        toast.show();
    }

    $("#btnOpenModal").click(function(){
        modalThem.show();
    });

    // 1. AJAX THÊM SUẤT CHIẾU
    $("#formThemSuatChieu").submit(function(e){
        e.preventDefault();
        var formData = $(this).serialize() + '&ajax_add_showtime=1';

        $.post("test_quanly_suatchieu.php", formData, function(res){
            var response = JSON.parse(res);
            if(response.status === 'success') {
                var s = response.data;
                modalThem.hide();
                $("#formThemSuatChieu")[0].reset();

                var badgeStatus = '<span class="badge bg-success-subtle text-success px-2 py-1">🟢 Đang Chiếu</span>';
                if(s.movie_status === 'sap_chieu') {
                    badgeStatus = '<span class="badge bg-warning-subtle text-warning px-2 py-1">🟡 Sắp Chiếu</span>';
                } else if(s.movie_status === 'ngung_chieu') {
                    badgeStatus = '<span class="badge bg-secondary-subtle text-secondary px-2 py-1">⚪ Ngừng Chiếu</span>';
                }

                var dongMoi = `
                    <tr id="dong-${s.id}" data-date="${s.raw_date}" data-status="${s.movie_status}" data-today="${s.is_today}" data-future="${s.is_future}" class="table-success">
                        <td class="ps-3 fw-bold text-muted">#${s.id}</td>
                        <td>
                            <div class="fw-bold text-dark cell-movie">
                                <i class="bi bi-film text-primary me-2"></i>${s.movie_title}
                            </div>
                            <small class="text-muted ps-4"><i class="bi bi-clock me-1"></i>${s.duration} phút</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border cell-room fw-bold">
                                <i class="bi bi-door-open me-1"></i>${s.room_name}
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-primary"><i class="bi bi-calendar-check me-1"></i>${s.start_time}</div>
                        </td>
                        <td>
                            <span class="fw-bold text-danger">${s.price}</span>
                        </td>
                        <td>
                            ${badgeStatus}
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" data-id="${s.id}" title="Xóa suất chiếu">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $("#bangSuatChieu tbody").prepend(dongMoi);
                showToast("Đã tạo suất chiếu mới thành công!");
                applyAllFilters();
            }
        });
    });

    // 2. AJAX XÓA SUẤT CHIẾU
    $(document).on("click", ".btn-xoa", function(){
        var id = $(this).data("id");
        if(confirm("Bạn có chắc chắn muốn hủy suất chiếu #" + id + " này?")) {
            $.post("test_quanly_suatchieu.php", {
                ajax_delete_showtime: 1,
                id: id
            }, function(res){
                var response = JSON.parse(res);
                if(response.status === 'success') {
                    $("#dong-" + id).fadeOut(300, function(){
                        $(this).remove();
                        applyAllFilters(); // TỰ ĐỘNG TÍNH LẠI CẢ 3 Ô THỐNG KÊ
                    });
                    showToast("Đã xóa suất chiếu thành công!");
                }
            });
        }
    });

    // 3. THUẬT TOÁN LỌC KẾT HỢP & TỰ ĐỘNG TÍNH LẠI 3 Ô THỐNG KÊ TRÊN ĐẦU
    function applyAllFilters() {
        var keyword = $("#liveSearch").val().toLowerCase().trim();
        var selectedDate = $("#filterDate").val(); // YYYY-MM-DD
        var selectedStatus = $("#filterStatus").val(); // all, dang_chieu, sap_chieu, ngung_chieu

        var countTotal = 0;
        var countToday = 0;
        var countFuture = 0;

        $("#bangSuatChieu tbody tr:not(#noResultRow)").each(function(){
            var row = $(this);
            var movie = row.find(".cell-movie").text().toLowerCase();
            var room = row.find(".cell-room").text().toLowerCase();
            var rowDate = row.attr("data-date");
            var rowStatus = row.attr("data-status");
            var isToday = (row.attr("data-today") == "1");
            var isFuture = (row.attr("data-future") == "1");

            var matchKeyword = (keyword === '' || movie.indexOf(keyword) > -1 || room.indexOf(keyword) > -1);
            var matchDate = (selectedDate === '' || rowDate === selectedDate);
            var matchStatus = (selectedStatus === 'all' || rowStatus === selectedStatus);

            if (matchKeyword && matchDate && matchStatus) {
                row.show();
                countTotal++;
                if (isToday) countToday++;
                if (isFuture) countFuture++;
            } else {
                row.hide();
            }
        });

        // CẬP NHẬT TRỰC TIẾP 3 Ô THỐNG KÊ TRÊN GIAO DIỆN THEO KẾT QUẢ ĐÃ LỌC
        $("#statTotal").text(countTotal);
        $("#statToday").text(countToday);
        $("#statFuture").text(countFuture);

        if (countTotal === 0) {
            $("#noResultRow").removeClass("d-none");
        } else {
            $("#noResultRow").addClass("d-none");
        }
    }

    $("#liveSearch").on("keyup", applyAllFilters);
    $("#filterDate").on("change", applyAllFilters);
    $("#filterStatus").on("change", applyAllFilters);

    // 4. NÚT RESET
    $("#btnResetFilter").click(function(){
        $("#liveSearch").val('');
        $("#filterDate").val('');
        $("#filterStatus").val('all');
        applyAllFilters();
        showToast("Đã đặt lại bộ lọc danh sách.");
    });
});
</script>
</body>
</html>