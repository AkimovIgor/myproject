<?php
/* ОБРАБОТЧИК УДАЛЕНИЯ КОММЕНТАРИЯ */

// подключаемся к бд
require_once('db.php');

// стартуем сессию
session_start();

// если нет параметра id в запросе
if (!isset($_GET['id'])) {
    header('Location: /admin.php'); // редирект в админку
    exit();
}

/**
 * Удаление комментария
 *
 * @param [object] $pdo
 * @return void
 */
function daleteComment($pdo) {

    // получаем ID удаляемого коммента
    $comId = (integer) trim(htmlspecialchars($_REQUEST['id']));

    // проверяяем, существует ли этот коммент в базе
    $sql = "SELECT cs.id, cs.date, us.name 
            FROM comments AS cs 
            LEFT JOIN users AS us 
            ON cs.user_id = us.id 
            WHERE cs.id = $comId 
            LIMIT 1";

    $stmt = $pdo->query($sql);
    $delete = $stmt->fetch();

    // если коммент существует
    if($delete['id']) {

        // удаляем коммент
        $sql = "DELETE FROM comments 
            WHERE id = $comId";

        $stmt = $pdo->query($sql);

        // записываем флешки
        if ($stmt) {
            $messages['success'] = 'Запись с ID[<b>' . $delete['id'] . '</b>] пользователя <b>' . $delete['name'] . '</b> от <b>' . $delete['date']  . '</b> успешно удалена!';
        } else {
            $messages['errors']['status'] = 'Ошибка удаления комментария!';
        }
    } else {
        $messages['errors']['status'] = 'Такого комментария не существует!';
    }

    // сохраняем флеш в сессию
    $_SESSION['messages'] = $messages;

    // редиректим в админку
    header('Location: /admin.php');
}

daleteComment($pdo);