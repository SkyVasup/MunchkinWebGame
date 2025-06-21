<center><h2>Уже начатые игры</h2></center><br>

<div id="game_list">
<?php 
if (isset($_SESSION['id_user'])) {
    $mysql->sql_query('UPDATE users SET active=' . time() . ', last_ip="' . getIP() . '", last_page="other" WHERE (id_user=' . $_SESSION['id_user'] . ')');
}

$result = $mysql->sql_query('SELECT * FROM game_tables WHERE (gt_status=1 OR gt_status=3) ORDER BY timestamp DESC LIMIT 20');

if (mysqli_num_rows($result) != 0) {
?>
    <table style="border-collapse:collapse;" border="1">
        <tbody>  
        <tr>
            <td align="center" valign="top" width="15%">Имя стола</td>
            <td align="center" valign="top" width="25%">Игроки</td>
            <td align="center" valign="top" width="10%">Кол-во игроков</td>
            <td align="center" valign="top" width="15%">Ограничение по игрокам</td>   
            <td align="center" valign="top" width="20%">Последняя активность</td>
            <td align="center" valign="top" width="10%">Действие</td>
        </tr>   
        <?php	
        while ($row = mysqli_fetch_array($result)) {
            $result1 = $mysql->sql_query("SELECT * FROM users WHERE (id_gt={$row['id_gt']} AND login <> '{$row['creator']}')");
            $gamer = '<b>' . $row['creator'] . '</b>';

            if (mysqli_num_rows($result1) != 0) {
                while ($row1 = mysqli_fetch_array($result1)) {
                    $gamer .= ", " . $row1['login'];
                }
            }
        ?>              
        <tr>
            <td align="center" valign="top"><?= $row['name'] ?></td>
            <td align="center" valign="top"><?= $gamer ?></td>
            <td align="center" valign="top"><?= $row['num_user'] ?></td>
            <td align="center" valign="top"><?= $row['limit_user'] ?></td>
            <td align="center" valign="top">
                <?php
                $lastacttime = "";
                $differencetime = time() - $row['timestamp'];

                if ($differencetime < 86400) {
                    if ($differencetime < 3600) {
                        $lastacttime = ceil($differencetime / 60) . " минут назад";
                    } else {
                        $lastacttime = ceil($differencetime / 3600) . " часов ";
                        $lastacttime .= ceil((ceil($differencetime / 3600) * 3600 - $differencetime) / 60) . " минут назад ";
                    }
                } else {
                    if (date("d", $row['timestamp']) == date("d")) {
                        $lastacttime = "Сегодня в ";
                    } elseif (date("d", $row['timestamp']) == date("d") - 1) {
                        $lastacttime = "Вчера в ";
                    } else {
                        $lastacttime = date("d.m", $row['timestamp']) . " в ";
                    }
                    $lastacttime .= date("H:i:s", $row['timestamp']);
                }

                echo $lastacttime;
                ?>
            </td>
            <td align="center" valign="middle">
                <a href="spectate.php?id=<?= $row['id_gt'] ?>">
                    <img src="picture/binoculars.png" alt="Наблюдать" title="Войти в режим наблюдателя" />
                </a>
                <?php
                // Показываем кнопку "Присоединиться", если есть места
                if ($row['num_user'] < $row['limit_user']) {
                    echo '<a href="gamemenu.php?profile=join&id='.$row['id_gt'].'" title="Присоединиться к игре">';
                    echo '<img src="picture/joingame.png" alt="Присоединиться" />';
                    echo '</a>';
                }
                ?>
            </td>
        </tr>
        <?php	
        }
        ?>
        </tbody>
    </table>
<?php
}
?>
</div>
