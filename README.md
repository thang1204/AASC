Yêu cầu môi trường
- PHP >= 8.1
- Composer
- Laravel 11
- Đăng ký App trên Bitrix24 (Cloud)

## ⚙️ Cài đặt trên máy mới

### 1. Clone dự án

```bash
git clone <link_repo>
cd <tên_thư_mục>
```

### 2. Cài đặt Laravel

```bash
composer install
```

### 3. Tạo `.env`

```bash
cp .env.example .env
```

Cập nhật `.env` các dòng sau với thông tin ứng dụng Bitrix24:

```
BITRIX_CLIENT_ID=client_id_từ_bitrix
BITRIX_CLIENT_SECRET=client_secret_từ_bitrix
```

Rồi chạy:

```bash
php artisan key:generate
```

## 🔐 Cấu hình App Bitrix24

1. Truy cập trang Bitrix24 OAuth Applications: https://oauth.bitrix.info/
2. Đăng nhập tài khoản và tạo App mới.
3. Nhập thông tin như sau:
•	Tên ứng dụng (Application Name): Name
•	Callback URL (Redirect URI):
https://yourdomain.com/bitrix/install
•	(có thể dùng ngrok và dùng URL như https://abc123.ngrok.io/bitrix/install)
•	Scope: crm
4. Sau khi tạo, bạn sẽ nhận được:
•	- client_id
•	- client_secret
5. Mở file .env trong dự án Laravel và cập nhật:

BITRIX_CLIENT_ID=your_client_id
BITRIX_CLIENT_SECRET=your_client_secret



## 🚀 Chạy ứng dụng

```bash
php artisan serve
```

> Nếu bạn dùng HTTPS bắt buộc (do Bitrix yêu cầu), có thể dùng [ngrok](https://ngrok.com):

```bash
ngrok http 8000
```

---


## 🔄 Cách hoạt động

- Khi người dùng cài đặt ứng dụng Bitrix24:
  - Bitrix gửi `auth` đến route: `/bitrix/install`
  - Laravel lưu `access_token` + `refresh_token` vào `bitrix_tokens.json`
- Giao tiếp qua API Bitrix để quản lý contacts, requisites (thông tin ngân hàng).

---


https://github.com/user-attachments/assets/6854f4c1-3d3e-4b01-992b-f8c95c5079d9

