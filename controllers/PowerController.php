<?php

namespace app\controllers;
use app\engine\{Shmb};
use app\controllers\{AuthController};

class PowerController {
    public function actionShutdown_reboot($action) { // на входе ключ: shutdown или reboot
        if (AuthController::isLoggedIn() == false) {echo 'nok'; return 'nok';} 

        $status = 'ok';
        
        // записать из shmb с командами в файл        
        if (Shmb::actionSave('b', "/home/lk/html/antinoise/config/shmb_b") != 'ok') { $status = 'ok'; }
        
        // удалить shmb с командами
        if (Shmb::remove('b') != 'ok') { $status = 'ok'; }
        
        // вызвать скрипт
        echo($status);  // ok или nok. Echo - до выключения, иначе на команду с клиента не придет ответ и будет отображена ошибка
        exec("sudo /home/www-data/shutdownRPi " . $action, $output, $retval);
    } 
}

/*

#!/bin/bash
if [ "$1"="shutdown" ]
then
    shutdown now
elif [ "$1"="reboot" ]
then
    systemctl reboot
fi

*/