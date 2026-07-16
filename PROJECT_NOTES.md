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

## 🧪 Testing
Unit tests ada di:
- `tests/Unit/OrderServiceTest.php` — split, merge, refund, diskon, pajak
- `tests/Unit/ShiftServiceTest.php` — start/end shift, cash reconciliation
- `tests/Unit/ReportServiceTest.php` — summary, daily sales, top products

Jalankan: `php artisan test --testsuite=Unit`
