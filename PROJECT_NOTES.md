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

---

## 🗺️ Roadmap Selanjutnya

### 🥇 Prioritas 1: Web Self-Order via QR
Halaman publik terpisah untuk customer scan QR meja → pesan sendiri.
- [ ] Frontend React/Vue sederhana (terpisah dari Admin Panel)
- [ ] Integrasi API yang sudah ada (products, customize, orders)
- [ ] Bahan bisa dimatiin + add-on (via endpoint `/products/{id}/customize`)

### 🥇 Prioritas 2: POS Mobile (Flutter)
Aplikasi Flutter untuk kasir di tablet/HP.
- [ ] Integrasi API yang sudah ada
- [ ] Pilih outlet, produk, checkout
- [ ] Thermal printer + QR payment

### 🥈 Prioritas 3 (Improvement)
- [ ] Stok opname — adjustment manual + log riwayat perubahan stok
- [ ] Alert stok minim (notifikasi pas stok di bawah min_stock)
- [ ] Customer & membership (histori transaksi, tier diskon)

---

## 🧪 Testing
Unit tests ada di:
- `tests/Unit/OrderServiceTest.php` — split, merge, refund, diskon, pajak
- `tests/Unit/ShiftServiceTest.php` — start/end shift, cash reconciliation
- `tests/Unit/ReportServiceTest.php` — summary, daily sales, top products

Jalankan: `php artisan test --testsuite=Unit`
