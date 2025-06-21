<?php
$link = mysqli_connect('localhost', 'root', 'root', 'munchkin');
if (!$link) {
    die('Ошибка подключения: ' . mysqli_connect_error());
}
echo 'Успешное подключение к базе!';
mysqli_close($link);
?>
