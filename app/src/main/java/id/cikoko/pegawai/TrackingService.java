package id.cikoko.pegawai;

import android.Manifest;
import android.app.*;
import android.content.*;
import android.content.pm.*;
import android.location.Location;
import android.os.*;
import com.google.android.gms.location.*;
import org.json.*;
import java.io.*;
import java.net.*;
import java.nio.charset.StandardCharsets;
import java.util.UUID;
import java.util.concurrent.*;

/** GPS/SQLite state is serialized on worker; network never blocks location callbacks. */
public class TrackingService extends Service {
    static volatile boolean running = false;
    private HandlerThread thread;
    private Handler worker;
    private final ExecutorService network = Executors.newSingleThreadExecutor();
    private FusedLocationProviderClient fused;
    private LocationCallback callback;
    private android.location.LocationManager platformGps;
    private android.location.LocationListener platformListener;
    private boolean usePlatformGps;
    private PointQueue queue;
    private PowerManager.WakeLock uploadLock;
    private String token = "";
    private long sessionId, validUntil, lastAcceptedElapsed, generation;
    private Location previous, pendingFix;
    private boolean gpsRequested, inFlight, finishing;
    private volatile boolean destroyed;

    @Override public void onCreate() {
        super.onCreate();
        NotificationManager nm = (NotificationManager)getSystemService(NOTIFICATION_SERVICE);
        nm.createNotificationChannel(new NotificationChannel("tracking", "Lokasi selama bertugas", NotificationManager.IMPORTANCE_LOW));
        thread = new HandlerThread("CikokoGpsQueue"); thread.start();
        worker = new Handler(thread.getLooper()); queue = new PointQueue(this);
        usePlatformGps=com.google.android.gms.common.GoogleApiAvailability.getInstance().isGooglePlayServicesAvailable(this)!=com.google.android.gms.common.ConnectionResult.SUCCESS;
        if(!usePlatformGps)fused = LocationServices.getFusedLocationProviderClient(this);
        platformGps=(android.location.LocationManager)getSystemService(LOCATION_SERVICE);
        platformListener=new android.location.LocationListener(){
            public void onLocationChanged(Location location){capture(location);}
            public void onStatusChanged(String provider,int status,Bundle extras){}
            public void onProviderEnabled(String provider){}
            public void onProviderDisabled(String provider){notice("Lokasi HP dinonaktifkan · aktifkan kembali GPS");}
        };
        uploadLock = ((PowerManager)getSystemService(POWER_SERVICE)).newWakeLock(PowerManager.PARTIAL_WAKE_LOCK,"Cikoko:GPSUpload");
        uploadLock.setReferenceCounted(false);
        callback = new LocationCallback() {
            @Override public void onLocationResult(LocationResult result) {
                for (Location location : result.getLocations()) capture(location);
            }
            @Override public void onLocationAvailability(LocationAvailability a) {
                if (!a.isLocationAvailable()) notice("Sinyal GPS belum tersedia · periksa Lokasi HP");
            }
        };
    }
    private Notification notification(String message) {
        PendingIntent open = PendingIntent.getActivity(this,0,new Intent(this,MainActivity.class),PendingIntent.FLAG_IMMUTABLE|PendingIntent.FLAG_UPDATE_CURRENT);
        return new Notification.Builder(this,"tracking").setSmallIcon(R.drawable.ic_location)
            .setContentTitle("Cikoko · Tracking jam kerja aktif").setContentText(message)
            .setContentIntent(open).setOngoing(true).setOnlyAlertOnce(true).build();
    }
    private void notice(String message) {
        TrackingHealth.message=message;
        if (!running || destroyed) return;
        ((NotificationManager)getSystemService(NOTIFICATION_SERVICE)).notify(44,notification(message));
    }
    @Override public int onStartCommand(Intent intent,int flags,int startId) {
        if (checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION)!=PackageManager.PERMISSION_GRANTED) {
            TrackingHealth.message="Izin lokasi akurat belum tersedia";stopSelf(); return START_NOT_STICKY;
        }
        String saved=TokenStore.read(this);
        if (saved.isEmpty()) { TrackingHealth.message="Token aplikasi belum tersedia · buka kembali halaman kerja";stopSelf(); return START_NOT_STICKY; }
        try {
            if (Build.VERSION.SDK_INT>=29) startForeground(44,notification("Memeriksa sesi kerja dan GPS"),ServiceInfo.FOREGROUND_SERVICE_TYPE_LOCATION);
            else startForeground(44,notification("Memeriksa sesi kerja dan GPS"));
        } catch (RuntimeException e) { TrackingHealth.message="Sistem menolak layanan: "+e.getClass().getSimpleName();stopSelf(); return START_NOT_STICKY; }
        running=true;
        boolean flush=intent!=null && "FLUSH".equals(intent.getAction());
        worker.post(()->{
            if(destroyed)return;
            if(!saved.equals(token)) {
                generation++; token=saved; sessionId=0; previous=null; lastAcceptedElapsed=0;
                SharedPreferences state=getSharedPreferences("tracking_state",0);
                if(tokenHash().equals(state.getString("token_hash",""))) {
                    validUntil=state.getLong("until",0);
                    if(validUntil>System.currentTimeMillis()) sessionId=state.getLong("session",0);
                }
            }
            if(flush) { finishing=true;generation++;clearSession();stopGps(); }
            else { finishing=false; startGps(); }
            worker.removeCallbacks(tick);worker.post(tick);
        });
        return flush?START_NOT_STICKY:START_STICKY;
    }
    private void startGps() {
        if(gpsRequested || finishing || destroyed)return;
        if(usePlatformGps){startPlatformGps();return;}
        TrackingHealth.provider="Google Fused Location";
        LocationRequest request=new LocationRequest.Builder(Priority.PRIORITY_HIGH_ACCURACY,TrackingPolicy.INTERVAL_MS)
            .setMinUpdateIntervalMillis(TrackingPolicy.INTERVAL_MS).setMaxUpdateDelayMillis(0)
            .setMaxUpdateAgeMillis(0).setMinUpdateDistanceMeters(0).setWaitForAccurateLocation(true).build();
        try {
            gpsRequested=true;
            fused.requestLocationUpdates(request,callback,thread.getLooper()).addOnFailureListener(error->worker.post(()->{
                if(destroyed||finishing)return;gpsRequested=false;usePlatformGps=true;notice("Beralih ke GPS perangkat");startPlatformGps();
            }));
        }catch(SecurityException e){gpsRequested=false;notice("Izin lokasi dicabut · buka aplikasi");stopSelf();}
    }
    private void startPlatformGps() {
        if(destroyed||finishing||gpsRequested)return;
        TrackingHealth.provider="GPS perangkat Android";
        try {
            if(platformGps==null||!platformGps.isProviderEnabled(android.location.LocationManager.GPS_PROVIDER)){notice("Aktifkan Lokasi/GPS pada pengaturan HP");return;}
            platformGps.requestLocationUpdates(android.location.LocationManager.GPS_PROVIDER,TrackingPolicy.INTERVAL_MS,0,platformListener,thread.getLooper());
            gpsRequested=true;
        }catch(SecurityException e){notice("Izin lokasi dicabut · buka aplikasi");stopSelf();}
        catch(RuntimeException e){gpsRequested=false;notice("GPS perangkat belum tersedia");}
    }
    private void stopGps() {
        gpsRequested=false;
        if(platformGps!=null && platformListener!=null)platformGps.removeUpdates(platformListener);
        if(fused!=null && callback!=null)fused.removeLocationUpdates(callback);
    }
    @SuppressWarnings("deprecation") private void capture(Location location) {
        if(destroyed || finishing || sessionId<=0)return;
        if(System.currentTimeMillis()>validUntil) {notice("Sesi belum terverifikasi lebih dari 12 jam · sambungkan internet");return;}
        long elapsed=location.getElapsedRealtimeNanos()/1000000L;
        long age=SystemClock.elapsedRealtime()-elapsed;
        boolean mock=Build.VERSION.SDK_INT>=31?location.isMock():location.isFromMockProvider();
        if(!location.hasAccuracy() || !TrackingPolicy.validFix(location.getLatitude(),location.getLongitude(),location.getAccuracy(),age,mock)) {
            notice("GPS belum akurat (perlu ±30 m atau lebih baik) · menunggu lokasi baru");return;
        }
        TrackingHealth.accuracy=location.getAccuracy();
        if(!TrackingPolicy.due(elapsed,lastAcceptedElapsed))return;
        boolean moving=location.hasSpeed() && location.hasSpeedAccuracy()
            && location.getSpeed()-location.getSpeedAccuracyMetersPerSecond()>0.7f;
        double gap=previous==null?Double.POSITIVE_INFINITY:(elapsed-previous.getElapsedRealtimeNanos()/1000000L)/1000.0;
        boolean confirm=previous==null||gap>30||(!moving && location.distanceTo(previous)>Math.max(20,2*(location.getAccuracy()+previous.getAccuracy())));
        if(confirm){
            long pendingAge=pendingFix==null?0:elapsed-pendingFix.getElapsedRealtimeNanos()/1000000L;
            boolean consistent=pendingFix!=null && TrackingPolicy.confirmed(pendingAge,location.distanceTo(pendingFix),location.getAccuracy(),pendingFix.getAccuracy(),moving?location.getSpeed():0);
            if(!consistent){pendingFix=new Location(location);notice("Memastikan lokasi stabil · menunggu sampel GPS berikutnya");return;}
        }
        pendingFix=null;
        if(previous!=null) {
            double seconds=(location.getElapsedRealtimeNanos()-previous.getElapsedRealtimeNanos())/1e9;
            if(seconds<=0 || location.distanceTo(previous)>45*seconds+location.getAccuracy()+previous.getAccuracy()) {
                notice("Lonjakan GPS disaring · menunggu posisi stabil");return;
            }
        }
        try {
            float speed=location.hasSpeed()?location.getSpeed():0;
            if(!Float.isFinite(speed)||speed<0||speed>100)return;
            JSONObject point=new JSONObject().put("id",UUID.randomUUID().toString()).put("session_id",sessionId)
                .put("lat",location.getLatitude()).put("lng",location.getLongitude()).put("acc",location.getAccuracy())
                .put("fix_epoch",location.getTime()/1000).put("speed",speed);
            if(!queue.add(point)) {notice("Antrean penuh · sambungkan internet");return;}
            TrackingHealth.fixAt=location.getTime();
            previous=new Location(location);lastAcceptedElapsed=elapsed;
            sync(); // Fused callbacks also wake upload work while the screen is locked.
        }catch(Exception e){notice("GPS belum tersimpan · periksa ruang penyimpanan");}
    }
    private final Runnable tick=new Runnable(){@Override public void run(){
        if(destroyed)return;
        if(!finishing && !gpsRequested)startGps();
        sync();worker.postDelayed(this,TrackingPolicy.INTERVAL_MS);
    }};
    private void sync() {
        if(inFlight || destroyed || token.isEmpty())return;
        inFlight=true;
        final long attempt=generation;final String credential=token;
        uploadLock.acquire(30000); // bounded CPU lock only while status/upload is in flight
        network.execute(()->{
            try {
                JSONObject status=api("status",null,credential);
                worker.post(()->{
                    if(!current(attempt)){finishAttempt();return;}
                    try {
                        long uid=status.optLong("uid");if(uid<=0)throw new IOException("Status server tanpa UID");
                        queue.owner(String.valueOf(uid));
                        JSONObject session=status.optJSONObject("session");
                        long next=!finishing && status.optBoolean("active") && session!=null?session.optLong("id"):0;
                        if(next!=sessionId){previous=null;pendingFix=null;lastAcceptedElapsed=0;TrackingHealth.fixAt=0;TrackingHealth.sentAt=0;TrackingHealth.serverFixAt=0;}
                        sessionId=next;
                        if(next>0) {
                            validUntil=System.currentTimeMillis()+TrackingPolicy.OFFLINE_LEASE_MS;
                            getSharedPreferences("tracking_state",0).edit().putLong("session",next).putLong("until",validUntil).putString("token_hash",tokenHash()).apply();
                        } else {clearSession();stopGps();finishing=true;}
                        JSONArray batch=queue.batch();
                        if(batch.length()==0){notice(next>0?(System.currentTimeMillis()-TrackingHealth.fixAt<=20000?"GPS aktif · target pembaruan 5 detik":"Menunggu GPS akurat dan stabil · belum ada posisi baru"):"Sesi kerja selesai");finishAttempt();if(next==0)stopSelf();return;}
                        network.execute(()->upload(batch,attempt,credential));
                    }catch(Exception e){failed(e,attempt);}
                });
            }catch(Exception e){worker.post(()->failed(e,attempt));}
        });
    }
    private void upload(JSONArray batch,long attempt,String credential) {
        try {
            JSONObject response=api("points_batch",new JSONObject().put("points",batch),credential);
            worker.post(()->{
                if(!current(attempt)){finishAttempt();return;}
                try {
                    JSONArray ack=response.optJSONArray("ack");if(ack==null)throw new IOException("Server belum mengakui GPS");
                    queue.ack(ack);
                    JSONObject rejectedPoints=response.optJSONObject("rejected");
                    java.util.HashSet<String> acknowledged=new java.util.HashSet<>();
                    for(int i=0;i<ack.length();i++)acknowledged.add(ack.optString(i));
                    long confirmedFix=0;
                    for(int i=0;i<batch.length();i++){
                        JSONObject item=batch.getJSONObject(i);String id=item.optString("id");
                        if(acknowledged.contains(id) && (rejectedPoints==null||!rejectedPoints.has(id)))confirmedFix=Math.max(confirmedFix,item.optLong("fix_epoch")*1000L);
                    }
                    if(confirmedFix>0){TrackingHealth.sentAt=System.currentTimeMillis();TrackingHealth.serverFixAt=Math.max(TrackingHealth.serverFixAt,confirmedFix);}
                    int rejected=rejectedPoints==null?0:rejectedPoints.length();
                    notice((rejected>0?rejected+" titik ditolak server · ":"GPS diproses · ")+"antrean "+queue.size());
                    finishAttempt();
                    if(finishing && queue.size()==0)stopSelf();
                    else if(queue.size()>=100)sync();
                }catch(Exception e){failed(e,attempt);}
            });
        }catch(Exception e){worker.post(()->failed(e,attempt));}
    }
    private boolean current(long attempt){return !destroyed && generation==attempt;}
    private void finishAttempt(){inFlight=false;if(uploadLock.isHeld())uploadLock.release();}
    private void failed(Exception error,long attempt) {
        finishAttempt();if(!current(attempt))return;
        if(error instanceof AuthFailure){clearSession();stopGps();TokenStore.clear(this);notice("Login berakhir · buka aplikasi dan masuk kembali");stopSelf();return;}
        notice("Belum terkirim · "+queue.size()+" titik tersimpan · periksa internet/server");
    }
    private static final class AuthFailure extends IOException {}
    private JSONObject api(String endpoint,JSONObject body,String credential)throws Exception {
        HttpURLConnection connection=(HttpURLConnection)new URL(AppConfig.BASE+"/api/mobile/"+endpoint+".php").openConnection();
        connection.setConnectTimeout(6000);connection.setReadTimeout(6000);connection.setInstanceFollowRedirects(false);
        connection.setRequestProperty("Authorization","Bearer "+credential);
        try {
            if(body!=null){connection.setRequestMethod("POST");connection.setDoOutput(true);connection.setRequestProperty("Content-Type","application/json");try(OutputStream out=connection.getOutputStream()){out.write(body.toString().getBytes(StandardCharsets.UTF_8));}}
            int code=connection.getResponseCode();if(code==401||code==403)throw new AuthFailure();if(code!=200)throw new IOException("HTTP "+code);
            try(InputStream in=connection.getInputStream();ByteArrayOutputStream out=new ByteArrayOutputStream()) {
                byte[] buffer=new byte[4096];int n;while((n=in.read(buffer))!=-1){out.write(buffer,0,n);if(out.size()>2000000)throw new IOException("Respons terlalu besar");}
                JSONObject result=new JSONObject(out.toString("UTF-8"));if(!result.optBoolean("ok"))throw new IOException("Server menolak permintaan");return result;
            }
        }finally{connection.disconnect();}
    }
    private String tokenHash(){try{return android.util.Base64.encodeToString(java.security.MessageDigest.getInstance("SHA-256").digest(token.getBytes(StandardCharsets.UTF_8)),android.util.Base64.NO_WRAP);}catch(Exception e){throw new IllegalStateException(e);}}
    private void clearSession(){sessionId=0;validUntil=0;getSharedPreferences("tracking_state",0).edit().clear().apply();}
    @Override public void onTaskRemoved(Intent root){TrackingHealth.taskRemovedAt=System.currentTimeMillis();notice("Kartu aplikasi ditutup · layanan masih berjalan saat ini");super.onTaskRemoved(root);}
    @Override public IBinder onBind(Intent intent){return null;}
    @Override public void onDestroy(){
        destroyed=true;running=false;stopGps();worker.removeCallbacksAndMessages(null);
        network.shutdownNow();if(uploadLock.isHeld())uploadLock.release();
        worker.post(()->{queue.close();thread.quitSafely();});
        stopForeground(STOP_FOREGROUND_REMOVE);super.onDestroy();
    }
}
