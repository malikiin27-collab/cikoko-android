APLIKASI ANDROID CIKOKO — GITHUB ACTIONS

Proyek untuk https://datawebs.my.id/cikoko/. Package id.cikoko.pegawai, versi 2.0.0. Login memakai website Cikoko sehingga form email/password/PIN PPPK dan username/password PJLP mengikuti website terbaru. Native bridge CikokoNative/CikokoAddress, kamera bawaan dan layanan GPS foreground dari sumber Android Melawai.

CARA MEMBANGUN TANPA ANDROID STUDIO
1. Buat repository GitHub baru dengan nama cikoko-android, sebaiknya private.
2. Ekstrak ZIP di komputer. Upload ISI folder hasil ekstrak ke root repository: app/, settings.gradle, build.gradle, gradle.properties, README-ID.md dan .github/workflows/build-apk.yml. Jangan taruh semuanya di subfolder Android atau project.
3. Folder .github tersembunyi di Mac: tekan Command+Shift+titik untuk menampilkannya. Jika upload browser melewatkannya, buat file .github/workflows/build-apk.yml lewat Add file > Create new file dan salin isi file workflow yang disertakan.
4. Commit ke main. Buka Actions > APK Cikoko. Jika Actions tidak aktif, aktifkan untuk repository tersebut. Klik Run workflow jika build belum dimulai.
5. Setelah status hijau, buka run tersebut. Di Artifacts unduh Cikoko-Pegawai-APK. Ekstrak hasil download dan ambil app-debug.apk.
6. Salin APK ke HP Android, izinkan pemasangan dari sumber tersebut, lalu instal. Jika aplikasi Cikoko sebelumnya memakai penandatangan berbeda, update dapat ditolak: backup kebutuhan pengguna sebelum memilih uninstall. Debug APK ini untuk uji coba; build GitHub yang berbeda dapat memakai debug key berbeda. Untuk pembaruan produksi konsisten, gunakan release signing dengan keystore tetap melalui GitHub Secrets.
7. Masuk, izinkan lokasi akurat dan notifikasi, lalu mulai bertugas. Layanan lokasi berjalan selama sesi aktif dengan notifikasi. Android tidak mengizinkan jaminan tetap berjalan setelah Paksa Berhenti. Pembatasan baterai perangkat juga dapat menghentikan layanan.

Tidak perlu mengunggah file cPanel, SQL, config.php, password database, atau data pegawai ke repository. Ini hanya kode Android.
Pastikan patch login Cikoko sebelumnya terpasang bila PJLP login memakai nama lengkap. API bridge/status/points_batch pada ZIP Cikoko (3) tersedia. Semua data disimpan oleh website, bukan database baru.

Status: proyek dan workflow disiapkan, struktur/manifest/konfigurasi diperiksa. Belum dibangun oleh GitHub Actions dalam sesi ini dan belum diuji di perangkat; ZIP ini bukan APK siap instal. Kamera, unduh PDF, token, dan tracking perlu diuji dengan akun nyata sesudah build.
