<?php
error_reporting(0); //comment this if you are on dev env

header("Cache-Control: no-cache, must-revalidate"); // this to reset cache
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

/*
get url desired ios/bdid/cpid from ipsw or theiphonewiki or appledb
bdid hex : gsm/global are different; iphone8 : 2/A; 8+ : 4/C; X: 6/E

8+ : 15.6 rc https://updates.cdn-apple.com/2022SummerFCS/fullrestores/012-40444/082D5132-5C81-4EA0-8253-16D603447C05/iPhone_5.5_P3_15.6_19G69_Restore.ipsw
8 : 15.6 rc https://updates.cdn-apple.com/2022SummerFCS/fullrestores/012-40419/2E28BDBA-78AB-4431-8128-6FBA80997091/iPhone_4.7_P3_15.6_19G69_Restore.ipsw
X : 15.6 rc https://updates.cdn-apple.com/2022SummerFCS/fullrestores/012-40468/6AD38679-189F-400F-A10D-0FF83492CBB7/iPhone10,3,iPhone10,6_15.6_19G69_Restore.ipsw
*/

$chipid='32789'; //A11
$bid=0xE; // iphone X, if you wanna try other dont forget to change this too

$url = "https://updates.cdn-apple.com/2022SummerFCS/fullrestores/012-40468/6AD38679-189F-400F-A10D-0FF83492CBB7/iPhone10,3,iPhone10,6_15.6_19G69_Restore.ipsw";

$lastSlashPos = strrpos($url, "/");
$baseUrl = substr($url, 0, $lastSlashPos);
$baseUrl .= "/BuildManifest.plist";

$contents = file_get_contents($baseUrl);
if ($contents === false) {
    echo "Unable to retrieve the contents of the BuildManifest file, check ipsw link maybe broken";
    die;
} else {
$startPos = strpos($contents, '<dict>');
$endPos = strrpos($contents, '</dict>');
$value = substr($contents, $startPos, $endPos - $startPos + 7);
$buildIdentitiesPos = strpos($value, '<key>BuildIdentities</key>');
if ($buildIdentitiesPos !== false) {
  $bdidPos = strpos($value, '<key>ApBoardID</key>', $buildIdentitiesPos);
  while ($bdidPos !== false) {
    $bdidStartPos = strpos($value, '<string>', $bdidPos);
    $bdidEndPos = strpos($value, '</string>', $bdidStartPos);
    $bdid0 = substr($value, $bdidStartPos + 8, $bdidEndPos - $bdidStartPos - 8);
    
	if (hexdec($bdid0) === $bid) {
	  $bdid=hexdec($bdid0);
      $ubidStartPos = strpos($value, '<key>UniqueBuildID</key>', $bdidEndPos);
      $ubidDataStartPos = strpos($value, '<data>', $ubidStartPos);
      $ubidDataEndPos = strpos($value, '</data>', $ubidDataStartPos);
      $ubid = substr($value, $ubidDataStartPos + 6, $ubidDataEndPos - $ubidDataStartPos - 6);
      break;
    }
    $bdidPos = strpos($value, '<key>ApBoardID</key>', $bdidEndPos);
  }
}

$url = "http://gs.apple.com/TSS/controller?action=2";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$headers = array(
   "User-Agent: InetURL/P5_2005",
   "Host: gs.apple.com",
   "Content-Type: application/xml",
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$data = <<<DATA
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
<key>ApBoardID</key>
<integer>$bdid</integer>
<key>ApChipID</key>
<integer>$chipid</integer>
<key>ApECID</key>
<integer>1</integer>
<key>UniqueBuildID</key>
<data>$ubid</data>
</dict>
</plist>

DATA;

curl_setopt($curl, CURLOPT_POSTFIELDS, $data);


$resp = curl_exec($curl);
curl_close($curl);

if (strpos($resp, "STATUS=460") !== false) {
   echo "15.6 RC Status :<br /> <b style='color:green'>Signed! :)</b>";
} elseif (strpos($resp, "STATUS=94") !== false){
    echo "15.6 RC Status :<br /> <b style='color:red'>Unsigned! :(</b>";
} elseif (strpos($resp, "STATUS=98") !== false){
    echo "Error on request, maybe change";
}
else {
    echo "backend issue";
}
    die;
}

?>

