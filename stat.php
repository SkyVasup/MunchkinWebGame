<?php
// Проверяем, был ли уже создан объект для работы с БД. Если нет - создаем.
if (!isset($mysql) || !is_a($mysql, 'MYSQL')) {
    require_once(dirname(__FILE__) . '/modules/mysql.php');
    $mysql = new MYSQL();
}

$now = time();
$ip = mysqli_real_escape_string($mysql->connection, $_SERVER["REMOTE_ADDR"]);
$req = mysqli_real_escape_string($mysql->connection, $_SERVER["REQUEST_URI"]);
$ref = mysqli_real_escape_string($mysql->connection, $_SERVER["HTTP_REFERER"] ?? '');
$browser = mysqli_real_escape_string($mysql->connection, $_SERVER["HTTP_USER_AGENT"] ?? '');

$mysql->sql_query("
    INSERT INTO statistics (time, ip, req, ref, browser, temp)
    VALUES ('$now', '$ip', '$req', '$ref', '$browser', '0')
");
?>
