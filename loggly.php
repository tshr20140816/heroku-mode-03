<?php

$type = $argv[1]; // 'A' or 'E'
$prefix = $argv[2];

$stdin = fopen('php://stdin', 'r');
ob_implicit_flush(true);

$ch = null;
$count = 0;

while ($line = fgets($stdin)) {
  if ($type == 'E') {
    $line = getenv('HEROKU_APP_NAME') . " ${line}";
  }  
  loggly_log("${prefix} ${line}");
}

exit();

function loggly_log($message_) {
  $url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . '/tag/' . getenv('HEROKU_APP_NAME') . '/';
  
  global $ch, $count;
  if ($count % 10 == 0) {
    $ch = curl_init();
  }
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  curl_setopt($ch, CURLOPT_ENCODING, '');
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain', 'Connection: Keep-Alive']);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $message_);
  curl_exec($ch);
  if ($count % 10 == 9) {
    curl_close($ch);
  }
  $count = ($count + 1) % 10;
}
?>
