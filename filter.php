<?php
$pid = getmypid();

error_log("${pid} ***** FILTER MESSAGE START ***** " . $_SERVER['REQUEST_URI']);

error_log("${pid} " . $_SERVER['HTTP_USER_AGENT']);

// 最終コミット日時のハッシュを比較し一致した場合のみ許可
$md5_hash = md5_file('/app/www/last_update.txt');
error_log("${pid} last_update.txt " . $md5_hash);
error_log("${pid} X-Access-Key*** " . $_SERVER['HTTP_X_ACCESS_KEY']);
$access_key = $_SERVER['HTTP_X_ACCESS_KEY'];

// 多段接続のみ許可
error_log("${pid} X-Forwarded-For " . $_SERVER['HTTP_X_FORWARDED_FOR']);
$forward_count = count(explode(' ', $_SERVER['HTTP_X_FORWARDED_FOR']));

// IE Edge 不可
if (preg_match('/(Trident|Edge)/', $_SERVER['HTTP_USER_AGENT']) || $forward_count != 3 || $access_key != $md5_hash)
{
  error_log("${pid} #*#*#*#*# IE or Edge or Direct Connect or X-Access-Key Unmatch #*#*#*#*#");
  header('HTTP', true, 403);
  
  $message =
    'D 403 ' .
    $_SERVER['SERVER_NAME'] . ' ' .
    $_SERVER['HTTP_X_FORWARDED_FOR'] . ' ' .
    $_SERVER['REMOTE_USER'] . ' ' .
    $_SERVER['REQUEST_METHOD'] . ' ' .
    $_SERVER['REQUEST_URI'] . ' ' .
    $_SERVER['HTTP_USER_AGENT'];

  loggly_log($message);

  error_log("${pid} ${res}");
    
  error_log("${pid} ***** FILTER MESSAGE FINISH ***** " . $_SERVER['REQUEST_URI']);
  return;
}

error_log("${pid} ***** STDIN START ***** " . $_SERVER['REQUEST_URI']);
$buf = file_get_contents('php://stdin');
error_log("${pid} ***** STDIN FINISH ***** " . $_SERVER['REQUEST_URI']);

$arr_buf = preg_split('/^\r\n/m', $buf, 2);
$header = $arr_buf[0];
$body = $arr_buf[1];

// 余計なヘッダ削除
$header = preg_replace('/^X-Request.+\n/m', '', $header);
$header = preg_replace('/^ETag.+\n/m', '', $header);
$header = preg_replace('/^Expires.+\n/m', '', $header);
// 偽装
$header = preg_replace('/^Server: DeleGate.+$/m', 'Server: Apache', $header);
$header = preg_replace('/^DeleGate.+\n/m', '', $header);

if (strpos($header, 'Content-Type: text/html') !== false)
{
  // イメージファイルでは残したいけどここでは不要
  $header = preg_replace('/^Last-Modified.+\n/m', '', $header);
  
  // 元サイズ
  $header = str_replace('Content-Length:', 'X-Content-Length:', $header);
  
  $tmp = explode('/', $_SERVER['REQUEST_URI']);
  
  // レンジ指定の場合のみ自動更新追加
  if (preg_match('/^\d+$/', end($tmp)))
  {
    $body = str_replace('<TITLE>', '<HTML><HEAD><TITLE>', $body);
  }
  else
  {
    $body = str_replace('<TITLE>', '<HTML><HEAD><META HTTP-EQUIV="REFRESH" CONTENT="600"><TITLE>', $body);
  }
  $replace_text = <<< __HEREDOC__
</TITLE>
<STYLE TYPE='text/css'>
a { text-decoration: none; font-weight: 500; }
</STYLE></HEAD>
__HEREDOC__;
  //$body = str_replace('</TITLE>', '</TITLE></HEAD>', $body);
  $body = str_replace('</TITLE>', $replace_text, $body);

  // アイコンはフロント側から取得
  $body = str_replace('http://' . $_SERVER['SERVER_NAME'] . ':80/-/builtin/icons/ysato/', '/icons/', $body);

  $body = preg_replace('/<FORM ACTION="..\/-search" METHOD=GET>.+?<\/FORM>/s', '', $body);

  $body = preg_replace('/<!-- generated by DeleGate\/x.x.x -->.+/s', '</BODY></HTML>', $body);

  $body = str_replace('<A HREF="../"><IMG BORDER=0 ALIGN=MIDDLE ALT="upper" SRC="/icons/up.gif"></A>', '', $body);

  $body = preg_replace('/<FONT .+?>.+?<\/FONT>/s', '', $body, 1);

  $body = preg_replace('/<small>.+?<\/small>/s', '', $body, 3);
  
  $body = preg_replace('/<(s|\/s)mall>/s', '', $body);
  $body = str_replace('</DT>', '</DT><BR><BR></B>', $body);
  
  $body = str_replace('<FORM ACTION="" METHOD=GET>', '', $body);
  $body = str_replace('</FORM>', '', $body);
  
  // 空白削除
  $body = preg_replace('/^ *\r\n/m', '', $body);
  $body = preg_replace('/^  /m', ' ', $body);
  $body = preg_replace('/^ +</m', '<', $body);

  $body = str_replace("</TD>\r\n", "</TD>", $body);
  $body = str_replace("</TR>\r\n", "</TR>", $body);
  $body = str_replace("<TD>\r\n", "<TD>", $body);
  $body = str_replace("<TR>\r\n", "<TR>", $body);
  $body = str_replace("<CODE>.</CODE>\r\n", '', $body);
  $body = str_replace("<DD>\r\n", "<DD>", $body);
  
  error_log("${pid} " . $_SERVER['REQUEST_URI']);
  error_log($header);
  if (getenv('DELEGATE_LOG_LEVEL') != 'simple') {
    error_log("\r\n");
    error_log($body);
  }

  // 圧縮
  $buf = $header;
  $body = gzencode($body, 9);

  $buf .= "Content-Encoding: gzip\r\n";
  $buf .= "Content-Length: " . strlen($body) . "\r\n";
  $buf .= "\r\n";
  $buf .= $body;
} else {
  error_log("${pid} " . $_SERVER['REQUEST_URI']);
  error_log($header);
  $buf = $header;
  //$buf .= "Cache-Control: max-age=86400\r\n";
  $buf .= "\r\n";
  $buf .= $body;
}

echo $buf;

$message =
  'D ' .
  explode(' ', $header, 3)[1] . ' ' .
  $_SERVER['SERVER_NAME'] . ' ' .
  $_SERVER['HTTP_X_FORWARDED_FOR'] . ' ' .
  $_SERVER['REMOTE_USER'] . ' ' .
  $_SERVER['REQUEST_METHOD'] . ' ' .
  $_SERVER['REQUEST_URI'] . ' ' .
  $_SERVER['HTTP_USER_AGENT'];

loggly_log($message);

error_log("${pid} ${res}");

error_log("${pid} ***** FILTER MESSAGE FINISH ***** " . $_SERVER['REQUEST_URI']);

function loggly_log($message_) {
  $url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . '/tag/' . $_SERVER['SERVER_NAME'] . ',filter.php/';
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  curl_setopt($ch, CURLOPT_ENCODING, '');
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $message_);
  curl_exec($ch);
  curl_close($ch);
  
  $count = ($count + 1) % 10;
}
?>
