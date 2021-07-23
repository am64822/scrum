<?php

namespace app\controllers;

use app\engine\{GPIO, Shmb};

class AuthController {
    public function actionLogin($vals) { // возврат - ok (успешная авторизация), nok (неуспешная авторизация), err - ошибка
        $error = false;
        
        if (time() < +Shmb::getSingle('s', 'waitFrom') + LOGIN_TIMEOUT) { // проверка на истечение таймаута
            echo('nok4');
            return;
        } 
        
        $loggedIn = true;
        
        $vals = json_decode($vals, true); // имя пользователя и пароль
        
        if ($vals['userName'] != Shmb::getSingle('s', 'userName')) {
            $loggedIn = false;
        }
        
        if (password_verify($vals['pwd'], Shmb::getSingle('s', 'pwd')) == false) {
            $loggedIn = false;
        }
        
        if ($error == true) {
            echo('err');
            return;
        } elseif ($loggedIn == true) {
            if (session_status() != PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION['loggedIn'] = true;
            Shmb::setSingle('s', 'failedLogins', '0');
            echo('ok');
            return;
        } elseif ($loggedIn == false) {
            $failedLogins = +Shmb::getSingle('s', 'failedLogins') + 1;
            Shmb::setSingle('s', 'failedLogins', $failedLogins);
            if ($failedLogins >= MAX_FAILED_LOGINS) { // превышено макс. кол-во неудачных попыток входа. Активация таймаута
                Shmb::setSingle('s', 'waitFrom', time());
                echo('nok3'); 
            } else {
                echo('nok'); // неудачная попытка входа
            }
            
        }  
    }
    
    public function actionLogout() {
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (isset($_SESSION['loggedIn'])) {
            unset($_SESSION['loggedIn']);
            echo('ok');
        }
        
        session_destroy();
    }
    
    public static function isLoggedIn() {
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (!isset($_SESSION['loggedIn'])) {
            return false;    
        }
        if ($_SESSION['loggedIn'] != true) {
            return false;    
        }
        return true;
    }
    
}