<?php

$pid = getmypid();

error_log($pid . ' ***** FILTER MESSAGE START ***** ' . $_SERVER['REQUEST_URI']);

error_log($pid . ' ' . $_SERVER['HTTP_USER_AGENT']);

/*
$url = 'http://logs-01.loggly.com/inputs/TOKEN/' . getenv('LOGGLY_TOKEN') . '/http/';

error_log($pid . ' ' . $url);
          
$context = array(
  "http" => array(
    "method" => "POST",
    "header" => "content-type:text/plain",
    "content" => $_SERVER['HTTP_USER_AGENT']
    )
  );

$res = file_get_contents($url, false, stream_context_create($context));

error_log($pid . ' ' . $res);
*/

if (preg_match('/(Trident|Edge)/', $_SERVER['HTTP_USER_AGENT']))
{
  throw new Exception("' #*#*#*#*# IE or Edge #*#*#*#*# '");
  return;
}

error_log($pid . ' ***** STDIN START ***** ' . $_SERVER['REQUEST_URI']);
$buf = file_get_contents('php://stdin');
error_log($pid . ' ***** STDIN FINISH ***** ' . $_SERVER['REQUEST_URI']);

$arr_buf = preg_split('/^\r\n/m', $buf, 2);
$header = $arr_buf[0];
$body = $arr_buf[1];

$header = preg_replace('/^X-Request.+\n/m', '', $header);
$header = preg_replace('/^ETag.+\n/m', '', $header);
$header = preg_replace('/^Expires.+\n/m', '', $header);
// 偽装
$header = preg_replace('/^Server: DeleGate.+$/m', 'Server: Apache', $header);
$header = preg_replace('/^DeleGate.+\n/m', '', $header);

if (strpos($header, 'Content-Type: text/html') !== false)
{
  $header = preg_replace('/^Last-Modified.+\n/m', '', $header);
  
  // 元サイズ
  $header = str_replace('Content-Length:', 'X-Content-Length:', $header);
  
  $tmp = explode('/', $_SERVER['REQUEST_URI']);
  
  if (preg_match('/^\d+$/', end($tmp)))
  {
    $body = str_replace('<TITLE>', '<HTML><HEAD><TITLE>', $body);
  }
  else
  {
    // 自動更新追加
    $body = str_replace('<TITLE>', '<HTML><HEAD><META HTTP-EQUIV="REFRESH" CONTENT="600"><TITLE>', $body);
  }
  $body = str_replace('</TITLE>', '</TITLE></HEAD>', $body);

  // アイコンはフロント側から取得
  $body = str_replace('http://' . $_SERVER['SERVER_NAME'] . ':80/-/builtin/icons/ysato/', '/icons/', $body);
  //$body = str_replace('/-/builtin/icons/ysato/', '/icons/', $body);

  $body = preg_replace('/<FORM ACTION="..\/-search" METHOD=GET>.+?<\/FORM>/s', '', $body);
  //$body = preg_replace('/<FORM ACTION="\/mail\/-search" METHOD=GET>.+?<\/FORM>/s', '', $body);

  $body = preg_replace('/<!-- generated by DeleGate\/x.x.x -->.+/s', '</BODY></HTML>', $body);

  $body = str_replace('<A HREF="../"><IMG BORDER=0 ALIGN=MIDDLE ALT="upper" SRC="/icons/up.gif"></A>', '', $body);
  //$body = str_replace('<A HREF="/mail/"><IMG BORDER=0 ALIGN=MIDDLE ALT="upper" SRC="/icons/up.gif"></A>', '', $body);

  $body = preg_replace('/<FONT .+?>.+?<\/FONT>/s', '', $body, 1);

  $body = preg_replace('/<small>.+?<\/small>/s', '', $body, 3);
  
  // 空白削除
  $body = preg_replace('/^ *\r\n/m', '', $body);
  $body = preg_replace('/^  /m', ' ', $body);
  $body = preg_replace('/^ +</m', '<', $body);

  error_log($_SERVER['REQUEST_URI']);
  error_log($header);
  error_log("\r\n");
  error_log($body);

  $buf = $header;
  $body = gzencode($body, 9);

  $buf .= "Content-Encoding: gzip\r\n";
  $buf .= "Content-Length: " . strlen($body) . "\r\n";
  $buf .= "\r\n";
  $buf .= $body;
} else {
  error_log($_SERVER['REQUEST_URI']);
  error_log($header);
  $buf = $header;
  //$buf .= "Cache-Control: max-age=86400\r\n";
  $buf .= "\r\n";
  $buf .= $body;
}

echo $buf;

error_log($pid . ' ***** FILTER MESSAGE FINISH ***** ' . $_SERVER['REQUEST_URI']);
?>
