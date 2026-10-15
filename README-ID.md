# Cikoko Android — Tracking 5 Detik (2.1.0)

Server: https://cikoko.datawebs.my.id

## Yang diperbarui

- Mengganti domain lama /cikoko dengan subdomain yang sesuai config.php website.
- Foreground service lokasi dimulai dari aplikasi terlihat setelah Mulai Bekerja. Notifikasi menandai sesi aktif. stopWithTask=false dan START_STICKY dipertahankan untuk swipe recent apps dan pemulihan proses jika diizinkan Android.
- Target pembacaan GPS 5 detik, termasuk saat diam. Tidak mengirim koordinat lama sebagai titik baru. Interval aktual tetap bergantung pada GPS, Android, baterai dan jaringan.
- Pencatatan GPS/SQLite dan jaringan berjalan pada thread berbeda. Server lambat tidak menghentikan pencatatan GPS.
- Titik mempunyai UUID, session_id, waktu pengambilan asli, akurasi dan kecepatan. Titik lama >15 detik, akurasi >100 meter, koordinat tidak valid, lompatan tidak wajar dan lokasi mock yang ditandai Android disaring. Deteksi mock bukan jaminan mencegah semua pemalsuan GPS.
- Antrean persisten maksimal 20.000 titik. Pengiriman mendahulukan satu titik terbaru lalu 99 titik paling lama. Titik dihapus hanya setelah server mengakui ID-nya; ditolak server juga dikeluarkan dari antrean dan diberitahukan di notifikasi.
- Sesi yang terakhir terverifikasi boleh mencatat tanpa internet hingga 12 jam sejak verifikasi terakhir. Setelah itu menunggu verifikasi kembali. Server batch menyaring titik lebih tua dari 24 jam.
- Selesai Bekerja menghentikan pencatatan; pengiriman antrean yang tersisa diselesaikan. Logout menghentikan layanan dan membersihkan token sesi lokal.
- CPU wake lock berbatas waktu 30 detik hanya digunakan selama permintaan status/upload, bukan untuk menyalakan layar.
- Menghapus penyisipan CSS APK lama yang menimpa navbar website.

## Pasang website terlebih dahulu

1. Pasang 16 file perubahan dari paket Cikoko-TRACKING-WEB-5DETIK.zip yang sebelumnya diberikan, sesuai BACA-DULU-TRACKING.txt.
2. Salin isi server-patch/ (ui.php dan assets/native-sync.js) ke document root Cikoko. ui.php hanya menaikkan versi URL native-sync agar cache lama dilewati. Atau ekstrak PATCH-Cikoko-Native-Sync.zip di document root setelah membuat cadangan file lama.
3. Tutup dan buka ulang aplikasi agar JavaScript terbaru dimuat.

Patch native-sync penting: mencegah pertukaran token berulang pada setiap pembaruan status 10 detik dalam satu halaman. Token diotorisasi kembali ketika halaman dimuat ulang. Tanpa patch ini, layanan sering mengulang validasi sesi.

Tidak ada perubahan database/destruksi file pada patch native-sync. Google Maps tersedia melalui tautan posisi di website; peta live Google di dashboard belum diaktifkan karena memerlukan API key/billing milik pengguna.

## APK uji dan APK resmi

Debug menggunakan ID id.cikoko.pegawai.uji, nama Cikoko Tracking Uji. Bisa dipasang berdampingan dengan aplikasi lama. Sebelum menguji, selesaikan sesi kerja aplikasi lama dan gunakan satu aplikasi saja untuk pencatatan. Jangan menjalankan dua pelacak untuk sesi pekerja yang sama.

Release tetap id.cikoko.pegawai, versionCode 21. Untuk memperbarui aplikasi lama tanpa uninstall, release WAJIB ditandatangani dengan keystore/certificate yang sama dengan APK terpasang. Jika versi terpasang >=21, naikkan versionCode sebelum build release. Jangan menghapus aplikasi dengan antrean GPS yang belum terkirim.

## Build lewat GitHub tanpa Android Studio

1. Ekstrak ZIP source. Buka folder cikoko-android-main.
2. Pada repository GitHub lama, perbarui file proyek dengan isi folder ini. app/, tests/, server-patch/, build.gradle, settings.gradle dan .github/ harus berada di ROOT repository; jangan menaruh folder cikoko-android-main sebagai folder bertingkat tambahan.
3. Perhatikan folder .github yang tersembunyi di sebagian file manager. Perbarui .github/workflows/build-apk.yml melalui editor GitHub jika upload folder menyembunyikannya.
4. Commit changes. Buka Actions > APK Cikoko Tracking > Run workflow (atau build otomatis dari push main).
5. Tunggu status hijau. Di bagian Artifacts unduh Cikoko-Tracking-APK-Uji, ekstrak ZIP artifact, lalu pasang app-debug.apk.

Untuk rilis resmi, tambahkan Repository Secrets berikut di Settings > Secrets and variables > Actions:
- ANDROID_KEYSTORE_BASE64: keystore lama dalam base64.
- ANDROID_STORE_PASSWORD
- ANDROID_KEY_ALIAS
- ANDROID_KEY_PASSWORD

Workflow hanya membangun APK resmi bila secret keystore tersedia. Kunci tidak dimasukkan ke source. Artifact resminya Cikoko-Tracking-APK-Resmi. Tanpa secrets, hanya APK uji dibuat. Setelah APK resmi teruji, barulah ganti downloads/kelurahan-cikoko.apk pada website dengan APK resmi tersebut.

## Build lokal

Java 17, Gradle 8.9, Android SDK 35 (AGP dapat memasang build-tools 34 otomatis):

    gradle :app:assembleDebug :app:lintDebug

Untuk signing release lokal, set CI_KEYSTORE_PATH (path absolut), CI_STORE_PASSWORD, CI_KEY_ALIAS, CI_KEY_PASSWORD lalu jalankan gradle :app:assembleRelease. Jangan commit nilai signing ke Git.

## Uji di HP dan admin (wajib sebelum dipakai seluruh pegawai)

1. Beri izin Lokasi akurat dan notifikasi. Aktifkan Lokasi HP. Pilih baterai Tanpa batas/data latar belakang sesuai merek perangkat.
2. Login, Mulai Bekerja dengan foto, pastikan notifikasi tracking muncul dan admin menerima fix GPS baru.
3. Bergerak di luar ruangan, cek koordinat/akurasi/waktu asli di admin. Diam 1 menit: pembacaan baru tetap ditargetkan 5 detik.
4. Kunci layar 10–15 menit. Pantau dari perangkat admin yang berbeda. Ulangi setelah swipe aplikasi dari recent apps.
5. Matikan internet 2 menit sambil bergerak, lalu aktifkan: antrean terkirim, posisi terbaru muncul lebih dahulu, riwayat tertunda menyusul.
6. Selesai Bekerja: sesi tidak lagi aktif; layanan berhenti setelah antrean selesai. Pastikan tidak ada titik baru setelah checkout.
7. Periksa login, kamera, unggah foto, unduh PDF dan navbar pada APK uji.
8. Uji Paksa Berhenti/izin lokasi dicabut: tracking memang berhenti dan admin menjadi tidak terkini; buka aplikasi kembali untuk melanjutkan sesi yang masih aktif.

HP mati, uninstall, Paksa Berhenti, izin dicabut dan pembatasan sistem tidak dapat dilampaui aplikasi. Tidak ada alarm/boot receiver yang memaksa mengaktifkan pelacakan di luar izin Android. Tidak ada jaminan tepat 5 detik pada semua perangkat. Saat offline, admin hanya dapat melihat lokasi terakhir yang diterima.
