<?php

// Подключение файла соединения с БД
require_once('db.php');

// старт сессии
session_start();

// осуществление доступа админке
if (!isset($_SESSION['user']['is_login']) && !isset($_COOKIE['user']['is_login'])) {
    header('Location: /'); // редирект на главную
    exit;
}
if (!isset($_COOKIE['user']['is_login']) && isset($_SESSION['user']['is_login']) && $_SESSION['user']['name'] != 'admin') {
    header('Location: /'); // редирект на главную
    exit;
}
if (!isset($_SESSION['user']['is_login']) && isset($_COOKIE['user']['is_login']) && $_COOKIE['user']['name'] != 'admin') {
    header('Location: /'); // редирект на главную
    exit;
}

// если сессия с флеш-сообщениями не существует
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = []; // создать сессию
} else {
    // если сессия существует, записать значения в соответствующие переменные
    $errors = $_SESSION['messages']['errors'] ? $_SESSION['messages']['errors'] : null;
    $success = $_SESSION['messages']['success'] ? $_SESSION['messages']['success'] : null;

    // уничтожить сессию
    unset($_SESSION['messages']);
}

// если сессия с данными пользователя не существует
if (!isset($_SESSION['user'])) {
    // если существуют куки с данными
    if (isset($_COOKIE['user'])) {
        $isLogin = $_COOKIE['user']['is_login'];
        $name = $_COOKIE['user']['name'];
        $email = $_COOKIE['user']['email'];
    }
} else {
    // если же сессия с данными пользователя существует
    $isLogin = $_SESSION['user']['is_login'];
    $name = $_SESSION['user']['name'];
    $email = $_SESSION['user']['email'];
}

/**
 * Получение всех комментариев из базы
 *
 * @param [object] $pdo
 * @return array
 */
function getAllComments($pdo) {
    // формируем sql-запрос
    $sql = "SELECT cs.*, us.name, us.image
            FROM comments AS cs 
            LEFT JOIN users AS us 
            ON cs.user_id = us.id 
            ORDER BY cs.id DESC";
    // выполняем sql-запрос
    $stmt = $pdo->query($sql);
    // формируем ассоциативный массив полученных данных
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // возвращаем массив
    return $row;
}

/**
 * Формирование красивой даты для вывода
 *
 * @param [string] $date
 * @return string
 */
function prettyDate($date) {
    // формирование массива из строки
    $arr = explode('-', $date);
    // реверс массива
    $arr_rev = array_reverse($arr);
    // формирование строки с датой из массива
    $date = implode('/', $arr_rev);

    return $date;
}

// получение массива комментариев
$comments = getAllComments($pdo);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Comments</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="css/app.css" rel="stylesheet">

    <!-- Scripts -->
    <script src="markup/js/jquery.min.js" defer></script>
    <script src="markup/js/bootstrap.js" defer></script>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="/">
                    Project
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->
                        <?php if (isset($isLogin)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?= $name ?>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="profile.php">Профиль</a>
                                <a class="dropdown-item" href="logout.php">Выход</a>
                            </div>
                        </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="register.php">Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-12">
                        <?php if (isset($errors['status'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= $errors['status'] ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <?= $success ?>
                            </div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-header"><h3>Админ панель</h3></div>

                            <div class="card-body">
                                <?php if (!empty($comments)): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Аватар</th>
                                            <th>Имя</th>
                                            <th>Дата</th>
                                            <th>Комментарий</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($comments as $comment): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?= ($comment['image'] == 'no-user.jpg') ? 'img/' . $comment['image'] : 'uploads/' . $comment['image']?>" alt="" class="img-fluid" width="64" height="64">
                                                </td>
                                                <td><?= $comment['name'] ?></td>
                                                <td><?= prettyDate($comment['date']) ?></td>
                                                <td><?= $comment['text'] ?></td>
                                                <td>
                                                    <?php if ($comment['status']): ?>
                                                        <a href="update.php/?id=<?= $comment['id'] ?>" class="btn btn-warning">Запретить</a>
                                                    <?php else: ?>
                                                        <a href="update.php/?id=<?= $comment['id'] ?>" class="btn btn-success">Разрешить</a>
                                                    <?php endif; ?>
                                            
                                                    <a href="delete.php/?id=<?= $comment['id'] ?>" onclick="return confirm('Вы действительно хотите удалить запись?')" class="btn btn-danger">Удалить</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <!-- Если комментарии в таблице отсутствуют -->
                                <?php else: ?>
                                    <span>Комментариев пока нет.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
