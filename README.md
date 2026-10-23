# Website Podcast / Media Library
---

## khởi chạy hệ thống
1. Đảm bảo máy tính đã cài đặt **Docker** và **Docker Compose**.
2. Giải nén thư mục dự án và mở terminal/command prompt tại thư mục này.
3. Khởi chạy toàn bộ hệ thống bằng lệnh:

   docker-compose up -d --build

   khởi chạy các container

   docker compose up -d

4. Truy cập các dịch vụ:
   - **Website Podcast (Nginx Reverse Proxy HTTPS):** `https://localhost` (hoặc `http://localhost`)
   - **Quản lý MySQL (phpMyAdmin):** `http://localhost:8081`
   - **Prometheus:** `http://localhost:9090`
   - **Grafana:** `http://localhost:3000` (Tài khoản: `admin` / `admin`)
   - **Loki Server:** `http://localhost:3100`