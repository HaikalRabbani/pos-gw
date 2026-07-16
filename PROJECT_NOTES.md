### Pending / Next
- [x] Export laporan Excel/PDF
- [x] Inventory / Bahan & Add-on — ingredients + product_ingredients pivot (backend + halaman khusus di Master Data)
- [x] Stok ganda — Mode Per-Produk / Per-Bahan (BOM) + auto-deduct pas bayar
- [ ] POS Mobile (Flutter) — integrasi API
- [ ] Web Self-Order via QR — pelanggan scan QR meja → pesan sendiri
- [ ] Manajemen metode pembayaran (Flutter/QR)
- [x] Optimasi N+1 di Discount model accessors
- [x] Testing — unit test OrderService, ShiftService, ReportService
- [x] Export laporan Excel/PDF (Shift)
- [x] Absensi karyawan — sudah tercakup di shift (start_at/end_at per user). Tinggal nambah view Audit Shift di ShiftReport.vue

### 🏗️ Saran Fitur Besar (masih relevan)
| Fitur | Deskripsi | Status |
|---|---|---|
| Inventory BOM (lanjutan) | Stok bahan baku otomatis terpotong + stok opname | ✅ Backend + halaman Bahan siap. Butuh stok opname & alert stok minim |
| Customer & membership | Tabel customers, histori transaksi, tier diskon | ⬜ |
