# POS Admin — Project Notes

## Stack
- **Backend**: Laravel 13 + Sanctum (API)
- **Frontend**: Vue 3 + Vite + Pinia + Vue Router + PrimeVue 4 + Tailwind CSS v4
- **Database**: SQLite (dev) / MySQL (prod via Docker)
- **Realtime**: Laravel Reverb (WebSocket)
- **Payment**: Midtrans (snap) + Xendit (disbursement/payout)
- **POS Mobile**: Flutter (proyek terpisah — komunikasi via REST API yg sama)
- **Web Self-Order**: Pelanggan scan QR meja → buka web order mandiri (proyek terpisah)

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
Dashboard       → Ringkasan
Transaksi       → Pesanan
Laporan         → Keuangan, Shift
Master Data     → Menu, Diskon, Pajak, Meja
Shift & Jadwal  → Shift (master + jadwal)
Keuangan        → Penarikan
Pengaturan      → Pengguna, Outlet (owner only)
```

---

## Test Accounts (Seeder)

Jalankan `php artisan db:seed` — semua password: **`password`**

| Email | Role | PIN | Outlet |
|---|---|---|---|
| dev@pos.com | Developer | — | Semua |
| admin@pos.com | Owner | 123456 | Semua |
| manager@pos.com | Manager | 111111 | Outlet Pusat |
| kasir@pos.com | Kasir | 333333 | Outlet Pusat |

Outlet: Outlet Pusat, Outlet Cabang

---

## Payment Integration

### Dual Mode Payment

```
┌─ Outlet punya Midtrans key sendiri? ──────────────┐
│                                                     │
│  YA ───► Midtrans Snap (own key)                   │
│           Uang LANGSUNG ke akun Midtrans outlet     │
│           Tidak ada saldo internal / withdraw       │
│                                                     │
│  TIDAK ─► Xendit Invoice (QRIS/VA)                 │
│            Uang ke akun Xendit platform             │
│            + Saldo internal → withdraw (Xendit payout)
└─────────────────────────────────────────────────────┘
```

### Provider
- **Xendit** — Payment gateway (Invoice QRIS/VA) + Disbursement/Payout (platform mode)
- **Midtrans** — Payment gateway opsional kalo outlet punya akun sendiri (own key mode)
- **Auto-detection** — Sandbox/production dideteksi dari format key: `SB-Mid-server` = sandbox, `Mid-server` = production
- **Webhook Security** — Webhook token verification di Xendit callback endpoint

---

## Seeder Data

Seeder sekarang minimalis — hanya **2 data** per entitas untuk testing:

- 2 kategori (Makanan, Minuman)
- 2 produk (Nasi Goreng, Es Teh Manis)
- 2 pajak (PPN 11%, Service Charge 5%)
- 2 diskon (10%, Rp 5.000)
- 2 order (hari ini & kemarin)
- 4 user + 2 outlet (tetap dari DatabaseSeeder)

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
- `app/Services/XenditPaymentService.php` — Xendit invoice payment + callback handler
- `app/Services/MidtransService.php` — Midtrans snap + auto-add balance (own key: SKIP balance)
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
- `pages/auth/Login.vue` — login form + PIN login
- `pages/auth/NoAccess.vue` — halaman staff tanpa akses
- `pages/dashboard/Dashboard.vue` — dashboard real-time dari API
- `pages/orders/Orders.vue` — daftar pesanan + split bill + merge bill
- `pages/menu/MenuManagement.vue` — CRUD produk + foto + kategori & station inline
- `pages/master/DiscountManagement.vue` — CRUD diskon multi-produk (MultiSelect pills)
- `pages/master/TaxManagement.vue` — CRUD pajak + urutan perhitungan
- `pages/tables/TableManagement.vue` — CRUD meja + QR code gambar
- `pages/users/UserManagement.vue` — CRUD user + foto + role assignment
- `pages/outlets/OutletManagement.vue` — CRUD outlet + config Midtrans key (opsional)
- `pages/report/Report.vue` — laporan keuangan + Chart.js
- `pages/report/ShiftReport.vue` — laporan shift khusus
- `pages/shifts/ShiftManagement.vue` — master shift + penjadwalan (tabs)
- `pages/withdraw/WithdrawManagement.vue` — saldo QRIS + withdraw otomatis (own key: info card)
- `pages/auth/ProfileSettings.vue` — edit profil sendiri

---

## API Endpoints (v1)

### Public
- `POST /api/v1/auth/register` (throttled)
- `POST /api/v1/auth/login` (throttled)
- `POST /api/v1/auth/login-pin` (throttled)
- `POST /api/v1/midtrans/notification` (Midtrans own-key notification)
- `POST /api/v1/xendit/callback` (Xendit webhook, verified with token)

### Authenticated
- `GET /api/v1/dashboard` — optimized single-endpoint dashboard
- `GET/POST /api/v1/outlets` — list & create
- `GET/PUT/DELETE /api/v1/outlets/{id}` — detail, update, delete
- `POST /api/v1/user-outlets/assign` — assign role ke user di outlet
- `GET/PUT /api/v1/users` — list & update user
- `POST /api/v1/users/{id}/toggle-active` — toggle aktif
- `POST /api/v1/users/{id}/pin` — set PIN
- `GET/POST/PUT/DELETE /api/v1/categories`
- `GET/POST/PUT/DELETE /api/v1/products` (+ variant, station, category)
- `GET/POST/PUT/DELETE /api/v1/taxes`
- `GET/POST/PUT/DELETE /api/v1/discounts` (+ multi-produk array target_id)
- `GET/POST/PUT/DELETE /api/v1/stations`
- `GET/POST/PUT/DELETE /api/v1/tables` + regenerate QR
- `GET/POST /api/v1/orders` + items, status, pay, split, merge, refund
- `GET /api/v1/orders/{id}/print-groups` — routing cetak per station
- `GET/POST/PUT/DELETE /api/v1/shift-types`
- `GET/POST/PUT/DELETE /api/v1/shift-schedules` + generate bulanan
- `GET /api/v1/shifts` + start/end shift
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

## Ekosistem POS (3 Aplikasi)

| Aplikasi | Platform | Fungsi | Status |
|---|---|---|---|
| **POS Admin** | Web (Vue + Laravel) | Manajemen outlet, menu, diskon, laporan | ✅ Aktif (ini) |
| **POS Mobile** | Flutter | Input pesanan, pembayaran, shift kasir | 🚧 Rencana |
| **Self-Order** | Web (terpisah) | Pelanggan scan QR meja → order mandiri | 🚧 Rencana |

### Alur Data:
```
Self-Order → API POS → Mobile POS → (sync)
    ↓                        ↓
  QR Meja                Thermal Printer
    ↓                        ↓
  Customer                Dapur/Bar/Grill
```

---

## Progress Sesi 17 Juli 2026 — Payment Overhaul

### Dual Mode Payment System
1. ✅ **Migration** — Tambah `midtrans_server_key` (encrypted) di outlets
2. ✅ **MidtransService** — Conditional key: pake key outlet kalo ada, fallback ke config
3. ✅ **XenditPaymentService (baru)** — Create Xendit Invoice + handle callback
4. ✅ **PaymentController** — Deteksi otomatis: Midtrans (own key) vs Xendit (platform)
5. ✅ **Routes** — Tambah `/v1/xendit/callback`, rename midtrans endpoint
6. ✅ **Webhook Security** — Xendit callback verification dengan token
7. ✅ **Bug Fix Critical** — Own key transactions sekarang gak addBalance (fix double-payment)
8. ✅ **Auto-detect Sandbox/Prod** — Dari format key (`SB-Mid-server` / `Mid-server`)
9. ✅ **OutletManagement UI** — Input Midtrans key (password field + eye toggle)
10. ✅ **Withdraw UI** — Sembunyiin form withdraw + riwayat kalo own key (ganti info card)
11. ✅ **Text Simplification** — Hapus semua jargon provider dari halaman Penarikan
12. ✅ **Cleanup** — Hapus `token_public` (dead code, gak kepake)

### Key Decisions
- **Xendit = platform payment + payout** (satu ekosistem, uang gak pindah provider)
- **Midtrans = opsional untuk outlet yang punya akun sendiri**
- **Gak ada toggle sandbox/production** — format key sudah menentukan
- **Halaman Penarikan polos** — Gak pake nama provider, cuma jelasin flow doang

---

## Progress Sesi 17 Juli 2026 — Part 2: UX & Optimization

### Alert/Confirm → Toast & Dialog
1. ✅ **OutletManagement.vue** — `alert()` → `toast.error()` (import + 2 lokasi)
2. ✅ **WithdrawManagement.vue** — `alert()` → `toast.error()` (import + 1 lokasi)
3. ✅ **Report.vue** — `console.error()` → `toast.error()`
4. ✅ **Orders.vue** — `confirm()` diganti Dialog konfirmasi Void
5. ✅ **UserManagement.vue** — `confirm()` diganti Dialog konfirmasi Hapus Akses

### N+1 Fix — Discount Model
6. ✅ **Discount.php** — Hapus `$appends` penyebab N+1 tiap serialisasi
7. ✅ **DiscountController** — Manual `->append()` cuma di index/store/update (3 endpoint)

### Multi-Outlet Dashboard
8. ✅ **DashboardController** — Tambah parameter `outlet_id` opsional (filter per outlet)
9. ✅ **Dashboard.vue** — Selector outlet muncul kalo >1 outlet, data dashboard terfilter

### Responsive Improvements
10. ✅ **Overflow-x-auto** — 6 halaman (Orders, User, Discount, Tax, Table, Outlet)
11. ✅ **Flex-wrap toolbar** — Search box rapi di mobile (User, Discount)

### Search/Filter di Tabel
12. ✅ **TaxManagement** — Search by nama pajak
13. ✅ **TableManagement** — Search by nama meja
14. ✅ **OutletManagement** — Search by nama/alamat outlet

---

## Progress Sesi 14-15 Juli 2026

### Fitur Baru
1. ✅ **Pajak Bertingkat** — Service Charge dulu, baru PPN (sequential)
2. ✅ **Deskripsi Produk** — migration + frontend
3. ✅ **Avatar/Foto User** — migration + upload + frontend
4. ✅ **Diskon Kompleks** — target_type, min_purchase, max_discount, buy_x_get_y, masa aktif
5. ✅ **Notes Order Item** — catatan per item di POS Cashier
6. ✅ **Withdraw QRIS System** — Xendit disbursement, auto cair, tracking balance per outlet
7. ✅ **Manajemen Meja (Frontend)** — QR code gambar per meja
8. ✅ **Split Bill & Merge Bill** — Backend + frontend dialog
9. ✅ **Stations Feature** — Migration + CRUD + print-groups API
10. ✅ **Profile Settings** — Halaman `/profile` edit profil sendiri

### Perbaikan & Refactor
11. ✅ **RBAC Fix** — Owner tidak bisa lihat/edit Developer
12. ✅ **RBAC Frontend** — `usePermission.js` composable + action gating
13. ✅ **DataTable Redesign** — gradient header, striped rows, paginator
14. ✅ **CRUD buttons** — pindah ke toolbar tabel
15. ✅ **Format Rupiah Diseragamkan** — Shared helper `utils/format.js`
16. ✅ **ReportController formatRupiah ÷ 100** — Export Excel/PDF fix
17. ✅ **PosCashier.vue & KitchenDisplay.vue dihapus** — POS pindah ke Flutter
18. ✅ **StationManagement.vue dikosongkan** — Diganti modal inline di Menu
19. ✅ **Shift Page Revamp — 2 Tabs** — Master Shift + Penjadwalan (tab Laporan dipindah ke sidebar Laporan > Shift)
20. ✅ **Sidebar Restruktur** — Dashboard, Transaksi, Laporan, Master Data, Shift & Jadwal, Keuangan, Pengaturan
21. ✅ **N+1 query fix** — AuthService login sekarang eager load outlets
22. ✅ **Smart Auto Outlet Mode** — Selector outlet otomatis ilang klo cuma 1
23. ✅ **Duplicate session check dihapus** — Router guard handle semua, main.js gak perlu

---

## Progress Sesi 16 Juli 2026

### Optimasi & Perbaikan Kode
1. ✅ **Dead Code Cleanup** — File kosong/tak terpakai dihapus:
   - `CategoryManagement.vue` (inline di Menu)
   - `StationManagement.vue` (inline di Menu)
   - `Orders copy.vue` (duplicate)
   - `OrderReport.vue` (redundan — gap andanya `/orders`)
   - Folder `auth/Register.vue` gak dipake

2. ✅ **Unused Dependencies Dihapus** — `package.json` dibersihkan dari library tak terpakai (chart.js, html2canvas, jspdf, qrcode, vue-chartjs, dll)

3. ✅ **Session Check Optimization** — Hapus duplikasi `checkSession()` di main.js + circular dependency di router

### Security Improvements
4. ✅ **Rate Limiting** — Login, register, login-pin pake throttle (5-10x/60 detik)
5. ✅ **Mass Assignment Protection** — Semua model pake `$fillable`, validasi ketat di controller
6. ✅ **XSS Prevention** — Vue template auto-escape, tag pake component Tag, inputNumber buat numeric
7. ✅ **SQL Injection Safe** — Semua query pake Eloquent (parameter binding)
8. ✅ **CORS & Session** — API pake cookie-based auth (Sanctum SPA), CSRF protection aktif
9. ✅ **File Upload Validation** — Upload dibatasi image only, max 2MB
10. ✅ **Route Protection** — Semua route di `auth:sanctum` + `outlet.access` middleware

### Dashboard Redesign
11. ✅ **Dashboard Cards** — Revenue, pesanan, produk, shift — pakai data real, bukan dummy
12. ✅ **Ringkasan Sistem Dihapus** — Versi aplikasi, status server, laba rangkap — gak esensial
13. ✅ **Auto-refresh Dihapus** — Manual refresh via tombol

### Laporan (Sidebar Category)
14. ✅ **Sidebar Tab Laporan Baru** — Laporan > Keuangan, Shift, Pesanan (lalu Pesanan dihapus karena redundan)
15. ✅ **ShiftReport.vue** — Halaman laporan shift khusus
16. ✅ **Backend Date Filter** — ShiftController + OrderController terima `start_date`/`end_date`
17. ✅ **Tab Laporan di ShiftManagement dihapus** — Pindah ke sidebar Laporan > Shift

### Menu Management Improvements
18. ✅ **DataTable Sederhana** — Hapus kolom Deskripsi & Status dari tabel (pindah ke detail dialog)
19. ✅ **Foto Produk** — Thumbnail di tabel + upload di form + detail dialog
20. ✅ **Detail Dialog** — Card info lengkap: modal, laba, persentase, timestamp
21. ✅ **Status Diatur via Form** — Dropdown status di dialog edit (ganti toggle di tabel)
22. ✅ **Fix station_id gak kesimpan** — Tambah `station_id` ke validasi controller + `$fillable` model + eager load
23. ✅ **Eager Load Kategori & Station** — `index()` sekarang load `category` & `station` (sebelumnya cuma `variants`)

### Discount Management Redesign
24. ✅ **Form Terstruktur** — 5 section: Informasi Dasar, Jenis Diskon, Sasaran, Ketentuan, Masa Berlaku
25. ✅ **Visual Card Selector** — Persentase, Nominal, Beli Gratis (BOGO) — pake kartu dengan icon + centang
26. ✅ **Adaptive Fields** — BOGO fields cuma muncul kalo pilih BOGO
27. ✅ **Multi-Produk MultiSelect** — Pilih >1 produk dengan pills + tombol X hapus per item
28. ✅ **Preview Summary** — Live preview card di bawah form
29. ✅ **Migration target_id** — Kolom diubah dari integer ke text untuk simpan JSON array

### Pending / Next
- [ ] Export laporan Excel/PDF
- [ ] POS Mobile (Flutter) — integrasi API
- [ ] Web Self-Order via QR — pelanggan scan QR meja → pesan sendiri
- [ ] Manajemen metode pembayaran (Flutter/QR)
- [ ] Dark mode toggle
- [ ] Table reservation system
- [ ] Attendance / absensi karyawan
- [ ] Kitchen Display System (KDS) — layar dapur otomatis
- [x] Optimasi N+1 di Discount model accessors
