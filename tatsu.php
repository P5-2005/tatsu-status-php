<?php

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");


//get url desired ios/bdid/cpid from other api like ipsw
// iphone8 : 2/A, 8+ : 4/C, X: 6/E

//8+ : https://updates.cdn-apple.com/2023WinterFCS/fullrestores/032-36565/C5083F46-63AC-4853-A14E-F918E123EFD3/iPhone_5.5_P3_16.3_20D47_Restore.ipsw
// 15.6 rc wYEWeBuzHXReRo99Oe7dYymYzbU= // S+vby/DTeOTvw4hyfLX109v94lc= https://updates.cdn-apple.com/2022SummerFCS/fullrestores/012-40444/082D5132-5C81-4EA0-8253-16D603447C05/iPhone_5.5_P3_15.6_19G69_Restore.ipsw
// 8 : https://updates.cdn-apple.com/2023WinterFCS/fullrestores/032-36262/2C10DC57-1287-4AC5-888F-D4A3D3FE21F0/iPhone_4.7_P3_16.3_20D47_Restore.ipsw
// 15.6 rc msEhkK0DF0QJPVq9hTD+W0oEUNw= // VcgFkGLjZPqxoQLD28ydWFzv3QY=  https://updates.cdn-apple.com/2022SummerFCS/fullrestores/012-40419/2E28BDBA-78AB-4431-8128-6FBA80997091/iPhone_4.7_P3_15.6_19G69_Restore.ipsw

// X : https://updates.cdn-apple.com/2023WinterFCS/fullrestores/032-36563/F19214DA-F2A2-4204-86B9-EA0B1CF71C66/iPhone10,3,iPhone10,6_16.3_20D47_Restore.ipsw
// 15.6 rc https://updates.cdn-apple.com/2022SummerFCS/fullrestores/012-40468/6AD38679-189F-400F-A10D-0FF83492CBB7/iPhone10,3,iPhone10,6_15.6_19G69_Restore.ipsw


$url = "https://updates.cdn-apple.com/2022SummerFCS/fullrestores/012-40468/6AD38679-189F-400F-A10D-0FF83492CBB7/iPhone10,3,iPhone10,6_15.6_19G69_Restore.ipsw";

$lastSlashPos = strrpos($url, "/");
$baseUrl = substr($url, 0, $lastSlashPos);
$baseUrl .= "/BuildManifest.plist";
$contents = file_get_contents($baseUrl);
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
	if (hexdec($bdid0) === 0xE) {
	  $bdid=hexdec($bdid0);
      $ubidStartPos = strpos($value, '<key>UniqueBuildID</key>', $bdidEndPos);
      $ubidDataStartPos = strpos($value, '<data>', $ubidStartPos);
      $ubidDataEndPos = strpos($value, '</data>', $ubidDataStartPos);
      $ubid = substr($value, $ubidDataStartPos + 6, $ubidDataEndPos - $ubidDataStartPos - 6);
      echo $ubid."<br>";
      break;
    }
    $bdidPos = strpos($value, '<key>ApBoardID</key>', $bdidEndPos);
  }
}

$chipid='32789';

// start check
$url = "http://gs.apple.com/TSS/controller?action=2";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
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
	<key>ApProductionMode</key>
	<true/>
	<key>ApSecurityDomain</key>
	<integer>1</integer>
	<key>ApSecurityMode</key>
	<true/>
	<key>ApNonce</key>
    <data>q83vASNFZ4mrze8BI0VniavN7wE=</data>
    <key>SepNonce</key>
    <data>z59YgWI9Pv3oNas53hhBJXc4S0E=</data>
<key>UniqueBuildID</key>
<data>$ubid</data>
</dict>
</plist>

DATA;

curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); uncomment this if you use https instead of http
//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); uncomment this if you use https instead of http

$resp = curl_exec($curl);
curl_close($curl);

if (strpos($resp, "STATUS=460") !== false) {
    echo "Signed";
} elseif (strpos($resp, "STATUS=94") !== false){
    echo "Unsigned";
} elseif (strpos($resp, "STATUS=98") !== false){
    echo "Error on request";
}
else {
    echo "backend issue";
}

?>
