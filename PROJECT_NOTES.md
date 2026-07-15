# POS Admin — Project Notes

## Stack
- **Backend**: Laravel 13 + Sanctum (API)
- **Frontend**: Vue 3 + Vite + Pinia + Vue Router + PrimeVue 4 + Tailwind CSS v4
- **Database**: SQLite (dev) / MySQL (prod via Docker)
- **Realtime**: Laravel Reverb (WebSocket)
- **Payment**: Midtrans (snap) + Xendit (disbursement/payout)
- **POS Mobile**: Rencana pakai Flutter / React Native (terpisah)

---

## Role System (4 role)

| Role | Label | Akses Panel Admin | Kelola User | Kelola Outlet | Kelola Konten |
|---|---|---|---|---|---|
| `developer` | 🟣 Developer | ✅ Full | ✅ | ✅ | ✅ |
| `admin` | 🟠 Owner | ✅ Full | ✅ | ✅ | ✅ |
| `manager` | 🔵 Manager | ✅ (tanpa Pengaturan) | ❌ CRUD | ❌ | ✅ (outletnya) |
| `cashier` | 🟢 Kasir | ❌ (redirect /no-access) | ❌ | ❌ | ❌ |
| `kitchen` | 🟤 Dapur | ❌ (redirect /no-access) | ❌ | ❌ | ❌ |

**Manager scope**: Bisa edit nama user, set PIN, toggle aktif — tapi hanya untuk user di outlet yang dia tempatin.

---

## Sidebar Navigation (Frontend)

```
Ringkasan     → Dashboard
Transaksi     → Pesanan, Dapur, Shift
Master Data   → Menu, Diskon, Pajak
Keuangan      → Penarikan Saldo          ← BARU
Pengaturan    → Pengguna, Outlet         ← disembunyikan utk Manager
Laporan       → Laporan
```

Total: **11 menu** (8 untuk Manager)

---

## Test Accounts (Seeder)

Jalankan `php artisan db:seed` — semua password: **`password`**

| Email | Role | PIN | Outlet |
|---|---|---|---|
| dev@pos.com | Developer | — | Semua |
| admin@pos.com | Owner | 123456 | Semua |
| manager@pos.com | Manager | 111111 | Outlet Pusat |
| manager2@pos.com | Manager | 222222 | Outlet Cabang 1 |
| kasir@pos.com | Kasir | 333333 | Outlet Pusat |
| kasir2@pos.com | Kasir | 444444 | Outlet Cabang 1 |
| dapur@pos.com | Koki | 555555 | Outlet Pusat |

Outlet: Pusat (Jakarta), Cabang 1 (Bandung), Cabang 2 (Surabaya)

---

## Payment Integration

- **Midtrans** — QRIS / pembayaran online (via Snap)
- **Xendit** — Disbursement/Payout (kirim dana ke rekening owner)
- **Withdraw system** — auto cair tanpa approval, tracking balance per outlet
- **Sandbox**: Xendit API key sudah terisi, bisa test withdraw langsung

---

## Seeder Data

Seeder sekarang minimalis — hanya **2 data** per entitas untuk testing:

- 2 kategori (Makanan, Minuman)
- 2 produk (Nasi Goreng, Es Teh Manis)
- 2 pajak (PPN 11%, Service Charge 5%)
- 2 diskon (10%, Rp 5.000)
- 2 order (hari ini & kemarin)
- 7 user + 3 outlet (tetap dari DatabaseSeeder)

---

## Key Files

### Backend (PHP)
- `routes/api.php` — semua endpoint API
- `app/Models/` — models by name
- `app/Http/Controllers/Api/V1/` — controllers by name
- `app/Http/Middleware/VerifyOutletAccess.php` — middleware outlet-scoped access
- `app/Services/AuthService.php` — auth logic
- `app/Services/OrderService.php` — order logic + diskon kompleks + pajak bertingkat
- `app/Services/ReportService.php` — laporan keuangan (HPP, laba, margin)
- `app/Services/WithdrawService.php` — balance tracking + Xendit payout
- `app/Services/MidtransService.php` — Midtrans snap + auto-add balance
- `database/seeders/DatabaseSeeder.php` — seeder akun & outlet
- `database/seeders/TransactionSeeder.php` — seeder transaksi dummy (2 data)
- `database/factories/OutletFactory.php` — factory outlet
- `app/Console/Commands/SeedBalance.php` — isi dummy balance withdraw

### Frontend (Vue)
- `resources/js/admin/main.js` — entry point
- `resources/js/admin/App.vue` — root component + route transitions
- `resources/js/admin/router/index.js` — routes + auth guard + role guard
- `resources/js/admin/stores/auth.js` — auth store (+ role detection)
- `resources/js/admin/components/layout/MainLayout.vue` — sidebar + layout
- `resources/js/admin/api/client.js` — axios client
- `resources/js/admin/theme/preset.js` — PrimeVue theme (teal)
- `resources/css/app.css` — global styles + PrimeVue overrides

### Pages
- `pages/auth/Login.vue` — login form
- `pages/auth/PinLogin.vue` — PIN login
- `pages/auth/NoAccess.vue` — halaman staff tanpa akses
- `pages/dashboard/Dashboard.vue` — dashboard real-time dari API
- `pages/orders/Orders.vue` — daftar pesanan
- `pages/kitchen/KitchenDisplay.vue` — display dapur
- `pages/menu/MenuManagement.vue` — CRUD produk + deskripsi
- `pages/master/DiscountManagement.vue` — CRUD diskon kompleks
- `pages/master/TaxManagement.vue` — CRUD pajak + urutan perhitungan
- `pages/users/UserManagement.vue` — CRUD user + foto + role assignment
- `pages/outlets/OutletManagement.vue` — CRUD outlet + token publik
- `pages/report/Report.vue` — laporan + Chart.js (bar/line/doughnut)
- `pages/shifts/ShiftManagement.vue` — riwayat shift kasir
- `pages/withdraw/WithdrawManagement.vue` — saldo QRIS + withdraw otomatis
- `pages/pos/PosCashier.vue` — POS kasir (simulasi, nanti pindah ke mobile)

---

## API Endpoints (v1)

### Public
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/login-pin`
- `POST /api/v1/midtrans/notification`

### Authenticated
- `GET /api/v1/dashboard` — optimized single-endpoint dashboard
- `GET/POST /api/v1/outlets` — list & create
- `GET/PUT/DELETE /api/v1/outlets/{id}` — detail, update, delete
- `POST /api/v1/user-outlets/assign` — assign role ke user di outlet
- `GET/PUT /api/v1/users` — list & update user
- `POST /api/v1/users/{id}/toggle-active` — toggle aktif
- `POST /api/v1/users/{id}/pin` — set PIN
- `GET/POST/PUT/DELETE /api/v1/categories`
- `GET/POST/PUT/DELETE /api/v1/products`
- `GET/POST/PUT/DELETE /api/v1/taxes`
- `GET/POST/PUT/DELETE /api/v1/discounts`
- `GET/POST/PUT/DELETE /api/v1/tables`
- `GET/POST /api/v1/orders` + items, status, pay
- `GET /api/v1/withdraw/balance` — lihat saldo outlet
- `GET /api/v1/withdraw/transactions` — riwayat mutasi
- `GET /api/v1/withdraw/withdrawals` — riwayat penarikan
- `POST /api/v1/withdraw/withdraw` — proses penarikan
- `POST /api/v1/upload` — upload file/foto

### Reports
- `GET /api/v1/reports/summary` — ringkasan keuangan (HPP, laba, margin)
- `GET /api/v1/reports/daily-sales` — penjualan harian untuk grafik
- `GET /api/v1/reports/top-products` — produk terlaris + profit margin

---

## Progress Sesi Ini (14 Juli 2026)

### Fitur Baru
1. ✅ **Pajak Bertingkat** — Service Charge dulu, baru PPN (sequential)
2. ✅ **Deskripsi Produk** — migration + frontend
3. ✅ **Avatar/Foto User** — migration + upload + frontend
4. ✅ **Diskon Kompleks** — target_type (produk/kategori/transaksi), min_purchase, max_discount, buy_x_get_y, masa aktif
5. ✅ **Notes Order Item** — catatan per item di POS Cashier
6. ✅ **Withdraw QRIS System** — Xendit disbursement, auto cair, tracking balance per outlet

### Perbaikan
7. ✅ **RBAC Fix** — Owner tidak bisa lihat/edit Developer
8. ✅ **DataTable Redesign** — gradient header, striped rows, paginator
9. ✅ **CRUD buttons** — pindah ke toolbar tabel, gap lebih lega
10. ✅ **Dialog opacity fix** — modal putih solid
11. ✅ **Dropdown overlay fix** — background putih
12. ✅ **Button icon size** — lebih kecil, ada gap
13. ✅ **Edit user** — foto digabung ke dialog edit

### Dashboard
14. ✅ **Dashboard real-time** — data asli dari API, optimized query
15. ✅ **DashboardController** — single endpoint, scoped ke user outlets
16. ✅ **Auto-refresh dihapus** — manual refresh aja

### Seeder
17. ✅ **TransactionSeeder** — hanya 2 data per entitas

## Progress Sesi Ini (15 Juli 2026)

### Fitur Baru
1. ✅ **Manajemen Meja (Frontend)** — TableManagement.vue dengan QR code gambar per meja (bukan token teks)
2. ✅ **Split Bill & Merge Bill** — Backend (OrderService) + frontend (dialog split per item, merge multi-order)
3. ✅ **Sample Order Button** — Generate dummy draft/confirmed orders langsung dari halaman Orders buat testing
4. ✅ **Stations Feature** — Migration `stations` + `station_id` di products + CRUD controller + print-groups API (`GET /orders/{id}/print-groups`) untuk routing thermal printer per station
5. ✅ **RBAC (Role-Based Access Control)** — Backend middleware rules (void=cashier, split/merge=cashier, refund=manager, shifts=cashier, users=admin, stations=manager) + Frontend action gating (`usePermission.js` composable)
6. ✅ **Profile Settings** — Halaman `/profile` untuk edit nama, email, password, PIN, foto profil. Dropdown di navbar avatar → link ke Profile Settings + tombol Keluar

### Perbaikan & Refactor
7. ✅ **PosCashier.vue & KitchenDisplay.vue dihapus** — POS pindah ke Flutter, KDS belum ada rencana. File dikosongkan, route & sidebar dibersihkan
8. ✅ **Kategori & Station Modal Inline** — Manajemen kategori & station jadi modal dialog di halaman Menu, bukan halaman terpisah. StationManagement.vue dikosongkan
9. ✅ **Column header # → No.** — Semua 10 DataTable columns di 9 file diganti
10. ✅ **Format Rupiah Diseragamkan** — Shared helper `utils/format.js` (formatRupiah ÷ 100 + Rp prefix), 5 local functions dihapus, 3 bug fix (Shift/Report/Dashboard tadinya gak ÷ 100), 9 template `Rp ` ganda di Orders.vue diperbaiki, chart tooltip labels Report.vue diperbaiki
11. ✅ **ReportController formatRupiah ÷ 100** — Export Excel/PDF tadinya gak bagi 100 (keliatan 100x lipat)
12. ✅ **Seeder disederhanakan** — Hanya 4 akun (dev, owner, manager, kasir) + 2 outlet. Pake `User::create()` langsung, gak pake factory
13. ✅ **N+1 query fix** — AuthService login & login-pin sekarang eager load outlets biar gak N+1
14. ✅ **Duplicate dashboard route dihapus** — Route `GET /v1/dashboard` di dalam `outlet.access` middleware dihapus (dead code)
15. ✅ **UserManagement sembunyikan akun sendiri** — User yg login difilter dari daftar (edit via Profile Settings)

### Pending / Next
- [ ] Sistem refund (frontend flow) — dialog detail refund + pilih item
- [ ] Export laporan Excel/PDF
- [ ] Manajemen metode pembayaran
- [ ] POS Mobile (Flutter/RN — proyek terpisah)
- [ ] Dark mode toggle
- [ ] Table reservation system
- [ ] Promo engine UI testing
- [ ] Attendance / absensi karyawan
