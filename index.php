<?php
// Подключение файла соединения с БД
require_once('db.php');

// старт сессии
session_start();

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

/**
 * Получение ID текущего пользователя
 *
 * @param [object] $pdo
 * @param [string] $email
 * @return integer
 */
function checkUser($pdo, $email) {
    // выбираем ID пользователя с текущим email
    $sql = "SELECT id 
            FROM users 
            WHERE email = '$email' 
            LIMIT 1";

    $stmt = $pdo->query($sql);
    
    $row = $stmt->fetch();
    // возвращаем ID
    return $row['id'];
}
// присваиваем переменной результат выполнения функции
$comments = getAllComments($pdo);

// получение ID текущего пользователя
$userId = checkUser($pdo, $email);
    
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
                        <div class="card">
                            <div class="card-header"><h3>Комментарии</h3></div>

                            <div class="card-body">
                                
                                <!-- Вывод флеш-сообщения в случае успеха -->
                                <?php if (isset($success)): ?>
                                    <div class="alert alert-success" role="alert">
                                        <?= $success ?>
                                    </div>
                                <?php endif; ?>
                                <!-- Если таблица с комментарими не пуста -->
                                <?php if (!empty($comments)): ?>
                                    <!-- Вывод данных каждого комментария -->
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="media">
                                            <img src="<?= ($comment['image'] == 'no-user.jpg') ? 'img/' . $comment['image'] : 'uploads/' . $comment['image']?>" class="mr-3" alt="..." width="64" height="64">
                                            <div class="media-body">
                                                <h5 class="mt-0"><?= $comment['name'] ?></h5> 
                                                <span><small><?= prettyDate($comment['date']) ?></small></span>
                                                <p>
                                                    <?= $comment['text'] ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <!-- Если комментарии в таблице отсутствуют -->
                                <?php else: ?>
                                    <span>Комментариев пока нет.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                
                    <div class="col-md-12" style="margin-top: 20px;">
                        <?php if (isset($isLogin)): ?>
                        <div class="card">
                            <div class="card-header"><h3>Оставить комментарий</h3></div>
                            
                            <div class="card-body">
                                <form action="store.php" method="POST">
                                    <div class="form-group">
                                        <input type="hidden" name="id" class="form-control" value="<?= $userId ?>"/>
                                    </div>
                                    <div class="form-group">
                                        <label for="exampleFormControlTextarea2">Сообщение</label>
                                        <textarea name="text" class="form-control" id="exampleFormControlTextarea2" rows="3"></textarea>
                                        <!-- Вывод флеш-сообщения -->
                                        <?php if (isset($errors['text'])): ?>
                                            <span class="text-danger"><?= $errors['text'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                  <button type="submit" class="btn btn-success">Отправить</button>
                                </form>
                            </div>
                        </div>
                        <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                Чтобы оставить комментарий <a href="login.php" class="alert-link">авторизуйтесь</a>.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
