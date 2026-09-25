@extends('layouts.admin')

@section('title', 'Quản lý phim')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<div class="container-fluid py-4">
    <h2 class="mb-4">Quản lý Phim (Kỹ thuật Ajax jQuery)</h2>

    
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white fw-bold">
            Thêm phim mới (Kiểm tra Ajax Get khi Blur & Thêm bằng Ajax Post)
        </div>
        <div class="card-body">
            <form id="formThemPhim">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tên phim:</label>
                        <input type="text" class="form-control tenphim" name="title" placeholder="Nhập tên phim rồi click ra ngoài..." required>
                        <div class="ketqua mt-1 fw-bold"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Thể loại:</label>
                        <input type="text" class="form-control" name="genre" placeholder="Hành động, Hài, Tình cảm..." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Thời lượng (phút):</label>
                        <input type="number" class="form-control" name="duration" value="120" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ngày khởi chiếu:</label>
                        <input type="date" class="form-control" name="release_date" required>
                    </div>
                    <div class="col-12">
                        <button type="button" id="btnThemPhimPost" class="btn btn-success">
                            Thêm phim bằng kỹ thuật Ajax Post
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white fw-bold">
            Danh sách Phim hiện có trong CSDL
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0" id="bangPhim">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">Mã phim</th>
                        <th>Tên phim</th>
                        <th>Thể loại</th>
                        <th>Thời lượng</th>
                        <th>Khởi chiếu</th>
                        <th style="width: 120px;" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movies as $movie)
                        <tr id="dong-{{ $movie->id }}">
                            <td>{{ $movie->id }}</td>
                            <td class="fw-bold">{{ $movie->title }}</td>
                            <td>{{ $movie->genre }}</td>
                            <td>{{ $movie->duration }} phút</td>
                            <td>{{ \Carbon\Carbon::parse($movie->release_date)->format('d/m/Y') }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.movies.destroy', $movie->id) }}" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Bạn có chắc muốn xóa phim này?')">Xóa</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // 1. Khi rời chuột khỏi ô tên phim (.blur) -> Gọi Ajax GET kiểm tra trùng tên
    $(".tenphim").blur(function(){
        var tenphim = $(this).val();
        if (tenphim.trim() === '') {
            $(".ketqua").html('');
            return;
        }

        $.get("{{ route('admin.movies.checkName') }}?tenphim=" + encodeURIComponent(tenphim), function(data, status){
            if(status === "success") {
                $(".ketqua").html(data);
            }
        });
    });

    // 2. Khi bấm Thêm phim -> Gọi Ajax POST gửi dữ liệu ngầm
    $("#btnThemPhimPost").click(function(){
        var title = $(".tenphim").val();
        var genre = $("input[name='genre']").val();
        var duration = $("input[name='duration']").val();
        var release_date = $("input[name='release_date']").val();

        if (title.trim() === '' || genre.trim() === '') {
            alert('Vui lòng điền đủ Tên phim và Thể loại!');
            return;
        }

        $.post("{{ route('admin.movies.storeAjax') }}", {
            _token: "{{ csrf_token() }}",
            title: title,
            genre: genre,
            duration: duration,
            release_date: release_date
        }, function(response, status){
            if(status === "success" && response.status === 'success') {
                alert("Thêm phim thành công qua Ajax Post!");

                var m = response.movie;
                var rowMoi = `
                    <tr id="dong-${m.id}" class="table-success">
                        <td>${m.id}</td>
                        <td class="fw-bold">${m.title}</td>
                        <td>${m.genre}</td>
                        <td>${m.duration} phút</td>
                        <td>${m.release_date}</td>
                        <td class="text-center">
                            <a href="/admin/movies/xoa/${m.id}" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                        </td>
                    </tr>
                `;
                $("#bangPhim tbody").prepend(rowMoi);
                $("#formThemPhim")[0].reset();
                $(".ketqua").html('');
            }
        });
    });
});
</script>
@endsection