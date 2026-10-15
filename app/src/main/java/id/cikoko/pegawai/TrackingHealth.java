package id.cikoko.pegawai;

import android.Manifest;
import android.app.*;
import android.content.*;
import android.content.pm.PackageManager;
import android.location.LocationManager;
import android.os.*;
import android.provider.Settings;
import org.json.JSONObject;

/** Status from the actual service in this process, not the web attendance session. */
final class TrackingHealth {
    static volatile String message="Layanan GPS belum berjalan", provider="Belum dipilih";
    static volatile long fixAt, sentAt, taskRemovedAt;
    static JSONObject status(Context c) throws Exception {
        NotificationManager nm=(NotificationManager)c.getSystemService(Context.NOTIFICATION_SERVICE);
        NotificationChannel channel=nm.getNotificationChannel("tracking");
        LocationManager lm=(LocationManager)c.getSystemService(Context.LOCATION_SERVICE);
        boolean gps=lm!=null && (lm.isProviderEnabled(LocationManager.GPS_PROVIDER)||lm.isProviderEnabled(LocationManager.NETWORK_PROVIDER));
        return new JSONObject().put("type","health").put("version",BuildConfig.VERSION_NAME)
            .put("device",Build.MANUFACTURER+" "+Build.MODEL).put("android",Build.VERSION.RELEASE)
            .put("running",TrackingService.running).put("message",message).put("provider",provider)
            .put("fix_at",fixAt).put("sent_at",sentAt).put("task_removed_at",taskRemovedAt)
            .put("precise",c.checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION)==PackageManager.PERMISSION_GRANTED)
            .put("background",Build.VERSION.SDK_INT<29 || c.checkSelfPermission(Manifest.permission.ACCESS_BACKGROUND_LOCATION)==PackageManager.PERMISSION_GRANTED)
            .put("notifications",nm.areNotificationsEnabled() && (channel==null||channel.getImportance()!=NotificationManager.IMPORTANCE_NONE))
            .put("location_enabled",gps)
            .put("battery_unrestricted",((PowerManager)c.getSystemService(Context.POWER_SERVICE)).isIgnoringBatteryOptimizations(c.getPackageName()));
    }
    static void settings(Activity a) {
        String maker=Build.MANUFACTURER.toLowerCase(java.util.Locale.ROOT);
        String note="Periksa pembatasan baterai dan izin berjalan di latar belakang pada pengaturan aplikasi HP.";
        if(maker.contains("xiaomi")||maker.contains("redmi")||maker.contains("poco"))note="Xiaomi/Redmi/POCO: aktifkan Mulai otomatis latar belakang, pilih Baterai Tanpa batas, dan kunci Cikoko pada aplikasi terbaru agar tidak ikut Bersihkan semua.";
        else if(maker.contains("samsung"))note="Samsung: pilih baterai Tidak dibatasi dan periksa daftar Aplikasi tidur/tidur lelap. Keluarkan Cikoko dari daftar tersebut selama bertugas.";
        else if(maker.contains("oppo")||maker.contains("realme")||maker.contains("oneplus"))note="OPPO/realme/OnePlus: periksa aktivitas latar belakang dan mulai otomatis pada pengelolaan baterai aplikasi; kunci kartu aplikasi jika tersedia.";
        else if(maker.contains("vivo")||maker.contains("iqoo"))note="vivo/iQOO: periksa izin mulai otomatis dan penggunaan daya latar belakang; nama menu bergantung versi Funtouch OS.";
        else if(maker.contains("huawei")||maker.contains("honor"))note="Huawei/Honor: periksa Peluncuran aplikasi dan izin mulai otomatis serta berjalan di latar belakang. GPS perangkat dipakai bila layanan Google tidak tersedia.";
        final String guidance=note;
        new AlertDialog.Builder(a).setTitle("Pengaturan tracking perangkat")
            .setItems(new String[]{"Izin lokasi & notifikasi", "Optimasi baterai", "Panduan "+Build.MANUFACTURER},(d,which)->{
                if(which==2){new AlertDialog.Builder(a).setTitle("Panduan perangkat").setMessage(guidance+"\n\nPengaturan ini memerlukan pilihan Anda. Paksa berhenti, HP mati, atau uninstall menghentikan tracking. Target 5 detik bergantung GPS, jaringan dan sistem HP.").setPositiveButton("OK",null).show();return;}
                Intent intent=which==1?new Intent(Settings.ACTION_IGNORE_BATTERY_OPTIMIZATION_SETTINGS):new Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS,android.net.Uri.parse("package:"+a.getPackageName()));
                try{a.startActivity(intent);}catch(Exception e){android.widget.Toast.makeText(a,"Buka Setelan HP > Aplikasi > Cikoko secara manual.",android.widget.Toast.LENGTH_LONG).show();}
            }).setNegativeButton("Tutup",null).show();
    }
}
