<?php
/* Выход из учетной записи пользователя */

session_start();

if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
    header('Location: /');
}
if (isset($_COOKIE['user'])) {
    setcookie('user[name]', '', time()-5);
    header('Location: /');
}