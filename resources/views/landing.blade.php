{{--
    PesenApa — Landing page publik (halaman pertama sebelum daftar).
    Murni Blade + Tailwind dari resources/css/app.css, tanpa JS/Vue,
    jadi ringan dan langsung kebaca mesin pencari.
--}}
@php
    $btn = 'inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-white rounded-xl bg-gradient-to-br from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 shadow-md shadow-teal-600/20 transition';
    $btnGhost = 'inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-slate-700 rounded-xl bg-white border border-slate-200 hover:border-slate-400 transition';
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <title>PesenApa — Aplikasi kasir untuk cafe, kedai minuman, dan resto</title>
    <meta name="description" content="PesenApa membantu cafe, kedai minuman, dan resto mencatat pesanan per meja, menggabung bill, mengontrol stok bahan otomatis, dan memantau shift kasir." />
    <link rel="canonical" href="https://pesenapa.my.id/" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="PesenApa" />
    <meta property="og:title" content="PesenApa — Aplikasi kasir untuk cafe, kedai minuman, dan resto" />
    <meta property="og:description" content="Catat pesanan per meja, gabung bill, kontrol stok otomatis, dan pantau shift kasir dari satu aplikasi." />
    <meta property="og:url" content="https://pesenapa.my.id/" />
    @vite(['resources/css/app.css'])
</head>
<body>
<div class="text-slate-800 bg-white">

    {{-- ===== HERO ===== --}}
    <div class="relative overflow-hidden" style="background: linear-gradient(135deg, #f8fafc 0%, #f0fdfa 50%, #ecfdf5 100%);">
        <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-teal-100/40 blur-3xl"></div>
        <div class="absolute -bottom-32 -left-20 w-96 h-96 rounded-full bg-emerald-100/30 blur-3xl"></div>

        <header class="relative z-10 max-w-6xl mx-auto px-5 py-5 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3" aria-label="PesenApa, ke beranda">
                <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center shadow-lg shadow-teal-200/50">
                    <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </span>
                <span class="text-lg font-bold text-slate-900 tracking-tight">PesenApa</span>
            </a>
            <nav class="flex items-center gap-2">
                <a href="/login" class="px-4 py-2 text-sm font-semibold text-teal-700 rounded-lg hover:bg-teal-50 transition">Masuk</a>
                <a href="/register" class="{{ $btn }} !px-5 !py-2.5">Daftar</a>
            </nav>
        </header>

        <section class="relative z-10 max-w-6xl mx-auto px-5 pt-8 pb-16 lg:pt-14 lg:pb-24 grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 leading-tight tracking-tight">
                    Kasir yang ngerti cara kerja cafe, kedai minuman, dan resto.
                </h1>
                <p class="mt-5 text-lg text-slate-600 leading-relaxed max-w-xl">
                    Catat pesanan per meja, gabung bill saat pelanggan bayar, kontrol stok bahan otomatis, dan pantau shift kasir. Semua dari satu aplikasi.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="/register" class="{{ $btn }}">Daftar sekarang</a>
                    <a href="#fitur" class="{{ $btnGhost }}">Lihat fiturnya</a>
                </div>
                <p class="mt-4 text-sm text-slate-500">Baca dulu sampai bawah. Daftar kalau sudah cocok.</p>
                <ul class="mt-8 flex flex-wrap gap-2">
                    @foreach (['Pesanan real-time', 'Multi-outlet', 'Laporan otomatis'] as $pill)
                        <li class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-teal-100 text-teal-800 text-xs font-medium">
                            <svg class="w-3.5 h-3.5 text-teal-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                            {{ $pill }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Contoh tampilan: dua pesanan satu meja digabung jadi satu bill --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xl shadow-slate-200/60 p-4 space-y-3 w-full max-w-md lg:ml-auto"
                 role="img" aria-label="Contoh tampilan kasir: dua pesanan di Meja 4 digabung menjadi satu bill">
                <div class="flex items-center justify-between px-1">
                    <span class="text-sm font-semibold text-slate-700">Meja 4</span>
                    <span class="px-2.5 py-0.5 rounded-full bg-teal-100 text-teal-700 text-xs font-semibold">2 pesanan</span>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3 text-sm">
                    <div class="flex justify-between font-semibold text-slate-800 mb-1.5"><span>Pesanan 1</span><span class="font-normal text-slate-500">Budi</span></div>
                    <div class="flex justify-between text-slate-600 py-0.5"><span>2 × Es kopi susu</span><span>36.000</span></div>
                    <div class="flex justify-between text-slate-600 py-0.5"><span>1 × Kentang goreng</span><span>22.000</span></div>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3 text-sm">
                    <div class="flex justify-between font-semibold text-slate-800 mb-1.5"><span>Pesanan 2</span><span class="font-normal text-slate-500">Sari</span></div>
                    <div class="flex justify-between text-slate-600 py-0.5"><span>1 × Matcha latte</span><span>24.000</span></div>
                    <div class="flex justify-between text-slate-600 py-0.5"><span>1 × Roti bakar</span><span>18.000</span></div>
                </div>
                <div class="flex items-center justify-between rounded-xl px-4 py-3 text-white text-sm font-semibold shadow-md shadow-teal-600/20"
                     style="background: linear-gradient(135deg, #0d9488, #0f766e);">
                    <span>Satu bill, Meja 4<span class="block text-xs font-normal text-teal-100">Digabung saat kasir tutup</span></span>
                    <span>Rp100.000</span>
                </div>
            </div>
        </section>
    </div>

    <main>
        {{-- ===== CARA MULAI ===== --}}
        <section id="mulai" class="max-w-6xl mx-auto px-5 py-16 lg:py-20">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Mulai dalam empat langkah</h2>
            <p class="mt-3 text-slate-600 max-w-xl">Tidak perlu install atau setup server. Daftar, verifikasi email, lalu langsung masuk ke dashboard.</p>
            <ol class="mt-10 grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                @foreach ([
                    ['Daftar akun', 'Isi data akun Anda di halaman daftar.'],
                    ['Masukkan kode email', 'Kami kirim kode 6 digit ke email Anda untuk aktivasi.'],
                    ['Outlet pertama siap', 'Setelah verifikasi, Anda langsung login dan outlet pertama dibuat otomatis.'],
                    ['Atur dan mulai jualan', 'Tambah menu, meja, dan karyawan, lalu buka shift pertama.'],
                ] as $i => [$title, $desc])
                    <li>
                        <span class="w-9 h-9 rounded-full bg-gradient-to-br from-teal-500 to-teal-700 text-white text-sm font-bold flex items-center justify-center shadow-md shadow-teal-200/60">{{ $i + 1 }}</span>
                        <h3 class="mt-4 font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">{{ $desc }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- ===== FITUR ===== --}}
        <section id="fitur" class="bg-slate-50 border-y border-slate-200">
            <div class="max-w-6xl mx-auto px-5 py-16 lg:py-20">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Yang bisa Anda lakukan</h2>
                <p class="mt-3 text-slate-600 max-w-xl">Dikelompokkan sesuai siapa yang memakainya sehari-hari.</p>
                <div class="mt-10 grid lg:grid-cols-3 gap-6">
                    @foreach ([
                        'Di kasir' => [
                            ['Pesanan per meja', 'Beberapa pesanan di satu meja dikelompokkan otomatis.'],
                            ['Pisah dan gabung bill', 'Split atau gabung saat pelanggan membayar, plus refund bila perlu.'],
                            ['Diskon dan pajak', 'Atur sekali di Master Data, terhitung otomatis di setiap pesanan.'],
                            ['Login PIN', 'Kasir masuk cepat dengan PIN, terhubung ke shift aktif.'],
                            ['Pesanan masuk langsung', 'Tampil tanpa perlu memuat ulang halaman.'],
                        ],
                        'Menu dan stok' => [
                            ['Menu dan kategori', 'Kelola produk beserta pengelompokannya.'],
                            ['Bahan dan add-on', 'Catat bahan baku dan tambahan seperti topping atau ekstra shot.'],
                            ['Stok otomatis', 'Pilih stok per produk atau per bahan. Stok berkurang sendiri saat ada penjualan.'],
                            ['Meja dan QR', 'Setiap meja punya kode QR sendiri yang bisa dibuat ulang kapan saja.'],
                        ],
                        'Untuk pemilik' => [
                            ['Banyak outlet dan peran', 'Atur siapa boleh akses outlet mana dan sebagai apa.'],
                            ['Shift dan jadwal', 'Buka dan tutup shift, rekonsiliasi kas, serta jadwal karyawan.'],
                            ['Laporan Excel dan PDF', 'Laporan keuangan dan shift, termasuk audit karyawan.'],
                            ['Dashboard gabungan', 'Ringkasan semua outlet Anda di satu halaman.'],
                            ['Pembayaran online', 'Terhubung ke Midtrans dan Xendit.'],
                        ],
                    ] as $group => $items)
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-slate-900 pb-3 mb-2 border-b-2 border-teal-600">{{ $group }}</h3>
                            <ul class="divide-y divide-slate-100">
                                @foreach ($items as [$name, $text])
                                    <li class="flex gap-3 py-3">
                                        <svg class="w-4 h-4 mt-1 shrink-0 text-teal-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ $name }}</p>
                                            <p class="text-sm text-slate-600 leading-relaxed">{{ $text }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===== SEGERA HADIR ===== --}}
        <section class="max-w-6xl mx-auto px-5 py-16 lg:py-20">
            <div class="rounded-2xl p-8 sm:p-10 text-white relative overflow-hidden" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%);">
                <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-white/5 blur-3xl"></div>
                <div class="relative max-w-2xl">
                    <span class="inline-flex px-3 py-1 rounded-full bg-white/10 text-teal-100 text-xs font-medium">Segera hadir</span>
                    <h2 class="mt-4 text-2xl sm:text-3xl font-bold tracking-tight">Pelanggan pesan sendiri lewat QR di meja</h2>
                    <p class="mt-3 text-teal-100 leading-relaxed">Pelanggan scan QR di meja, lihat menu, sesuaikan pesanan, lalu kirim ke kasir tanpa antre. Fitur ini sedang dikembangkan.</p>
                </div>
            </div>
        </section>

        {{-- ===== FAQ ===== --}}
        <section class="max-w-3xl mx-auto px-5 pb-16 lg:pb-20">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Pertanyaan sebelum daftar</h2>
            <div class="mt-8">
                @foreach ([
                    ['Daftarnya ribet nggak?', 'Tidak. Isi data, masukkan kode 6 digit dari email, dan Anda langsung masuk ke dashboard dengan outlet pertama sudah dibuat.'],
                    ['Kalau satu meja pesan berkali-kali, bayarnya gimana?', 'Setiap pesanan tersimpan terpisah, lalu digabung jadi satu bill saat kasir menutup. Bill juga bisa dipisah kalau pelanggan mau bayar sendiri-sendiri.'],
                    ['Kasir login pakai apa?', 'PIN, jadi tidak perlu mengetik email dan password setiap kali ganti shift.'],
                    ['Stok bahan ikut berkurang otomatis?', 'Ya. Anda bisa memilih mencatat stok per produk atau per bahan, dan stok berkurang sendiri saat ada penjualan.'],
                    ['Bisa untuk lebih dari satu outlet?', 'Bisa. Shift, meja, dan data kasir dipisah per outlet, dan dashboard menampilkan ringkasan semuanya.'],
                    ['Berapa biayanya?', 'Hubungi kami untuk detail paket dan harga.'],
                ] as [$q, $a])
                    <details class="group border-b border-slate-200 py-4">
                        <summary class="flex items-center justify-between gap-4 cursor-pointer list-none font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                            {{ $q }}
                            <span class="text-2xl leading-none text-teal-600 transition-transform group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        {{-- ===== CTA AKHIR ===== --}}
        <section class="max-w-6xl mx-auto px-5 pb-20">
            <div class="rounded-2xl px-6 py-14 text-center" style="background: linear-gradient(135deg, #f8fafc 0%, #f0fdfa 50%, #ecfdf5 100%); border: 1px solid #e2e8f0;">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight max-w-md mx-auto">Siap coba di tempat usaha Anda?</h2>
                <p class="mt-3 text-slate-600">Buat akun, atur menu, lalu buka shift pertama.</p>
                <a href="/register" class="{{ $btn }} mt-8">Daftar sekarang</a>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200">
        <div class="max-w-6xl mx-auto px-5 py-6 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
            <span>&copy; {{ date('Y') }} PesenApa. All rights reserved.</span>
            <span class="flex gap-5">
                <a href="/login" class="hover:text-teal-700">Masuk</a>
                <a href="/register" class="hover:text-teal-700">Daftar</a>
            </span>
        </div>
    </footer>
</div>
</body>
</html>
