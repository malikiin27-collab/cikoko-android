package id.cikoko.pegawai;
public final class TrackingPolicyTest {
 static void check(boolean value,String label){if(!value)throw new AssertionError(label);}
 public static void main(String[] args){
  check(TrackingPolicy.due(10000,5000),"stationary fix allowed at five seconds");
  check(!TrackingPolicy.due(9999,5000),"early fix suppressed");
  check(!TrackingPolicy.due(4000,5000),"out-of-order elapsed time rejected");
  check(TrackingPolicy.validFix(-6.25,106.85,8,1000,false),"valid recent GPS");
  check(!TrackingPolicy.validFix(-6.25,106.85,8,15001,false),"stale cached GPS rejected");
  check(!TrackingPolicy.validFix(-6.25,106.85,8,-1,false),"future elapsed time rejected");
  check(!TrackingPolicy.validFix(-6.25,106.85,8,1000,true),"reported mock rejected");
  check(!TrackingPolicy.validFix(Double.NaN,106.85,8,1000,false),"NaN rejected");
  check(!TrackingPolicy.validFix(-6.25,181,8,1000,false),"invalid longitude rejected");
  check(!TrackingPolicy.validFix(-6.25,106.85,101,1000,false),"poor accuracy rejected");
  check(!TrackingPolicy.validFix(-6.25,106.85,Float.NaN,1000,false),"invalid accuracy rejected");
  check(!TrackingPolicy.validFix(-6.25,106.85,31,1000,false),"31m uncertainty rejected");
  check(!TrackingPolicy.validFix(-6.25,106.85,0,1000,false),"zero uncertainty rejected");
  check(TrackingPolicy.validFix(-6.25,106.85,30,1000,false),"30m boundary accepted");
  check(!TrackingPolicy.confirmed(0,0,5,5),"duplicate fix cannot confirm position");
  check(!TrackingPolicy.confirmed(16000,0,5,5),"stale candidate cannot confirm position");
  check(!TrackingPolicy.confirmed(5000,50,5,5),"jump cannot confirm position");
  check(TrackingPolicy.confirmed(5000,6,5,5),"consistent second fix confirms position");
  check(TrackingPolicy.confirmed(5000,50,5,5,10),"startup while driving can acquire a stable fix");
  check(!TrackingPolicy.confirmed(5000,300,5,5,10),"moving speed cannot justify impossible relocation");
  System.out.println("PASS: 20 GPS interval/quality/stability regression cases");
 }
}
