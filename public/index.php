<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL);

// ошибки
// Недопустимое имя файла класса {$classFile}", 100

//session_start();

include '../config/config.php';
include '../engine/Autoload.php';

use app\engine\{Autoload, InitializeGPIO};
use app\controllers\{FrontendController, TestController};

spl_autoload_register(function ($className) {
    (new Autoload())->loadClass($className);
});

FrontendController::run();
