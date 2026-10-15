# Cikoko Android 2.2.0 — beberapa merek perangkat

Target: Android 8 (API 26) atau lebih baru, target API 35. Bukan aplikasi iPhone.
Kompatibilitas semua model tidak dapat dijamin tanpa pengujian perangkat.

## Isi pembaruan
- Foreground service lokasi, notifikasi, target GPS 5 detik dan stopWithTask=false.
- START_STICKY meminta pemulihan layanan oleh Android bila sistem mengizinkan;
  ini bukan jaminan pulih setelah dibersihkan atau Paksa berhenti.
- Google Fused Location pada perangkat yang mendukungnya, dengan fallback GPS
  Android saat Google Play services tidak tersedia atau permintaan Fused gagal.
- Panel web D14 membaca status nyata dari APK: layanan berjalan/berhenti,
  waktu lokasi diterima, waktu pengiriman diakui server, penyedia GPS,
  izin lokasi akurat, notifikasi dan pengecualian optimasi baterai.
- Tombol pengaturan Android standar dan panduan untuk Xiaomi/Redmi/POCO,
  Samsung, OPPO/realme/OnePlus, vivo/iQOO, Huawei/Honor serta panduan umum.
  Menu bergantung versi sistem; aplikasi tidak mengubah pengaturan tanpa pengguna.
- Izin lokasi latar belakang opsional dengan penjelasan; Android 10 meminta
  izin terpisah, Android 11+ membuka detail aplikasi untuk memilih izin.
  Izin ini membantu akses lokasi saat pemulihan layanan yang diizinkan OS;
  tidak menghapus larangan memulai layanan dari latar belakang.
- Token harus dikonfirmasi tersimpan sebelum perintah tracking diberikan.
- Tombol Back pada halaman awal memindahkan aplikasi ke latar belakang.
- Antrean GPS lokal dan validasi waktu/akurasi tetap digunakan. Titik tidak
  dibuat-buat saat GPS tidak tersedia. Sesi harus aktif dan tervalidasi server.

## Instal paket uji
1. Backup berkas website yang terdapat dalam server-patch.
2. Salin ISI server-patch ke document root cikoko.datawebs.my.id.
   ui.php, pegawai/tracking.php, assets/tracking-client.js, assets/native-sync.js
   menggantikan versi sebelumnya. api/mobile/.htaccess adalah patch D13;
   bila hosting sudah memiliki aturan tambahan dalam file itu, gabungkan isinya.
   Jangan hapus situs/database/storage. Patch login tanpa PIN tetap terpisah.
3. Hentikan aplikasi Cikoko Tracking Uji lama melalui Info aplikasi > Paksa henti
   sebelum mencoba versi baru, agar tidak ada dua pengirim. Jangan uninstall
   aplikasi lama jika masih memiliki data yang belum terkirim.
4. Instal APK Cikoko-Tracking-Uji-2.2.0.apk dari folder APK dalam paket ini.
   Nama aplikasi: Cikoko Tracking Uji 2. Package: id.cikoko.pegawai.uji2.
   Ini instalasi terpisah; sesi login lokal dan antrean aplikasi lama tidak dipindah.
   Sesi jam kerja di server dapat dilanjutkan oleh akun yang sama.
5. Login, buka Jam Kerja & Lokasi; jika sesi masih aktif tidak perlu check-in ulang.
6. Izinkan lokasi akurat dan notifikasi. Gunakan tombol Pengaturan tracking
   untuk memeriksa optimasi baterai dan panduan merek HP. Izin lokasi latar
   belakang dapat dipilih melalui tombol terpisah setelah membaca penjelasan.
7. Periksa panel D14: layanan berjalan, waktu lokasi dan pengiriman berubah,
   notifikasi terlihat, serta admin menerima titik baru.
8. Uji Home, layar terkunci 5 menit, geser kartu recent apps, dan Bersihkan semua
   secara terpisah. Pantau admin tanpa membuka ulang APK selama tiap pengujian.
   Jika berhenti, catat tindakan, waktu, model HP, dan Android dari panel D14.

## Batas kemampuan
Sistem vendor dapat mematikan layanan walau foreground service digunakan.
Paksa berhenti, uninstall, HP mati, izin dicabut, GPS mati atau internet putus
mempengaruhi tracking. Tidak ada jaminan universal 5 detik pada semua HP.
Kunci kartu aplikasi/autostart/tanpa batas mungkin diperlukan pada beberapa HP.
Tidak ada alarm berulang untuk melawan Paksa berhenti atau izin pengguna.
Tidak ada pelacakan di luar sesi kerja aktif. Tidak ada klaim lulus uji semua model.

## GitHub
Upload isi folder cikoko-android-main ke repository Android (termasuk .github),
bukan folder APK. Actions > APK Cikoko Tracking > Run workflow.
Workflow menjalankan tes aturan GPS, build, dan lint lalu menyediakan APK uji.
Signing rilis memakai secret milik pengelola: ANDROID_KEYSTORE_BASE64,
ANDROID_STORE_PASSWORD, ANDROID_KEY_ALIAS, ANDROID_KEY_PASSWORD. Gunakan key
rilis lama untuk pembaruan aplikasi resmi; key itu tidak disertakan paket ini.
Debug key dari build berbeda dapat berbeda. Paket uji ini bukan rilis produksi.
