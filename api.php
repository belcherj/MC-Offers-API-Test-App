<?php
header ("Cache-Control: no-cache");
$sandbox = false;
 //-----------------REPLACE WITH YOUR CLIENT KEYS----------------------------
$okey=$sandbox?"":"";
//------------------REPLACE THIS KEY WITH YOUR OWN--------------------------
$priv_key=$sandbox?
"-----BEGIN PRIVATE KEY----- 
-----END PRIVATE KEY-----":
"-----BEGIN PRIVATE KEY-----
-----END PRIVATE KEY-----";

//I put my keys in serpeate file.
include_once 'keys.php';

//-----------------------GET PARAMETERS---------------------------------------
$callType = "offer";
$postalCode = ($_GET["PostalCode"]!="")?$_GET["PostalCode"]:"19107";
$lat = ($_GET["lat"]!="")?$_GET["lat"]:"39.952473";
$lng = ($_GET["lng"]!="")?$_GET["lng"]:"-75.164106";
$count = ($_GET["count"]!="")?$_GET["count"]:"200";

//----------------------NONCE/TIMESTAMP CREATION------------------------------
$mt = microtime();
$rand = mt_rand();
$nonce = md5($mt.$rand);
$t=time();
//---------------------BASE STRING CREATION-----------------------------------
$base=$sandbox?"GET&https%3A%2F%2Fsandbox.":"GET&https%3A%2F%2F";
$base.="api.mastercard.com%2Foffers%2Fv2%2F" . $callType; 

$req_params="";
if ($callType == 'offer') {
    $base.="&";
    $req_params.="&PostalCode=" . $postalCode;
    $req_params.="&Latitude=" . $lat;
    $req_params.="&Longitude=" . $lng;
    $req_params.="&PageLength=" . $count;
    //$req_params.="&PostalCode=63368";
    //$req_params.="&Latitude=39.939225";
    //$req_params.="&Longitude=75.180352";
    //$req_params.="&Category=Food%20%26%20Drink";
    $req_params.="&PageOffset=0";
    //$req_params.="&PageLength=200";
    $req_params.="&Format=XML";
    $req_params=substr($req_params,1);
}


$params=$req_params."&oauth_nonce=".$nonce;
$params.="&oauth_consumer_key=".$okey;
$params.="&oauth_signature_method=RSA-SHA1";
$params.="&oauth_timestamp=".$t;
$params.="&oauth_version=1.0";  

//-------------------LEXICOGRAPHICAL BYTE VALUE ORDERING------------------

$params=ordByteSort($params, "&");
//echo $params;
//------------------------BASE STRING READY-------------------------------
$data=$base.amp($params);
//echo $data;
//------------------------SIGN BASE STRING--------------------------------
openssl_sign($data, $signature, $priv_key);
//---------------------FORMAT BASE STRING---------------------------------
$signature=urlencodeRFC3986(base64_encode($signature));
if(mb_detect_encoding($signature, "UTF-8", true) != "UTF-8" ){$signature = utf8_encode($signature);}
$signature=str_replace("+","%20",$signature);
$signature=str_replace("*","%2A",$signature);
$signature=str_replace("%7E","~",$signature);

//----------------------PREPARE HEADER-------------------------------------
$hdr="OAuth oauth_consumer_key=\"".$okey."\",oauth_nonce=\"".$nonce."\",oauth_timestamp=\"".$t."\",oauth_version=\"1.0\",oauth_signature_method=\"RSA-SHA1\",oauth_signature=\"".$signature."\"";
$header[] = 'Authorization: '.$hdr;

//----------------------SEND REQUEST----------------------------------------
$url=$sandbox?'https://sandbox.api.mastercard.com/offers/v2/'. $callType:'https://api.mastercard.com/offers/v2/'. $callType;

$ch = curl_init();
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
$call = $url.'?'.$req_params;
curl_setopt($ch, CURLOPT_URL, $call);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$begintime = $time;

$result = curl_exec($ch);

$time = microtime();
$time = explode(" ", $time);
$time = $time[1] + $time[0];
$endtime = $time;
$totaltime = ($endtime - $begintime);

print_r ($header);

echo 'API call took ' .$totaltime. ' seconds.';

$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($result, 0, $header_size);

echo $call;

curl_close($ch);
header ("Content-Type:text/xml"); 

echo $result;

function amp($str){
	$str=str_replace('=','%3D',$str);
	return str_replace("&","%26",$str);	
}

function urlencodeRFC3986($string){return str_replace('%7E', '~', rawurlencode($string));}

//OAuth Lexicographical (Ordinal) Byte Value Ordering.
function ordByteSort($params, $delimiter="&"){
 $params = explode($delimiter, $params);
 $array = array();
 foreach($params as $index => $param){
   $keyval = explode("=", $param);
  array_push($array, array( 'key' => rawurlencode(trim($keyval[0])),'val' => rawurlencode(trim($keyval[1]) )));
 }
 $ordBytes = array();
 foreach($array as $param) {
  $bytes_str ="";
  for($i=0;$i<strlen($param['key'].$param['val']);$i++){$chars[$i]=substr($param['key'].$param['val'],$i,1);}
  foreach($chars as $chr) {$bytes_str .= dechex(ord($chr));}
  array_push($ordBytes, $bytes_str);
 }
 asort($ordBytes ,SORT_STRING);
 $retval = "";
 $len = count($array)-1;
 foreach($ordBytes as $index=>$value){
  $retval .= $array[$index]['key']."=".$array[$index]['val'];
  if($len--) $retval .= "&";
 } 
 return $retval;
}

?>