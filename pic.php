<?php
// Начало сессии
session_start();

// Удаляем старые значения сессии для принудительного обновления
unset($_SESSION['regcode']);
unset($_SESSION['formula']);

// Генерация новой формулы и ответа
$fid = mt_rand(1, 20);
switch ($fid) {
    case 1: $formula = "2+2=?"; $text = "4"; break;
    case 2: $formula = "3+3=?"; $text = "6"; break;
    case 3: $formula = "3+2=?"; $text = "5"; break;
    case 4: $formula = "1+2=?"; $text = "3"; break;
    case 5: $formula = "5+5=?"; $text = "10"; break;
    case 6: $formula = "8-3=?"; $text = "5"; break;
    case 7: $formula = "1+4=?"; $text = "5"; break;
    case 8: $formula = "9-1=?"; $text = "8"; break;
    case 9: $formula = "4-1=?"; $text = "3"; break;
    case 10: $formula = "3-2=?"; $text = "1"; break;
    case 11: $formula = "7+7=?"; $text = "14"; break;
    case 12: $formula = "7+1=?"; $text = "8"; break;
    case 13: $formula = "6+1=?"; $text = "7"; break;
    case 14: $formula = "5-2=?"; $text = "3"; break;
    case 15: $formula = "3*3=?"; $text = "9"; break;
    case 16: $formula = "5*5=?"; $text = "25"; break;
    case 17: $formula = "4*4=?"; $text = "16"; break;
    case 18: $formula = "8-4=?"; $text = "4"; break;
    case 19: $formula = "5+2=?"; $text = "7"; break;
    case 20: $formula = "1+7=?"; $text = "8"; break;
}
$_SESSION['regcode'] = $text;
$_SESSION['formula'] = $formula;

// Путь к шрифту
$font = __DIR__ . "/font/TESLDOC.TTF";

// Проверяем, существует ли шрифт
if (!file_exists($font)) {
    // Если шрифт отсутствует, используем встроенный шрифт
    $use_ttf = false;
} else {
    $use_ttf = true;
}

// Проверяем, доступно ли расширение GD
if (!extension_loaded('gd') || !function_exists('imagecreate')) {
    die("Ошибка: Расширение GD не установлено или не активировано. Пожалуйста, включите GD в php.ini и перезапустите сервер.");
}
function getIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
// Создаем изображение
$img = imagecreate(120, 40);

// Задаем цвета
$background = imagecolorallocate($img, 255, 255, 255); // Белый фон
$text_color = imagecolorallocate($img, 0, 0, 0);       // Черный текст

// Заполняем фон
imagefill($img, 0, 0, $background);

// Выводим текст формулы на изображение
if ($use_ttf) {
    imagettftext($img, 16, 0, 10, 30, $text_color, $font, $_SESSION['formula']);
} else {
    imagestring($img, 5, 10, 10, $_SESSION['formula'], $text_color);
}

// Отключаем кэширование изображения
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Устанавливаем заголовок для PNG-изображения
header("Content-Type: image/png");

// Выводим изображение в формате PNG
imagepng($img);

// Освобождаем память
imagedestroy($img);
?>