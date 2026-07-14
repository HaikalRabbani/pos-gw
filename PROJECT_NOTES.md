# POS Admin — Project Notes

## Stack
- **Backend**: Laravel 13 + Sanctum (API)
- **Frontend**: Vue 3 + Vite + Pinia + Vue Router + PrimeVue 4 + Tailwind CSS v4
- **Database**: MySQL (via Docker)
- **Realtime**: Laravel Reverb (WebSocket)
- **Payment**: Midtrans (snap)
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
Transaksi     → Pesanan, Dapur
Master Data   → Menu, Diskon, Pajak
Pengaturan    → Pengguna, Outlet           ← disembunyikan utk Manager
Laporan       → Laporan
```

Total: **9 menu** (6 untuk Manager)

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

## Key Files

### Backend (PHP)
- `routes/api.php` — semua endpoint API
- `app/Models/` — models by name
- `app/Http/Controllers/Api/V1/` — controllers by name
- `app/Http/Middleware/VerifyOutletAccess.php` — middleware outlet-scoped access
- `app/Services/AuthService.php` — auth logic
- `database/seeders/DatabaseSeeder.php` — seeder akun & outlet
- `database/factories/OutletFactory.php` — factory outlet

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
- `pages/dashboard/Dashboard.vue` — dashboard utama
- `pages/orders/Orders.vue` — daftar pesanan
- `pages/kitchen/KitchenDisplay.vue` — display dapur
- `pages/menu/MenuManagement.vue` — CRUD menu
- `pages/master/DiscountManagement.vue` — CRUD diskon
- `pages/master/TaxManagement.vue` — CRUD pajak
- `pages/users/UserManagement.vue` — CRUD user + role assignment
- `pages/outlets/OutletManagement.vue` — CRUD outlet
- `pages/report/Report.vue` — laporan + Chart.js (bar/line/doughnut)
- `pages/shifts/ShiftManagement.vue` — riwayat shift kasir

---

## API Endpoints (v1)

### Public
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/login-pin`
- `POST /api/v1/midtrans/notification`

### Authenticated
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

### Reports
- `GET /api/v1/reports/summary` — ringkasan keuangan (HPP, laba, margin)
- `GET /api/v1/reports/daily-sales` — penjualan harian untuk grafik
- `GET /api/v1/reports/top-products` — produk terlaris + profit margin

Semua endpoint kecuali outlets & users butuh `outlet.access` middleware.

---

## What's Been Done (Sesi Ini)

1. ✅ **Design overhaul** — sidebar glass effect, transition, cards, login page, etc
2. ✅ **Navigation restructure** — 5 grup, POS Kasir dihapus
3. ✅ **Outlet CRUD page** — `/outlets`
4. ✅ **Discount CRUD page** — `/discounts`
5. ✅ **Tax CRUD page** — `/taxes`
6. ✅ **Role system** — Developer, Owner, Manager, Cashier, Kitchen
7. ✅ **Role-based access** — sidebar visibility, route guard, no-access page
8. ✅ **Manager outlet-scoped** — hanya lihat & kelola user di outletnya
9. ✅ **Database seeder** — 7 akun test, 3 outlet
10. ✅ **OutletFactory** — factory untuk testing

---

### Sesi 2 (14 Juli 2026)

11. ✅ **Financial Report System** — HPP, laba kotor, margin, Chart.js
    - Migration: field `cost` di products + `unit_cost` di order_items
    - Backend: ReportService (summary, daily sales, top products)
    - Backend: ReportController + 3 API endpoints (summary, daily-sales, top-products)
    - Frontend: Report.vue dengan Chart.js (bar/line chart + doughnut composition)
    - Filter periode (hari ini, 7 hari, 30 hari, bulan ini, kustom)
12. ✅ **Transaction Seeder** — 30 hari data dummy (orders, items, payments)
13. ✅ **Sidebar UI/UX Overhaul**
    - Submenu/nested dengan toggle (click group header)
    - Slide animation expand/collapse
    - Thin custom scrollbar (webkit + firefox)
    - Tombol minimize pindah ke border (bulat, di luar stacking context)
    - Collapsed state persist di localStorage
    - Sidebar no longer scrolls with main content (h-screen + overflow-hidden)
    - User info dipindah dari sidebar ke top navbar kanan
    - Logo ga kepotong pas collapsed (dynamic padding)
14. ✅ **Shift Management Page** — `/shifts`
    - Frontend read-only (start/end shift via POS mobile nanti)
    - Active shift banner + summary cards + history DataTable

## Pending / Next

- [ ] Fitur export laporan (Excel/PDF) — Sudah ada tombol di Report.vue, tinggal backend
- [ ] Dashboard real-time dengan data asli dari API
- [ ] Manajemen metode pembayaran
- [ ] POS Mobile (Flutter/RN — proyek terpisah)
- [ ] Dark mode toggle? (user request)
