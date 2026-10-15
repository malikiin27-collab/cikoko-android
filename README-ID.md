# Cikoko Android 2.3.0 — GPS dan galeri PPPK

Domain: https://cikoko.datawebs.my.id

Perbaikan: pilihan Galeri menghormati input HTML tanpa capture; kualitas GPS
maksimum 30 m, konfirmasi lokasi, status ACK yang membedakan penolakan, serta
patch estimasi rute dan ketidakpastian posisi pada website.

`server-patch` berisi file website yang perlu disalin ke document root cPanel.
Folder ini tidak menyertakan APK hasil build; APK debug hasil Actions berada di
artifact Cikoko-Tracking-APK-Uji. Setelah build, letakkan APK yang diinginkan
sebagai downloads/kelurahan-cikoko.apk untuk memperbarui menu Instal Aplikasi.

Workflow `.github/workflows` menjalankan tes dan lint serta menghasilkan APK.
Build membutuhkan JDK 17, Gradle 8.9, SDK platform 35 dan build-tools 35.0.0.

Package debug tetap id.cikoko.pegawai.uji2, versionCode 23. Debug keystore yang
berbeda membutuhkan uninstall APK lama setelah memastikan antrean tersinkron.
Untuk update tanpa uninstall gunakan signing key yang sama. Workflow rilis
mendukung secrets ANDROID_KEYSTORE_BASE64, ANDROID_STORE_PASSWORD,
ANDROID_KEY_ALIAS dan ANDROID_KEY_PASSWORD.

Lihat HASIL-UJI.txt untuk bukti pengujian dan batasnya. Belum diuji di HP fisik
atau database produksi. Target interval 5 detik bergantung GPS, jaringan dan OS.
