<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Kode Aktivasi</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',system-ui,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 20px">
<tr><td align="center">
<table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06)">
<tr><td style="background:linear-gradient(135deg,#0d9488,#0f766e);padding:32px 40px;text-align:center">
<div style="width:48px;height:48px;background:#fff;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px">
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0f766e" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
</div>
<h1 style="color:#fff;font-size:20px;font-weight:700;margin:0">Aktivasi Akun</h1>
<p style="color:#ccfbf1;font-size:13px;margin:4px 0 0;opacity:0.8">POS Admin</p>
</td></tr>
<tr><td style="padding:32px 40px">
<p style="font-size:14px;color:#1e293b;margin:0 0 6px">Halo, <strong>{{ $userName }}</strong></p>
<p style="font-size:13px;color:#475569;margin:0 0 20px;line-height:1.5">Terima kasih sudah mendaftar. Gunakan kode aktivasi berikut untuk memverifikasi email Anda:</p>
<div style="background:#f0fdfa;border:1px solid #ccfbf1;border-radius:12px;padding:20px;text-align:center;margin-bottom:20px">
<div style="font-size:36px;font-weight:800;letter-spacing:12px;color:#0d9488;font-family:monospace">{{ $code }}</div>
</div>
<p style="font-size:12px;color:#64748b;margin:0;line-height:1.5">Kode ini berlaku selama <strong>30 menit</strong>. Jika Anda tidak mendaftar, abaikan email ini.</p>
</td></tr>
<tr><td style="padding:0 40px 24px;text-align:center">
<p style="font-size:11px;color:#94a3b8;margin:0">&copy; {{ date('Y') }} POS Admin. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
