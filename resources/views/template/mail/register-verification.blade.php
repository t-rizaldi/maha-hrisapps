<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email MAHA Apps</title>
</head>
<body style="background-color: #FEFEFE; padding-bottom: 20px;">
    <div style="padding: 20px 30px; background: linear-gradient(310deg, #E91E21 -8.8%, #FEC1C2 93.72%); border-radius: 0px 0px 35% 35%;">
        <div style="text-align: center;">
            <img src="https://api.mahasejahtera.com/assets/img/logo-white.png" alt="" style="width: 164px;">
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <p style="font-size: 20px; font-weight: 600; color: #202020; margin: 0; padding: 0;">Verifikasi Akun MAHA APPS</p>

        <p style="font-size: 14px; color: #404040; margin: 25px 0px; padding: 0;">
            Hai <span style="font-weight: 600;">{{ $name }}</span>, Selamat datang di MAHA Apps. Segera verifikasi akunmu dengan menekan tombol verifikasi untuk dapat login kedalam aplikasi.
        </p>

        <a href="{{ $url }}" style="font-size: 12px; font-weight: 700; color: #FDFDFD; text-decoration: none; padding: 8px; border-radius: 5px; background-color: #E91E21; box-shadow: 0px 2px 20px 0px rgba(146, 178, 221, 0.25);">
            Verifikasi Akun
        </a>

        <p style="font-size: 14px; color: #404040; margin: 25px 0px; padding: 0;">
            Tombol verifikasi ini hanya berlaku selama <span style="font-weight: 600;">1 x 24 Jam</span> sejak pertama kali email ini diterima. Mohon jangan sebarkan kode verifikasi ini ke siapa pun, termasuk ke pihak yang mengatas namakan PT. Maha Akbar Sejahtera. Apabila ada kendala, saran, atau kritik, hubungi Admin melalui <a href="mailto:hrd@mahasejahtera.com" style="font-weight: 500;">hrd@mahasejahtera.com</a> atau WhatsApp <a href="https://wa.me/6281234567890" style="text-decoration: none; color: #404040;">+6281234567890</a>. Selamat bekerja bersama!
        </p>

        <p style="font-size: 14px; color: #404040; margin: 0px 0px; padding: 0;">
            Salam,
        </p>

        <p style="font-size: 14px; color: #404040; font-weight: 600; margin: 0px 0px; padding: 0;">
            Direktur
        </p>
    </div>
</body>
</html>
