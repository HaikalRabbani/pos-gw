# Project Notes — POS System

## ✅ Admin Panel — Feature Complete

### Sesi 16 Juli
| Fitur | Status |
|---|---|
| Export laporan Excel/PDF (Keuangan + Shift) | ✅ |
| Bahan & Add-on (halaman Master Data) | ✅ |
| Stok ganda (Per-Produk / Per-Bahan BOM + auto-deduct) | ✅ |
| Optimasi N+1 di Discount model accessors | ✅ |
| Unit testing (OrderService, ShiftService, ReportService) | ✅ |
| Audit Karyawan di Laporan Shift | ✅ |
| Struktur Self-Order (folder, entry Vite, route, halaman kosong) | ✅ |
| Migrasi icon PrimeIcons → Lucide (17+ file Vue) | ✅ |
| Font Poppins (ganti dari Plus Jakarta Sans / Inter / DM Sans) | ✅ |
| Sidebar SVG chevron tipis (stroke-width 1.5) | ✅ |
| Urutan sidebar: Bahan & Add-on setelah Menu | ✅ |

---

## 🗺️ Roadmap Selanjutnya

### 🥇 Prioritas 1: Web Self-Order via QR
- [x] Struktur awal (folder `self-order/`, entry Vite, route Laravel, halaman kosong)
- [ ] API endpoint menu publik (produk + kategori aktif)
- [ ] Tampilan menu + kustomisasi (bahan bisa dimatiin, add-on)
- [ ] Keranjang + checkout
- [ ] QR code di tiap meja → scan → buka halaman

### 🥇 Prioritas 2: POS Mobile (Flutter)
- [ ] Integrasi API yang sudah ada
- [ ] Pilih outlet, produk, checkout
- [ ] Thermal printer + QR payment

### 🥈 Prioritas 3 (Improvement)
- [ ] Stok opname — adjustment manual + log riwayat perubahan stok
- [ ] Alert stok minim (notifikasi pas stok di bawah min_stock)
- [ ] Customer & membership (histori transaksi, tier diskon)

---

## 📧 Email Service (Belum Aktif)

Untuk ngirim email beneran (kode aktivasi & reset password), setup `.env` pilih salah satu:

### Opsi 1: Gmail SMTP (gratis, udah punya akun)
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=emailkamu@gmail.com
MAIL_PASSWORD=password_app_gmail_16_karakter
MAIL_FROM_ADDRESS=emailkamu@gmail.com
MAIL_FROM_NAME="POS Admin"
```
Bikin App Password di: https://myaccount.google.com/apppasswords

### Opsi 2: Mailtrap (testing)
Daftar di https://mailtrap.io → ambil SMTP credentials

### Opsi 3: Log (gak perlu setup — nulis ke file)
Default `MAIL_MAILER=log` — email bisa dibaca di `storage/logs/laravel.log`

### Setting lain:
```
APP_FRONTEND_URL=http://localhost:5173
```

---

## 🔐 Auth & Verifikasi

### Halaman Publik (gak perlu login)
| Route | Halaman | Keterangan |
|---|---|---|
| `/login` | Login.vue | Login email/password + PIN |
| `/register` | Register.vue | Daftar akun baru |
| `/verify-email` | VerifyEmail.vue | Input kode 6 digit aktivasi |
| `/forgot-password` | ForgotPassword.vue | Minta link reset password |
| `/reset-password` | ResetPassword.vue | Ganti password baru (dari link email) |

### Alur Register
```
Daftar → Email kode 6 digit → Input kode → Auto login → Dashboard
```

### Alur Forgot Password
```
Minta reset → Email link → Klik link → Ganti password → Login ulang
```

### API Endpoints
| Method | Endpoint | Auth |
|---|---|---|
| POST | `/api/v1/auth/register` | ❌ |
| POST | `/api/v1/auth/verify-email` | ❌ |
| POST | `/api/v1/auth/resend-verification` | ❌ |
| POST | `/api/v1/auth/forgot-password` | ❌ |
| POST | `/api/v1/auth/reset-password` | ❌ |
| POST | `/api/v1/auth/login` | ❌ |
| POST | `/api/v1/auth/pin-login` | ❌ |
| GET | `/api/v1/auth/me` | ✅ |
| POST | `/api/v1/auth/logout` | ✅ |

---

## 🧪 Testing
Unit tests ada di:
- `tests/Unit/OrderServiceTest.php` — split, merge, refund, diskon, pajak
- `tests/Unit/ShiftServiceTest.php` — start/end shift, cash reconciliation
- `tests/Unit/ReportServiceTest.php` — summary, daily sales, top products

Jalankan: `php artisan test --testsuite=Unit`
