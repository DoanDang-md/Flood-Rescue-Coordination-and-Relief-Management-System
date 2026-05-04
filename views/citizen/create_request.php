<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gửi Yêu Cầu Cứu Hộ Khẩn Cấp</title>
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    />

    <style>
      body {
        background-color: #f8f9fc;
        font-family: Arial, sans-serif;
      }
      .form-container {
        max-width: 900px;
        margin: 40px auto;
        background: #fff;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border-top: 5px solid #e74a3b;
      }
      #map {
        height: 400px;
        border-radius: 8px;
        border: 2px solid #e3e6f0;
        z-index: 1;
      }
      input,
      select,
      textarea {
        border-radius: 8px !important;
      }
      .btn-locate {
        margin-bottom: 10px;
        border-radius: 8px;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="form-container">
        <div class="text-center mb-4">
          <i class="fas fa-life-ring fa-3x text-danger mb-3"></i>
          <h2 class="font-weight-bold text-gray-800">
            YÊU CẦU CỨU HỘ KHẨN CẤP
          </h2>
          <p class="text-muted">Hệ thống Điều phối Cứu hộ trực tuyến</p>
        </div>

        <form
          action="../../api/citizen/create_request.php"
          method="POST"
          id="rescueForm"
        >
          <div class="row">
            <div class="col-md-5">
              <div class="form-group">
                <label class="font-weight-bold">Họ và Tên *</label>
                <input
                  type="text"
                  class="form-control"
                  name="citizen_name"
                  required
                  placeholder="Tên người báo tin..."
                />
              </div>
              <div class="form-group">
                <label class="font-weight-bold">Số điện thoại *</label>
                <input
                  type="tel"
                  class="form-control"
                  name="phone"
                  required
                  placeholder="Nhập SĐT để liên hệ..."
                />
              </div>
              <div class="form-group">
                <label class="font-weight-bold">Địa chỉ / Ghi chú *</label>
                <textarea
                  class="form-control"
                  name="address_note"
                  rows="2"
                  required
                  placeholder="Mô tả địa điểm..."
                ></textarea>
              </div>
              <div class="form-group">
                <label class="font-weight-bold">Mức độ nguy hiểm *</label>
                <select class="form-control" name="severity" required>
                  <option value="Critical">
                    🔴 Khẩn cấp (Nguy hiểm tính mạng)
                  </option>
                  <option value="High">🟠 Cao (Nước ngập sâu, cô lập)</option>
                  <option value="Medium" selected>
                    🟡 Trung bình (Cần sơ tán)
                  </option>
                  <option value="Low">🟢 Thấp (Cần hỗ trợ nhẹ)</option>
                </select>
              </div>
              <div class="form-group">
                <label class="font-weight-bold">Tình trạng hiện tại</label>
                <textarea
                  class="form-control"
                  name="description"
                  rows="2"
                  placeholder="Số người mắc kẹt, tình hình nước..."
                ></textarea>
              </div>
            </div>

            <div class="col-md-7">
              <div class="d-flex justify-content-between align-items-end mb-2">
                <label class="font-weight-bold text-danger mb-0">
                  <i class="fas fa-map-marker-alt"></i> Vị trí của bạn *
                </label>
                <button
                  type="button"
                  class="btn btn-sm btn-info btn-locate"
                  id="btnAutoLocate"
                >
                  <i class="fas fa-crosshairs"></i> Lấy tọa độ hiện tại
                </button>
              </div>

              <div id="map"></div>

              <input type="hidden" name="latitude" id="lat_input" required />
              <input type="hidden" name="longitude" id="lng_input" required />

              <div
                id="coord-alert"
                class="alert alert-danger mt-2 py-2 text-center font-weight-bold"
              >
                Chưa chọn vị trí! Vui lòng click vào bản đồ hoặc bấm Lấy tọa độ.
              </div>
            </div>
          </div>

          <hr class="mt-4" />
          <button
            type="submit"
            class="btn btn-danger btn-lg btn-block font-weight-bold shadow-sm"
          >
            <i class="fas fa-paper-plane mr-2"></i> GỬI YÊU CẦU CỨU HỘ
          </button>

          <div class="text-center mt-4">
            <a
              href="track_request.php"
              class="text-primary font-weight-bold mr-3"
              ><i class="fas fa-search"></i> Tra cứu yêu cầu đã gửi</a
            >
            <a href="../../index.html" class="text-muted"
              ><i class="fas fa-home"></i> Trang chủ</a
            >
          </div>
        </form>
      </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        // Khởi tạo bản đồ ở trung tâm mặc định (Ví dụ: Miền Trung)
        var map = L.map("map").setView([16.4637, 107.5905], 6);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(
          map,
        );

        var currentMarker = null;

        // Hàm cập nhật tọa độ và giao diện
        function updateLocation(lat, lng) {
          if (currentMarker !== null) map.removeLayer(currentMarker);

          currentMarker = L.marker([lat, lng])
            .addTo(map)
            .bindPopup("Vị trí cứu hộ được chọn!")
            .openPopup();

          map.setView([lat, lng], 15);

          document.getElementById("lat_input").value = lat.toFixed(8);
          document.getElementById("lng_input").value = lng.toFixed(8);

          var alertDiv = document.getElementById("coord-alert");
          alertDiv.className =
            "alert alert-success mt-2 py-2 text-center font-weight-bold";
          alertDiv.innerHTML = `<i class="fas fa-check-circle"></i> Đã ghim tọa độ thành công!`;
        }

        // Sự kiện Click trên bản đồ
        map.on("click", function (e) {
          updateLocation(e.latlng.lat, e.latlng.lng);
        });

        // Sự kiện Tự động lấy định vị GPS trình duyệt
        document
          .getElementById("btnAutoLocate")
          .addEventListener("click", function () {
            var alertDiv = document.getElementById("coord-alert");
            alertDiv.className =
              "alert alert-warning mt-2 py-2 text-center font-weight-bold";
            alertDiv.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Đang lấy tọa độ GPS...`;

            if (navigator.geolocation) {
              navigator.geolocation.getCurrentPosition(
                function (position) {
                  updateLocation(
                    position.coords.latitude,
                    position.coords.longitude,
                  );
                },
                function (error) {
                  alertDiv.className =
                    "alert alert-danger mt-2 py-2 text-center font-weight-bold";
                  alertDiv.innerHTML =
                    "Không thể lấy vị trí. Vui lòng bật GPS hoặc click tay trên bản đồ!";
                },
                { enableHighAccuracy: true }, // Yêu cầu GPS độ chính xác cao
              );
            } else {
              alert("Trình duyệt của bạn không hỗ trợ định vị GPS.");
            }
          });

        // Chặn submit nếu chưa có tọa độ
        document
          .getElementById("rescueForm")
          .addEventListener("submit", function (e) {
            if (!document.getElementById("lat_input").value) {
              e.preventDefault();
              alert("Bạn phải ghim vị trí trên bản đồ trước khi gửi yêu cầu!");
            }
          });
      });
    </script>
  </body>
</html>
