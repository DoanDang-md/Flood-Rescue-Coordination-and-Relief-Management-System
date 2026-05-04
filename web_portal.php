<?php
session_start();

// 1. KIỂM TRA SESSION VÀ TỰ ĐỘNG CHUYỂN HƯỚNG
if (isset($_SESSION['user_id']) && isset($_SESSION['role_name'])) {
    $role = $_SESSION['role_name'];
    
    switch ($role) {
        case 'Admin':
            header("Location: views/admin/dashboard.php");
            exit;
        case 'Dispatcher':
            header("Location: views/dispatcher/dashboard.php"); 
            exit;
        case 'Rescue Team':
            header("Location: views/rescuer/dashboard.php");
            exit;
        // Bỏ qua Citizen vì Citizen không cần đăng nhập
    }
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Hệ thống Điều phối Cứu hộ & Quản lý Cứu trợ</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />

    <style>
      /* Nền Gradient xanh chuyên nghiệp */
      body {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        font-family: Arial, sans-serif;
      }
      /* Hiệu ứng thẻ nổi */
      .card-custom {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        border: none;
      }
      /* Cột màu đỏ cho người dân */
      .bg-citizen {
        background: #e74a3b;
        color: white;
        padding: 50px 40px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
      }
      /* Cột màu trắng cho Đăng nhập */
      .bg-login {
        background: white;
        padding: 50px 40px;
        height: 100%;
      }
      .form-control {
        border-radius: 10px;
        padding: 25px 15px;
      }
      .btn-rounded {
        border-radius: 10px;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
          <div class="card card-custom">
            <div class="row no-gutters">
              
              <!-- CỘT NGƯỜI DÂN -->
              <div class="col-lg-6 d-flex">
                <div class="bg-citizen text-center w-100">
                  <i class="fas fa-life-ring fa-5x mb-4 text-white"></i>
                  <h2 class="font-weight-bold mb-3">BẠN CẦN GIÚP ĐỠ?</h2>
                  <p class="mb-4 font-weight-light" style="font-size: 1.1rem">
                    Cổng thông tin tiếp nhận yêu cầu cứu hộ khẩn cấp. Chúng tôi
                    luôn sẵn sàng hỗ trợ bạn 24/7.
                  </p>

                  <a href="views/citizen/create_request.php" class="btn btn-light btn-lg btn-block btn-rounded font-weight-bold text-danger mb-3 shadow-sm py-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i> BÁO CÁO KHẨN CẤP
                  </a>

                  <a href="views/citizen/track_request.php" class="btn btn-outline-light btn-lg btn-block btn-rounded font-weight-bold py-3">
                    <i class="fas fa-search mr-2"></i> Tra cứu yêu cầu đã gửi
                  </a>
                </div>
              </div>

              <!-- CỘT ĐĂNG NHẬP -->
              <div class="col-lg-6 d-flex">
                <div class="bg-login w-100">
                  <div class="text-center mb-4">
                    <div class="text-primary mb-3">
                      <i class="fas fa-shield-alt fa-3x"></i>
                    </div>
                    <h4 class="text-gray-900 font-weight-bold">
                      ĐĂNG NHẬP HỆ THỐNG
                    </h4>
                    <p class="text-muted small">
                      Dành cho Admin, Điều phối viên & Đội cứu hộ
                    </p>
                  </div>

                  <!-- 2. HIỂN THỊ THÔNG BÁO LỖI NẾU ĐĂNG NHẬP SAI HOẶC ĐÃ ĐĂNG XUẤT -->
                  <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-danger text-center small" style="border-radius: 10px;">
                      <i class="fas fa-exclamation-circle mr-1"></i> Sai tài khoản hoặc mật khẩu!
                    </div>
                  <?php endif; ?>
                  
                  <?php if(isset($_GET['logout'])): ?>
                    <div class="alert alert-success text-center small" style="border-radius: 10px;">
                      <i class="fas fa-check-circle mr-1"></i> Đăng xuất thành công!
                    </div>
                  <?php endif; ?>

                  <form action="api/login.php" method="POST">
                    <div class="form-group">
                      <label class="font-weight-bold text-gray-700">Tên đăng nhập</label>
                      <input type="text" class="form-control" name="username" required placeholder="Nhập tài khoản của bạn..." />
                    </div>
                    <div class="form-group mt-4">
                      <label class="font-weight-bold text-gray-700">Mật khẩu</label>
                      <input type="password" class="form-control" name="password" required placeholder="Nhập mật khẩu..." />
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block btn-rounded font-weight-bold mt-5 shadow-sm py-3">
                      <i class="fas fa-sign-in-alt mr-2"></i> ĐĂNG NHẬP
                    </button>
                  </form>

                  <hr class="mt-5 mb-4" />
                  <div class="text-center">
                    <small class="text-muted">Hệ thống Quản lý và Điều phối Cứu hộ Lũ lụt &copy; 2026</small>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
