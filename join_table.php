<?php
//session_start();
require_once("global.php");
//require_once("modules/mysql.php");

//$mysql = new MySQL();

$id_user = $_SESSION['id_user'] ?? null;
$login = $_SESSION['login'] ?? null;
$id_gt = $_GET['id'] ?? null;

// 1. Проверяем, все ли данные у нас есть
if (!$id_user || !$id_gt || !is_numeric($id_gt)) {
    header("Location: gamemenu.php?error=invalid_data");
    exit;
}

// 2. Получаем информацию о столе
$table_q = $mysql->sql_query("SELECT * FROM game_tables WHERE id_gt = $id_gt");
if (!$table_q || mysqli_num_rows($table_q) == 0) {
    header("Location: gamemenu.php?error=table_not_found");
    exit;
}
$table = mysqli_fetch_assoc($table_q);

// 3. Проверяем, не полон ли стол
if ($table['num_user'] >= $table['limit_user']) {
    header("Location: gamemenu.php?error=table_full");
    exit;
}

// 4. Проверяем, не сидит ли игрок уже за каким-то столом
$user_q = $mysql->sql_query("SELECT id_gt FROM users WHERE id_user = $id_user");
$user = mysqli_fetch_assoc($user_q);
if ($user && $user['id_gt'] != 0) {
    header("Location: gamemenu.php?error=already_in_game");
    exit;
}

// 5. Все проверки пройдены, присоединяем игрока
$mysql->sql_query("UPDATE users SET id_gt = $id_gt WHERE id_user = $id_user");
$mysql->sql_query("UPDATE game_tables SET num_user = num_user + 1 WHERE id_gt = $id_gt");

// Добавляем игрока в таблицу game_players, если его там еще нет
$mysql->sql_query("INSERT INTO game_players (id_gt, id_user, login) 
                   VALUES ($id_gt, $id_user, '".addslashes($login)."')
                   ON DUPLICATE KEY UPDATE id_gt = $id_gt"); // На случай если он уже был и вышел

// Сохраняем id стола в сессию
$_SESSION['id_gt'] = $id_gt;

// 6. Перенаправляем в игровое меню, которое теперь покажет стол
// Добавляем ID стола в URL, чтобы гарантировать вход, даже если сессия "глючит"
header("Location: gamemenu.php?profile=my&id=" . $id_gt);
exit;
?>