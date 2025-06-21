<?php
session_start();
require_once("../global.php");
require_once("../chat.php");
require_once("../modules/mysql.php");
require_once("../modules/functions.php");
require_once("game_table.php");

header("Content-Type: text/html; charset=utf-8");

if (isset($_SESSION['id_gt_spec'])) {
    show_table();
}
?>
