package id.cikoko.pegawai;
final class AppConfig {
 static final String ORIGIN="https://cikoko.datawebs.my.id";
 static final String BASE=ORIGIN;
 static boolean trusted(android.net.Uri u){return u!=null && "https".equals(u.getScheme()) && "cikoko.datawebs.my.id".equals(u.getHost()) && (u.getPort()==-1 || u.getPort()==443);}
}
