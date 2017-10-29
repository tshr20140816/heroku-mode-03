<?php

error_log('***** TEST MESSAGE START ***** ' . $_SERVER['REQUEST_URI']);

$fp = fopen("php://stdin","r");
$buf = "";
if ($fp > 0) {
  while(!feof($fp)) $buf .= fread($fp,4092);
  fclose($fp);
}

$arr_buf = preg_split('/^\r\n/m', $buf, 2);
$header = $arr_buf[0];
$body = $arr_buf[1];

$header = preg_replace('/^X-Request.+\n/m', '', $header);
$header = preg_replace('/^ETag.+\n/m', '', $header);
$header = preg_replace('/^DeleGate.+\n/m', '', $header);
$header = preg_replace('/^Expires.+\n/m', '', $header);
$header = preg_replace('/^Server: DeleGate.+$/m', 'Server: Apache', $header);

if (strpos($header, 'Content-Type: text/html;') !== false || strpos($header, 'Content-Type: text/html') !== false)
{
  $header = preg_replace('/^Last-Modified.+\n/m', '', $header);
  
  $header = str_replace('Content-Length:', 'X-Content-Length:', $header);
  
  $body = str_replace('<TITLE>', '<HTML><HEAD><META HTTP-EQUIV="REFRESH" CONTENT="600"><TITLE>', $body);
  $body = str_replace('</TITLE>', '</TITLE></HEAD>', $body);

  $body = str_replace('http://' . $_SERVER['SERVER_NAME'] . ':80/-/builtin/icons/ysato/', '/icons/', $body);
  //$body = str_replace('/-/builtin/icons/ysato/', '/icons/', $body);

  $body = preg_replace('/<FORM ACTION="..\/-search" METHOD=GET>.+?<\/FORM>/s', '', $body);
  //$body = preg_replace('/<FORM ACTION="\/mail\/-search" METHOD=GET>.+?<\/FORM>/s', '', $body);

  $body = preg_replace('/<!-- generated by DeleGate\/x.x.x -->.+/s', '</BODY></HTML>', $body);

  $body = str_replace('<A HREF="../"><IMG BORDER=0 ALIGN=MIDDLE ALT="upper" SRC="/icons/up.gif"></A>', '', $body);
  //$body = str_replace('<A HREF="/mail/"><IMG BORDER=0 ALIGN=MIDDLE ALT="upper" SRC="/icons/up.gif"></A>', '', $body);

  //$body = str_replace(getenv('MAIL_ACCOUNT'), '', $body);
  $body = preg_replace('/<xxxxxFONT .+?>.+?<\/FONT>/s', '', $body, 1);

  $body = preg_replace('/<small>.+?<\/small>/s', '', $body, 3);
  
  $body = preg_replace('/^ *\r\n/m', '', $body);
  $body = preg_replace('/^  /m', ' ', $body);
  $body = preg_replace('/^ +</m', '<', $body);

  error_log($_SERVER['REQUEST_URI']);
  error_log($header);
  error_log("\r\n");
  error_log($body);

  $buf = $header;
  $body = gzencode($body);

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

error_log('***** TEST MESSAGE FINISH ***** ' . $_SERVER['REQUEST_URI']);
?>
