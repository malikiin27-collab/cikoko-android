package id.cikoko.pegawai;

/** Pure timing/quality rules, shared with local regression tests. */
final class TrackingPolicy {
    static final long INTERVAL_MS = 5000;
    static final long MAX_FIX_AGE_MS = 15000;
    static final long OFFLINE_LEASE_MS = 12 * 60 * 60 * 1000L;
    static boolean validFix(double lat, double lng, float accuracy, long ageMs, boolean mock) {
        return !mock && Double.isFinite(lat) && Double.isFinite(lng)
            && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180
            && Float.isFinite(accuracy) && accuracy >= 0 && accuracy <= 100
            && ageMs >= 0 && ageMs <= MAX_FIX_AGE_MS;
    }
    static boolean due(long fixElapsedMs, long lastElapsedMs) {
        return lastElapsedMs == 0 || fixElapsedMs - lastElapsedMs >= INTERVAL_MS;
    }
}
