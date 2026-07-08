# PRD — POS F&B System (Revisi)

## 1. Tujuan
Sistem POS modular & scalable untuk F&B (cafe/resto/catering) — bisa fast food, full service, atau QR order. Multi-outlet sejak awal.

## 2. Target Platform
- **Admin/Cashier:** Web (touchscreen-friendly)
- **Kitchen Display:** Web (layar besar)
- **Customer Order:** Web via QR scan (mobile-first)
- **Backend:** REST API + WebSocket

## 3. Arsitektur

```
┌────────────┐     ┌────────────┐     ┌────────────┐
│ Vue 3      │────▶│ Laravel API│────▶│ MySQL/     │
│ (Cashier)  │     │ (REST)     │     │ PostgreSQL │
├────────────┤     │            │     ├────────────┤
│ Vue 3      │────▶│ Laravel    │────▶│ Redis      │
│ (KDS)      │     │ Reverb (WS)│     │ (cart, cache)│
├────────────┤     │            │     └────────────┘
│ Vue 3      │────▶│ Midtrans   │
│ (QR Order) │     └────────────┘
└────────────┘
```

## 4. Fitur per Phase

### Phase 1 — Core POS (v1.0)
- **Auth:** login email+password sekali di awal shift; PIN 4 digit untuk quick-switch antar kasir dalam device yang sama selama shift berjalan
- Outlet management: create, switch, multi-outlet
- Produk & kategori: CRUD, variasi (size, topping), harga, gambar
- Order flow: pilih meja → cari produk → cart → checkout
- **Order flow tambahan:** state Void/Cancel paralel di tiap tahap (Draft, Confirmed, Preparing) — bukan cuma happy path
- **Kitchen Display (simplified):** real-time order masuk (list per order_id, urut waktu), accept, done. Grouping visual per meja **di-defer ke Phase 3**
- Payment: Tunai, QRIS, Midtrans
- Order status state machine + log
- **Role assignment dasar** (via `user_outlet` pivot: admin/kasir/koki) — CRUD role lengkap tetap di Phase 3, tapi assign manual harus ada dari awal karena entity-nya sudah dipakai
- Shift: buka shift, closing (itungin cash) — **shift terikat ke outlet** (lihat poin 6 di bawah)
- Laporan: penjualan harian

### Phase 2 — Customer Self-Order (v1.1)
- QR per meja → link ke menu
- **Cart per-device:** tiap orang yang scan QR dapat cart individual sendiri, tidak shared dengan orang lain di meja yang sama
- **Submit independen:** tiap device submit order sendiri-sendiri begitu selesai memilih — tidak menunggu "leader" atau collective submit. `table_id` jadi common reference untuk grouping visual saja (bukan blocking logic)
- **Bill merge saat closing:** kasir bisa gabung beberapa order dengan `table_id` sama jadi 1 tagihan pembayaran (lihat poin 3 & 4 di bawah)
- Public API dengan **token per-session/per-table** (bukan benar-benar "no auth") — mencegah spam order fiktif
- No auth required untuk customer (token-based session saja)

### Phase 3 — Manajemen Lengkap (v1.2)
- Stok/inventory
- Employee management + role (CRUD lengkap)
- **KDS grouping by table_id** (enhancement dari Phase 1 simplified version)
- Laporan laba rugi
- Multi-outlet dashboard
- Export Excel/PDF

### Phase 4 — Delivery & Eksternal (v2.0+)
- Integrasi GoFood/GrabFood
- Cetak struk thermal (v1 cukup preview/PDF digital, lihat poin 9)
- Online ordering web

## 5. Teknologi

| Layer | Tech | Alasan |
|---|---|---|
| API | **Laravel** | Familiar, ada base dari project sebelumnya |
| DB | **MySQL/PostgreSQL** | Native support Laravel |
| Cache/Queue | **Redis** | Session cart per-device, queue job Midtrans |
| Real-time | **Laravel Reverb** | WebSocket native Laravel, tanpa stack tambahan buat KDS |
| Auth | **Laravel Sanctum** | Token-based, cocok buat SPA + public API Phase 2 |
| Frontend | **Vue 3 + Tailwind + PrimeVue** | Touch-friendly komponen |
| Payment | **Midtrans** | QRIS, CC, bank transfer |
| Infra | **Docker + docker-compose** | Deploy gampang |

## 6. Entity Utama

```
outlet
├── user_outlet (pivot role: admin/kasir/koki)
├── category
├── product
│   └── product_variant
├── tax
├── discount
├── table
└── order
    ├── order_item
    ├── order_discount (pivot — traceability diskon per order)
    ├── payment (many-to-one: 1 payment bisa cover >1 order via bill_group_id)
    └── order_log

user
shift (terikat ke outlet, bukan cuma ke user — field outlet_id wajib)
session (redis) — cart per-device, linked ke table_id
```

**Catatan desain penting:**
- `order` → `payment` relasinya **many-to-one**, bukan one-to-one, karena ada fitur bill merge di kasir
- `discount` dihubungkan ke `order` lewat tabel pivot `order_discount`, bukan foreign key langsung — supaya kalau discount di-hapus (expired), histori order tetap punya traceability lengkap (nominal + referensi diskon apa yang dipakai)
- `shift` punya `outlet_id` eksplisit — penting karena user bisa kerja di lebih dari 1 outlet, laporan closing per-outlet harus gak campur

## 7. Flow Order

```
Draft → [confirm] → Confirmed → [void] → Voided
                        │
              Kitchen accept → Preparing → [cancel, stok habis] → Cancelled
                        │
              Kitchen done → Done
```

## 8. UI Screens (Vue 3)

| Screen | Role | Platform |
|---|---|---|
| Login | All | Desktop + touch |
| PIN Quick-Switch | Kasir | Touchscreen |
| Dashboard | Admin | Desktop |
| Menu management | Admin | Desktop |
| POS Kasir | Cashier | Touchscreen |
| Kitchen Display (simplified) | Koki | Layar besar |
| QR Order (per-device cart) | Customer | Mobile |
| Shift | Cashier | Desktop |
| Laporan | Admin | Desktop |
| Manajemen user | Admin | Desktop |

## 9. Non-Functional
- Response API < 200ms (local)
- WebSocket delay < 1s
- Multi-outlet: data terisolasi per outlet
- Midtrans retry + queue
- Semua state change pake log (audit trail)
- Offline handling: **out of scope v1** — kasir butuh koneksi aktif (dipertimbangkan lagi di Phase 4 kalau perlu)
- Struk: minimal PDF/preview digital di v1, thermal printer didorong ke Phase 4
- Docker: 1 compose up = semua jalan
