<?php

namespace app\controllers;
use app\controllers\{ProgramController, SettingsController, PowerController};
use app\engine\{GPIO, Shmb, Render};

class FrontendController 
{   
    private static $defaultController = '';
    private static $defaultAction = '';
    private static $defaultLayout = 'main';
    
    public static function run() {
        // определить параметры запроса (POST)
            // (c)program: (a)single
            // (c)power: (a)shutdown_reboot 
            // (c)settings: (a)change
            // в плане (c)display: / (a)request
            // в плане (c)user: (a)login / logout

        // определить контроллер и метод. Если не определяется - не делать ничего
        // вызвать контроллер. Возвращает шаблон и контент
        // вызвать рендерер
        
        // определить параметры запроса (POST)
        $doNothing = false;
        $controller = static::$defaultController;
        $action = static::$defaultAction;
        
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['c'])) {
                $controllerInterim = __NAMESPACE__ .'\\'.ucfirst($_POST['c'].'Controller');
                if (class_exists($controllerInterim)) {
                    $controller = $controllerInterim;
                } else {$doNothing = true;}   
            } else {$doNothing = true;}
            
            if (isset($_POST['a']) && ($controller != static::$defaultController)) {
                $actionInterim = 'action' . (ucfirst($_REQUEST['a']));
                if (method_exists($controller, $actionInterim)) {
                    $action = $actionInterim;
                } else {$doNothing = true;}   
            } else {$doNothing = true;}

            //echo(json_encode(['c'=>$controller, 'a'=>$action], JSON_PRETTY_PRINT));
            //echo ('Controller ' . $controller . '<br>'); 
            //echo ('Method ' . $action . '<br>'); */   
            
            (new $controller)->$action(isset($_POST['v']) ? $_POST['v'] : null);
            
            //var_dump(get_class_methods(get_class($test)));
            
            
        } 
        
        
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                            
            $myIP = $_SERVER['SERVER_ADDR'];

            // инициализировать shmb для статуса. Идентификатор shmb -> s = Status
            Shmb::actionLoad('s'); // если в shmb пусто, то считать в shmb из файла с состояниями            
            Shmb::setSingle('s', 'myIP', $myIP);
            $running = Shmb::getSingle('s', 'running');
            $monitor = 0;

            // считать оперативные настройки. Идентификатор shmb -> b = to Be
            Shmb::actionLoad('b'); // если в shmb пусто, то считать в shmb из файла с командами 
            $settings = Shmb::getAll('b');
            $params = array_merge($settings, array('running' => $running, 'monitor' => $monitor, 'myIP' => $myIP));          

            // выполнить предустановки громкости
            SettingsController::changeSystemVolume($settings['vol_L'], $settings['vol_R']);

            if (session_status() != PHP_SESSION_ACTIVE) {
                session_start();    
            }

            if (!isset($_SESSION['loggedIn'])) {
                // отобразить. Шаблон - наполнение - доп.скрипт - параметры
                Render::render('main.php', 'login.php', 'login.js'); // template - из ROOT_DIR/views, content - из ROOT_DIR/views, script - из ROOT_DIR/scripts
                //include ROOT_DIR.DS.'views'.DS.$template;
            } elseif ($_SESSION['loggedIn'] != true) {
                Render::render('main.php', 'login.php', 'login.js');
            } else {
                Render::render('main.php', 'controls.php', 'controls.js', $params);
            }
        } 
    } // end of run method
    
    
    
    
    
    
}
    
    
    
    
