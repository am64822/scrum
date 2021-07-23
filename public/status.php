<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL);

// ошибки
// Недопустимое имя файла класса {$classFile}", 100

//session_start();

include '../config/config.php';
include '../engine/Autoload.php';

use app\engine\{Autoload, InitializeGPIO, Shmb};
use app\controllers\{MainController, TestController};

spl_autoload_register(function ($className) {
    (new Autoload())->loadClass($className);
});

echo('<pre>');
//echo('Инициализация shmb: ' . Shmb_op::initialize() . '<br>');
//$testArray['volume'] = '70';
//echo('Настройки:<br>');
//var_dump($testArray);
//echo('Запись настроек: ' . Shmb_op::setAll($testArray) . '<br>');
echo('Чтение статуса: <br>');
var_dump(Shmb::getAll('s')); echo ('<br>');

echo('Чтение команд: <br>');
var_dump(Shmb::getAll('b')); echo ('<br>');

//echo('Запись настроек в файл: '. Shmb_load_saveController::actionSave() . '<br>');
/*echo('Чтение настройки volume: ' . Shmb_op::getSingle('volume'). '<br>');
echo('Запись настройки volume: ' . Shmb_op::setSingle('volume', '60'). '<br>');
echo('Чтение настройки volume: ' . Shmb_op::getSingle('volume'). '<br>');*/
//echo('Удаление shmb: ' . Shmb_op::remove() . '<br>');
//echo('Чтение настроек: <br>');
//var_dump(Shmb_op::getAll()); echo ('<br>');
