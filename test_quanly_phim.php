<?php
// 1. KẾT NỐI DATABASE
$conn = new mysqli("localhost", "root", "", "movie_booking");
$conn->set_charset("utf8mb4");

// 2. AJAX: ĐỔI TRẠNG THÁI PHIM TRỰC TIẾP (dang_chieu, sap_chieu, ngung_chieu)
if (isset($_POST['ajax_update_status'])) {
    $id = (int)$_POST['id'];
    $status = trim($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE movies SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    
    echo json_encode(['status' => 'success', 'new_status' => $status]);
    exit;
}

// 3. AJAX: KIỂM TRA TÊN PHIM TRÙNG KHI RỜI CHUỘT
if (isset($_GET['ajax_check'])) {
    $ten = trim($_GET['tenphim']);
    $stmt = $conn->prepare("SELECT id FROM movies WHERE title = ?");
    $stmt->bind_param("s", $ten);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        echo "<small class='text-danger fw-bold'><i class='bi bi-exclamation-circle-fill'></i> Phim này đã có trong hệ thống CINGO!</small>";
    } else {
        echo "<small class='text-success fw-bold'><i class='bi bi-check-circle-fill'></i> Tên phim hợp lệ.</small>";
    }
    exit;
}

// 4. AJAX: THÊM PHIM MỚI (ĐÃ BỎ CỘT POSTER)
if (isset($_POST['ajax_add'])) {
    $title = trim($_POST['title']);
    $genre = trim($_POST['genre']);
    $duration = (int)$_POST['duration'];
    $release_date = $_POST['release_date'];
    $status = trim($_POST['status'] ?? 'dang_chieu');
    $description = trim($_POST['description'] ?? '');

    $stmt = $conn->prepare("INSERT INTO movies (title, genre, duration, release_date, description, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $title, $genre, $duration, $release_date, $description, $status);
    $stmt->execute();
    $id = $stmt->insert_id;

    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => $id,
            'title' => htmlspecialchars($title),
            'genre' => htmlspecialchars($genre),
            'duration' => $duration,
            'release_date' => date('d/m/Y', strtotime($release_date)),
            'status' => $status
        ]
    ]);
    exit;
}

// 5. AJAX: XÓA PHIM
if (isset($_POST['ajax_delete'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM movies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['status' => 'success']);
    exit;
}

// 6. THỐNG KÊ KINH DOANH
$total_movies = $conn->query("SELECT COUNT(*) as total FROM movies")->fetch_assoc()['total'];
$active_movies = $conn->query("SELECT COUNT(*) as total FROM movies WHERE status = 'dang_chieu'")->fetch_assoc()['total'];
$upcoming_movies = $conn->query("SELECT COUNT(*) as total FROM movies WHERE status = 'sap_chieu'")->fetch_assoc()['total'];
$ended_movies = $conn->query("SELECT COUNT(*) as total FROM movies WHERE status = 'ngung_chieu'")->fetch_assoc()['total'];

$movies = $conn->query("SELECT * FROM movies ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CINGO CINEMA | Hệ Thống Quản Trị Rạp</title>
    
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
        
        .select-status {
            font-size: 0.82rem;
            font-weight: 600;
            border-radius: 20px;
            padding: 4px 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .status-dang_chieu { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .status-sap_chieu  { background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }
        .status-ngung_chieu { background-color: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>

<!-- Thông báo Toast -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
    <div id="liveToast" class="toast align-items-center text-white bg-dark border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">
                <i class="bi bi-check-circle-fill text-success me-2"></i> Cập nhật thành công!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR: THƯƠNG HIỆU CINGO -->
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
                    <a class="nav-link active" href="test_quanly_phim.php"><i class="bi bi-film me-2"></i> Quản lý Phim</a>
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
            
            <!-- HEADER: ĐÃ XÓA HOÀN TOÀN KHÚC ADMIN HUY -->
            <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                <div>
                    <h3 class="fw-bold mb-1 text-dark">Quản Lý Danh Mục Phim</h3>
                    <p class="text-muted mb-0 small">Hệ thống điều phối phim cụm rạp CINGO</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary shadow-sm" id="btnOpenModal">
                        <i class="bi bi-plus-lg me-1"></i> Thêm Phim Mới
                    </button>
                </div>
            </div>

            <!-- 4 THẺ THỐNG KÊ -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-metric p-3 bg-white">
                        <small class="text-muted fw-bold">TỔNG PHIM HỆ THỐNG</small>
                        <h3 class="fw-bold text-dark mb-0 mt-1" id="statTotal"><?php echo $total_movies; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-metric p-3 bg-white border-start border-success border-4">
                        <small class="text-success fw-bold">🟢 ĐANG CHIẾU</small>
                        <h3 class="fw-bold text-success mb-0 mt-1" id="statActive"><?php echo $active_movies; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-metric p-3 bg-white border-start border-warning border-4">
                        <small class="text-warning fw-bold">🟡 SẮP CHIẾU</small>
                        <h3 class="fw-bold text-warning mb-0 mt-1" id="statUpcoming"><?php echo $upcoming_movies; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-metric p-3 bg-white border-start border-secondary border-4">
                        <small class="text-secondary fw-bold">⚪ HẾT PHIM / NGỪNG</small>
                        <h3 class="fw-bold text-secondary mb-0 mt-1" id="statEnded"><?php echo $ended_movies; ?></h3>
                    </div>
                </div>
            </div>

            <!-- BẢNG DANH SÁCH: ĐÃ BỎ HẲN CỘT POSTER -->
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-collection-play me-2"></i>Danh Sách Phim Cụm Rạp CINGO</h6>
                    <div style="width: 320px;">
                        <input type="text" id="liveSearch" class="form-control form-control-sm" placeholder="🔍 Tìm nhanh tên phim hoặc thể loại...">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="bangPhim">
                            <thead>
                                <tr>
                                    <th class="ps-3" style="width: 70px;">Mã</th>
                                    <th>Tên Phim Chiếu Rạp</th>
                                    <th>Thể Loại</th>
                                    <th>Thời Lượng</th>
                                    <th>Ngày Khởi Chiếu</th>
                                    <th style="width: 170px;">Trạng Thái</th>
                                    <th class="text-center" style="width: 90px;">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $movies->fetch_assoc()): 
                                    $st = $row['status'];
                                ?>
                                <tr id="dong-<?php echo $row['id']; ?>" data-status="<?php echo $st; ?>">
                                    <td class="ps-3 fw-bold text-muted">#<?php echo $row['id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark cell-title mb-1">
                                            <i class="bi bi-film text-primary me-2"></i><?php echo htmlspecialchars($row['title']); ?>
                                        </div>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">2D Digital</span>
                                    </td>
                                    <td><span class="badge bg-secondary-subtle text-secondary cell-genre"><?php echo htmlspecialchars($row['genre']); ?></span></td>
                                    <td><i class="bi bi-clock me-1 text-muted"></i><?php echo $row['duration']; ?> phút</td>
                                    <td><?php echo date('d/m/Y', strtotime($row['release_date'])); ?></td>
                                    
                                    <!-- CỘT CHỈNH TRẠNG THÁI -->
                                    <td>
                                        <select class="form-select form-select-sm select-status status-<?php echo $st; ?>" data-id="<?php echo $row['id']; ?>">
                                            <option value="dang_chieu" <?php if($st == 'dang_chieu') echo 'selected'; ?>>🟢 Đang Chiếu</option>
                                            <option value="sap_chieu" <?php if($st == 'sap_chieu') echo 'selected'; ?>>🟡 Sắp Chiếu</option>
                                            <option value="ngung_chieu" <?php if($st == 'ngung_chieu') echo 'selected'; ?>>⚪ Hết Phim</option>
                                        </select>
                                    </td>

                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-danger btn-xoa" data-id="<?php echo $row['id']; ?>" title="Xóa phim">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- MODAL THÊM PHIM MỚI -->
<div class="modal fade" id="modalThemPhim" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6"><i class="bi bi-film me-2"></i>Thêm Phim Mới Vào CINGO</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formThemPhim">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Tên phim <span class="text-danger">*</span></label>
                        <input type="text" id="inputTenPhim" name="title" class="form-control" placeholder="Ví dụ: Avatar 3..." required>
                        <div id="msgCheckName" class="mt-1"></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Thể loại <span class="text-danger">*</span></label>
                            <input type="text" name="genre" class="form-control" placeholder="Hành động, Hài..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Trạng thái phát hành <span class="text-danger">*</span></label>
                            <select name="status" class="form-select">
                                <option value="dang_chieu" selected>🟢 Đang Chiếu</option>
                                <option value="sap_chieu">🟡 Sắp Chiếu</option>
                                <option value="ngung_chieu">⚪ Hết Phim</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Thời lượng (phút) <span class="text-danger">*</span></label>
                            <input type="number" name="duration" class="form-control" value="120" required min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Ngày khởi chiếu <span class="text-danger">*</span></label>
                            <input type="date" name="release_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small">Mô tả tóm tắt</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Tóm tắt ngắn nội dung phim..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnSubmitPhim" class="btn btn-primary btn-sm">
                        <i class="bi bi-save me-1"></i> Lưu Vào CSDL
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    
    var modalThemPhim = new bootstrap.Modal(document.getElementById('modalThemPhim'));
    var toast = new bootstrap.Toast(document.getElementById('liveToast'));
    
    function showToast(msg) {
        $("#toastMessage").html("<i class='bi bi-check-circle-fill text-success me-2'></i> " + msg);
        toast.show();
    }

    $("#btnOpenModal").click(function(){
        modalThemPhim.show();
    });

    // 1. AJAX: THAY ĐỔI TRẠNG THÁI TRỰC TIẾP
    $(document).on("change", ".select-status", function(){
        var selectEl = $(this);
        var id = selectEl.data("id");
        var newStatus = selectEl.val();
        var tr = $("#dong-" + id);
        var oldStatus = tr.attr("data-status");

        $.post("test_quanly_phim.php", {
            ajax_update_status: 1,
            id: id,
            status: newStatus
        }, function(res){
            var response = JSON.parse(res);
            if(response.status === 'success') {
                selectEl.removeClass("status-dang_chieu status-sap_chieu status-ngung_chieu").addClass("status-" + newStatus);
                tr.attr("data-status", newStatus);

                capNhatThongKe(oldStatus, newStatus);

                var tenTrangThai = (newStatus === 'dang_chieu') ? "Đang Chiếu" : ((newStatus === 'sap_chieu') ? "Sắp Chiếu" : "Hết Phim");
                showToast("Đã cập nhật phim #" + id + " sang: <strong>" + tenTrangThai + "</strong>");
            }
        });
    });

    function capNhatThongKe(oldSt, newSt) {
        if(oldSt === newSt) return;
        
        function getEl(st) {
            if(st === 'dang_chieu') return $("#statActive");
            if(st === 'sap_chieu')  return $("#statUpcoming");
            if(st === 'ngung_chieu') return $("#statEnded");
        }

        var oldEl = getEl(oldSt);
        var newEl = getEl(newSt);

        if(oldEl) oldEl.text(Math.max(0, parseInt(oldEl.text()) - 1));
        if(newEl) newEl.text(parseInt(newEl.text()) + 1);
    }

    // 2. AJAX KIỂM TRA TÊN PHIM TRÙNG
    $("#inputTenPhim").blur(function(){
        var tenphim = $(this).val();
        if(tenphim.trim() === '') {
            $("#msgCheckName").html('');
            return;
        }

        $.get("test_quanly_phim.php", {
            ajax_check: 1,
            tenphim: tenphim
        }, function(response){
            $("#msgCheckName").html(response);
        });
    });

    // 3. AJAX THÊM PHIM MỚI
    $("#formThemPhim").submit(function(e){
        e.preventDefault();
        var formData = $(this).serialize() + '&ajax_add=1';

        $.post("test_quanly_phim.php", formData, function(res){
            var response = JSON.parse(res);
            if(response.status === 'success') {
                var m = response.data;
                modalThemPhim.hide();
                $("#formThemPhim")[0].reset();
                $("#msgCheckName").html('');

                var opt1 = (m.status === 'dang_chieu') ? 'selected' : '';
                var opt2 = (m.status === 'sap_chieu') ? 'selected' : '';
                var opt0 = (m.status === 'ngung_chieu') ? 'selected' : '';

                var dongMoi = `
                    <tr id="dong-${m.id}" data-status="${m.status}" class="table-success">
                        <td class="ps-3 fw-bold text-muted">#${m.id}</td>
                        <td>
                            <div class="fw-bold text-dark cell-title mb-1">
                                <i class="bi bi-film text-primary me-2"></i>${m.title}
                            </div>
                            <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">2D Digital</span>
                        </td>
                        <td><span class="badge bg-secondary-subtle text-secondary cell-genre">${m.genre}</span></td>
                        <td><i class="bi bi-clock me-1 text-muted"></i>${m.duration} phút</td>
                        <td>${m.release_date}</td>
                        <td>
                            <select class="form-select form-select-sm select-status status-${m.status}" data-id="${m.id}">
                                <option value="dang_chieu" ${opt1}>🟢 Đang Chiếu</option>
                                <option value="sap_chieu" ${opt2}>🟡 Sắp Chiếu</option>
                                <option value="ngung_chieu" ${opt0}>⚪ Hết Phim</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" data-id="${m.id}" title="Xóa phim">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $("#bangPhim tbody").prepend(dongMoi);

                $("#statTotal").text(parseInt($("#statTotal").text()) + 1);
                if(m.status === 'dang_chieu') $("#statActive").text(parseInt($("#statActive").text()) + 1);
                if(m.status === 'sap_chieu')  $("#statUpcoming").text(parseInt($("#statUpcoming").text()) + 1);
                if(m.status === 'ngung_chieu') $("#statEnded").text(parseInt($("#statEnded").text()) + 1);

                showToast("Thêm phim <strong>" + m.title + "</strong> thành công!");
            }
        });
    });

    // 4. AJAX XÓA PHIM
    $(document).on("click", ".btn-xoa", function(){
        var id = $(this).data("id");
        var st = $("#dong-" + id).attr("data-status");

        if(confirm("Bạn có chắc chắn muốn xóa phim #" + id + " này khỏi hệ thống CINGO?")) {
            $.post("test_quanly_phim.php", {
                ajax_delete: 1,
                id: id
            }, function(res){
                var response = JSON.parse(res);
                if(response.status === 'success') {
                    $("#dong-" + id).fadeOut(300, function(){ 
                        $(this).remove(); 
                        $("#statTotal").text(Math.max(0, parseInt($("#statTotal").text()) - 1));
                        
                        if(st === 'dang_chieu') $("#statActive").text(Math.max(0, parseInt($("#statActive").text()) - 1));
                        if(st === 'sap_chieu')  $("#statUpcoming").text(Math.max(0, parseInt($("#statUpcoming").text()) - 1));
                        if(st === 'ngung_chieu') $("#statEnded").text(Math.max(0, parseInt($("#statEnded").text()) - 1));
                    });
                    showToast("Đã xóa phim #" + id + " khỏi hệ thống.");
                }
            });
        }
    });

    // 5. LIVE SEARCH
    $("#liveSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#bangPhim tbody tr").filter(function() {
            var title = $(this).find(".cell-title").text().toLowerCase();
            var genre = $(this).find(".cell-genre").text().toLowerCase();
            $(this).toggle(title.indexOf(value) > -1 || genre.indexOf(value) > -1);
        });
    });

});
</script>
</body>
</html>