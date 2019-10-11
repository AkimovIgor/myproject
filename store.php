<?php
// Подключение файла соединения с БД
require_once 'db.php';

// старт сессии
session_start();

/**
 * Получение данных из запроса (получение данных из формы и дальнейшая работа с ними)
 *
 * @param [object] $pdo
 * @return void
 */
function getRequestData($pdo) {
    $name = $_POST['name'] ? trim(htmlspecialchars($_POST['name'])) : null; // получение имени комментатора
    $text = $_POST['text'] ? trim(htmlspecialchars($_POST['text'])) : null; // получение текста комментария
    $date = date('Y-m-d');                                                  // устаковка даты добавления нового комментария
    $image = 'no-user.jpg';                                                 // изображение комментатора (заглушка)

    $messages = []; // массив для хранения флеш-сообщений

    // если все поля были успешно заполнены
    if ($name && $text) {
        // формируем sql-запрос в базу данных
        $sql = "INSERT INTO comments 
                (name, text, date, image) 
                VALUES (:name, :text, '$date', '$image')";

        // подготавливаем запрос перед выполнением (для защиты от sql-инъекций)
        $stmt = $pdo->prepare($sql);

        // связываем подготовленные данные
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':text', $text);

        // выполнение запроса
        $stmt->execute();

        // запись в массив флеш-сообщения об успехе
        $messages['success'] = 'Комментарий успешно добавлен';

        // добавление в сессию массива с флещ-сообщениями для вывода
        $_SESSION['messages'] = $messages;
    } else {
        if (!$name) {
            $messages['errors']['name'] = 'Введите Ваше имя!';
        }
        if (!$text) {
            $messages['errors']['text'] = 'Введите Ваш комментарий!';
        }
        $_SESSION['messages'] = $messages;
    }
    header('Location: /'); // редирект (перенаправление) на главную страницу
}

// вызов функции
getRequestData($pdo);
