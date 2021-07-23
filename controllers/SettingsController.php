<?php

namespace app\controllers;
use app\engine\{Shmb};

class SettingsController {
    public static function actionChange($params) { // изменяет заданные настройки в shmb. На входе: json параметр - значение, на выходе: ok или nok.  !!! При изменении значения громкости желательно передавать в данный метод одновременно значения Л. и П. каналов.
        if (AuthController::isLoggedIn() == false) {echo 'nok'; return 'nok';} 
        
        $volFlagL = false; // требуется изменение громкости Л.
        $volFlagR = false; // требуется изменение громкости П.
        $params = json_decode($params, true);
        if (!is_array($params)) { echo 'nok'; return 'nok'; } // входные данные не конвертируются в массив
        foreach ($params as $key => $value) {
            if ($key == 'vol_R') { $volFlagR = true; }
            if ($key == 'vol_L') { $volFlagL = true; }
            if (Shmb::setSingle('b', $key, $value) == 'nok') { echo 'nok'; return 'nok'; } // значения не устанавливаются
        }
        
        // особые условия
        // если изменяются значения громкости, одновременно изменить громкость на системном уровне. Предварительно убедиться, что есть оба значения громкости (Л. и П.)
        
        if ($volFlagR == true) {
            $vol_R = $params['vol_R'];
        } else {
            $vol_R = Shmb::getSingle('b', 'vol_R');
            if ($vol_R == 'nok') { echo 'nok'; return 'nok'; } // значение не читается
        }
        
        if ($volFlagL == true) {
            $vol_L = $params['vol_L'];
        } else {
            $vol_L = Shmb::getSingle('b', 'vol_L');
            if ($vol_L == 'nok') { echo 'nok'; return 'nok'; } // значение не читается
        }
        
        if (self::changeSystemVolume($vol_L, $vol_R) == 'nok') { echo 'nok'; return 'nok'; } // громкость на системном уровне не устанавливается
        
        echo 'ok';
        return 'ok';        
    }
    
    
    public static function changeSystemVolume($vol_L, $vol_R) { // Установка громкости на системном уровне. На входе: громкость Л.и П., на выходе: ок или nok
        if (AuthController::isLoggedIn() == false) {return 'nok';} 
        
        $result = 'ok';

        exec("sudo /home/www-data/volume {$vol_L}% {$vol_R}%", $output, $retval);
        if ($retval != 0) {$result = 'nok';}
        
        return $result;
    }
}