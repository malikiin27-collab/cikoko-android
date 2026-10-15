package id.cikoko.pegawai;

/** Pure timing/quality rules, shared with local regression tests. */
final class TrackingPolicy {
    static final long INTERVAL_MS = 5000;
    static final long MAX_FIX_AGE_MS = 15000;
    static final long OFFLINE_LEASE_MS = 12 * 60 * 60 * 1000L;
    static boolean validFix(double lat, double lng, float accuracy, long ageMs, boolean mock) {
        return !mock && Double.isFinite(lat) && Double.isFinite(lng)
            && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180
            && Float.isFinite(accuracy) && accuracy > 0 && accuracy <= 30
            && ageMs >= 0 && ageMs <= MAX_FIX_AGE_MS;
    }
    static boolean confirmed(long ageMs, float distance, float accuracy, float pendingAccuracy) {
        return confirmed(ageMs,distance,accuracy,pendingAccuracy,0);
    }
    static boolean confirmed(long ageMs, float distance, float accuracy, float pendingAccuracy, float speed) {
        return ageMs>=1000 && ageMs<=15000 && Float.isFinite(distance)
            && Float.isFinite(speed) && speed>=0 && speed<=100
            && distance<=Math.max(10, accuracy+pendingAccuracy)+speed*ageMs/1000f;
    }
    static boolean due(long fixElapsedMs, long lastElapsedMs) {
        return lastElapsedMs == 0 || fixElapsedMs - lastElapsedMs >= INTERVAL_MS;
    }
}
