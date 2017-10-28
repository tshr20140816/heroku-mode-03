<?php

error_log('***** TEST MESSAGE START *****');

$fp = fopen("php://stdin","r");
$buf = "";
if ($fp > 0) {
  while(!feof($fp)) $buf .= fread($fp,4092);
  fclose($fp);
}

if (strpos($buf, 'Content-Type: text/html;') !== false)
{
  $b1 = explode("\n", $buf, 1);
  error_log('***** ' . strlen($b1[0]) . ' *****');
  error_log($b1[0]);
  error_log('***** ----- *****');
  
  //$buf = preg_replace('/^X-Request.+\n/m', '', $buf);
  $buf = str_replace('Content-Length:', 'X-Content-Length:', $buf);
  
  $buf = str_replace('<TITLE>', '<HTML><HEAD><META HTTP-EQUIV="REFRESH" CONTENT="600"><TITLE>', $buf);
  $buf = str_replace('</TITLE>', '</TITLE></HEAD>', $buf);

  $buf = str_replace('http://' . $_SERVER['SERVER_NAME'] . ':80/-/builtin/icons/ysato/', '/icons/', $buf);

  $buf = preg_replace('/<FORM ACTION="..\/-search" METHOD=GET>.+?<\/FORM>/s', '', $buf);
  
  $buf = preg_replace('/<!-- generated by DeleGate\/x.x.x -->.+/s', '</BODY></HTML>', $buf);
  
  $buf = str_replace('<A HREF="../"><IMG BORDER=0 ALIGN=MIDDLE ALT="upper" SRC="/icons/up.gif"></A>', '', $buf);
  
  //$buf = str_replace('X-Content-Length:', "Content-Encoding: gzip\nX-Content-Length:", $buf);
  
  error_log($buf);
  
  //$buf = gzencode($buf);
}

echo $buf;

error_log('***** TEST MESSAGE FINISH *****');
?>
