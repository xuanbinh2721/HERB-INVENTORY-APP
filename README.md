# 🌿 Herb Inventory & Invoicing System

Hệ thống quản lý kho và bán hàng dược liệu với giao diện hiện đại, chuyên nghiệp.

## ✨ Tính năng chính

- 📦 **Quản lý sản phẩm**: Thêm, sửa, xóa sản phẩm với SKU và thông tin chi tiết
- 🏪 **Quản lý nhà cung cấp**: Theo dõi thông tin nhà cung cấp
- 👥 **Quản lý khách hàng**: Lưu trữ thông tin khách hàng
- 📥 **Phiếu nhập hàng**: Quản lý hàng nhập kho với chi tiết từng sản phẩm
- 📤 **Hóa đơn bán hàng**: Tạo và quản lý hóa đơn bán hàng
- 📊 **Tính tồn kho**: Tự động tính toán tồn kho theo thời gian thực
- 💰 **Báo cáo doanh thu**: Thống kê doanh thu với giá vốn trung bình
- 📄 **Xuất CSV**: Xuất báo cáo ra file Excel
- 🔐 **Hệ thống đăng nhập**: Bảo mật với session management

## 🚀 Cài đặt và chạy

### Yêu cầu hệ thống
- Docker và Docker Compose
- Git

### Cách chạy nhanh

```bash
# Clone repository
git clone https://github.com/xuanbinh2721/HERB-INVENTORY-APP.git
cd HERB-INVENTORY-APP

# Chạy ứng dụng
docker compose up -d --build

# Truy cập ứng dụng
# Main app: http://localhost:8080
# phpMyAdmin: http://localhost:8081
```

### Thông tin đăng nhập mặc định
- **Tài khoản**: `admin`
- **Mật khẩu**: `admin123`

## 🏗️ Cấu trúc dự án

```
herb-inventory-php/
├── src/
│   ├── public/           # Document root (index.php)
│   ├── controllers/      # Controllers cho từng trang
│   ├── lib/             # Helpers, auth, DB connection
│   ├── models/          # Business logic (tính tồn kho)
│   ├── views/layout/    # Header, navbar, footer
│   ├── sql/schema.sql   # Database schema
│   └── assets/css/      # Custom CSS styles
├── Dockerfile           # PHP Apache container
├── docker-compose.yml   # Multi-container setup
└── README.md           # Documentation
```

## 🎨 Giao diện

### Tính năng UI/UX
- 🎨 **Thiết kế hiện đại**: Gradient backgrounds, shadows, animations
- 📱 **Responsive**: Hoạt động tốt trên desktop và mobile
- 🎯 **User-friendly**: Icons, tooltips, form validation
- ⚡ **Performance**: Tối ưu hóa loading và interactions
- 🎨 **Custom CSS**: Styling chuyên nghiệp với Bootstrap 5

### Công nghệ sử dụng
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Icons**: Font Awesome 6
- **Fonts**: Google Fonts (Inter)
- **Backend**: PHP 8.2, MySQL 8.0
- **Container**: Docker, Docker Compose

## 📊 Database Schema

### Bảng chính
- `users` - Quản lý người dùng
- `products` - Thông tin sản phẩm
- `suppliers` - Nhà cung cấp
- `customers` - Khách hàng
- `purchases` - Phiếu nhập hàng
- `purchase_items` - Chi tiết nhập hàng
- `sales` - Hóa đơn bán hàng
- `sale_items` - Chi tiết bán hàng

## 🔧 Tùy biến

### Thay đổi cấu hình
Tạo file `.env` để tùy chỉnh:
```env
APP_PORT=8080
PMA_PORT=8081
DB_NAME=herb_shop
DB_USER=herb_user
DB_PASS=herb_pass
ADMIN_USER=admin
ADMIN_PASS=admin123
```

### Thêm tính năng mới
- **URL đẹp**: Thêm mod_rewrite và routing
- **COGS chính xác**: Implement FIFO/LIFO
- **In hóa đơn**: Tạo template in với CSS
- **Báo cáo nâng cao**: Charts và analytics

## 🤝 Đóng góp

1. Fork repository
2. Tạo feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Tạo Pull Request

## 📝 License

Dự án này được phát hành dưới MIT License.

## 👨‍💻 Tác giả

**Xuân Bình** - [GitHub](https://github.com/xuanbinh2721)

---

⭐ Nếu dự án này hữu ích, hãy cho một star trên GitHub!
