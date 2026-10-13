package id.cikoko.pegawai;
final class AppConfig {
 static final String ORIGIN="https://datawebs.my.id";
 static final String BASE=ORIGIN+"/cikoko";
 static boolean trusted(android.net.Uri u){return "https".equals(u.getScheme()) && "datawebs.my.id".equals(u.getHost()) && (u.getPort()==-1 || u.getPort()==443) && ("/cikoko".equals(u.getPath()) || (u.getPath()!=null && u.getPath().startsWith("/cikoko/")));}
}
