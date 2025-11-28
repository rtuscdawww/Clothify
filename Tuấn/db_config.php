<?php
$cn = new mysqli("localhost", "root", "", "myweb");
if ($cn->connect_error) {
    die("Kết nối thất bại: " . $cn->connect_error);
}
$cn->set_charset("utf8");
?>
