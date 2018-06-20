<?php

// file_name : /ttrss/feed-icons/nnn.ico

$icon_file_name = pathinfo($_GET['file_name'], PATHINFO_BASENAME);

$log_prefix = getmypid() . ' ' . $icon_file_name . ' ';

error_log($log_prefix);

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

$sql = <<< __HEREDOC__
SELECT file_data
  FROM t_icon_file
 WHERE file_name = :b_file_name
__HEREDOC__;

$statement = $pdo->prepare($sql);

$statement->execute(
  [':b_file_name' => $icon_file_name,
  ]);
$result = $statement->fetch();

if ($result === FALSE) {
  loggly_log($log_prefix . 'File Not Found');
  error_log($log_prefix . getenv('REMOTE_PATH_2'));
  error_log($log_prefix . getenv('REMOTE_PATH_2') . 'feed-icons/' . $icon_file_name);
  $http_response_header = null;
  $http_response_header[] = '';
  $tmp = explode(':', getenv('REMOTE_PATH_2'));
  $x_key = $tmp[0];
  error_log($log_prefix . $x_key);
  $context = [
    'http' => [
      'ignore_errors' => true,
      'method' => 'GET',
      'protocol_version' => '1.1',
      'header' => [
        'User-Agent: Love Love Show',
        'X-Key: ' . $x_key,
        'X-Request-Server: ' . getenv('HEROKU_APP_NAME'),
        ]],
    'ssl' => [
      'verify_peer' => false,
      'verify_peer_name' => false,
      ]];
  $result = file_get_contents('https://' . getenv('REMOTE_PATH_2') . 'feed-icons/' . $icon_file_name, false, stream_context_create($context));
  error_log($log_prefix . $http_response_header[0]);
  if (strpos($http_response_header[0], '200') !== FALSE) {
    header('Content-Type: image/vnd.microsoft.icon');
    echo $result;
    $statement = $pdo->prepare('INSERT INTO t_icon_file (file_name, file_data) VALUES (:b_file_name, :b_file_data)');
    $statement->execute(
      [':b_file_name' => $icon_file_name,
       ':b_file_data' => base64_encode($result),
      ]);
  } else {
    http_response_code(503);
  }
} else {
  error_log($log_prefix . 'File Found');
  header('Content-Type: image/vnd.microsoft.icon');
  echo base64_decode($result['file_data']);
}

$pdo = null;

exit();

function loggly_log($message_) {
  error_log("${message_}");
  
  $url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . '/tag/get_icon,' . getenv('HEROKU_APP_NAME') . '/';
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
}
?>
