<?php

namespace app\controllers;
use app\engine\{Shmb};
use app\controllers\{SettingsController, AuthController};

class ProgramController {
    public function actionSingle($params = null) {
        if (AuthController::isLoggedIn() == false) {echo 'nok'; return 'nok';} 
        
        $retval = 2;
        
        set_time_limit(0);
        // hw:CARD=Headphones,DEV=0    hw:CARD=b1,DEV=0
        $root_dir = ROOT_DIR;       
        
        $times = Shmb::getSingle('b', 'times');
        Shmb::setSingle('s', 'times_left', $times);
        
        $times_left;
        $cycle_start_delay; // hrtime, array
        $cycle_end_delay; // hrtime, array
        $current_time; // hrtime, array
        // Shmb::setSingle('b', 'running', '1'); // это лишнее
        Shmb::setSingle('b', 'run', '1');
        Shmb::setSingle('s', 'running', '1');
        
        for ($i=1; $i<=$times; $i++) {    
            Shmb::setSingle('s', 'next_in_sec', '0');
            $params = Shmb::getAll('b'); // массив, nok или empty
            $times = $params['times'];
            $delay_min = $params['delay_min'];
            $delay_max = $params['delay_max'];
            $vol_L = $params['vol_L'];
            $vol_R = $params['vol_R'];            
            $sound = $params['sound']; // "1" > Перф.дл., "2" > Перф.кор., "3" > Стук.дл., "4" > Стук.кор.
            $run = $params['run'];
            
            if ($run == 'nok') { echo 'nok'; return 'nok'; }
            if ($run == '0') { break; } // выйти из цикла, если run сброшен   
            
            if ($sound == 8) { // лай од.сер. Гавкает несколько раз сначала с одной стороны, потом - с другой.
                if (rand(0,1) == 0) { ; // 0 - лев., 1 - прав.
                    $sequence = [[$vol_L, 0], [0, $vol_R]];
                } else {
                    $sequence = [[0, $vol_R], [$vol_L, 0]];
                }
                
                for ($jj=0; $jj<=1; $jj++) { 
                    SettingsController::changeSystemVolume($sequence[$jj][0], $sequence[$jj][1]);
                    $incycle = rand(2, 4);
                    for ($j=1; $j<=$incycle; $j++) {
                        $sound = 7;
                        exec("sudo /home/www-data/singlblow {$root_dir}/sounds/{$sound}.wav", $output, $retval);
                    }
                    SettingsController::changeSystemVolume($vol_L, $vol_R);
                    sleep(rand(5,7));
                }
                $sound = 8;
            } elseif ($sound == 10) { // лай серия
                $incycle = rand(3, 5);
                for ($j=1; $j<=$incycle; $j++) {
                    $sound = rand(6, 9);
                    exec("sudo /home/www-data/singlblow {$root_dir}/sounds/{$sound}.wav", $output, $retval);
                }
                $sound = 10;
            } elseif ($sound == 20) { // ремонт
                $incycle = rand(5, 10);
                for ($j=1; $j<=$incycle; $j++) {
                    $sound = rand(1, 5);
                    exec("sudo /home/www-data/singlblow {$root_dir}/sounds/{$sound}.wav", $output, $retval);
                }
                $sound = 20;
            } else {
                exec("sudo /home/www-data/singlblow {$root_dir}/sounds/{$sound}.wav", $output, $retval);
            }

            $times_left = $times - $i; // записать в shmb (статус) кол-во оставшихся циклов
            Shmb::setSingle('s', 'times_left', $times_left);            

            if (($times != 1) && ($i != $times)) { // задержка
                $current_time = hrtime(false); // hrtime, array. [0] - sec.
                $cycle_start_delay = $current_time; 
                $cycle_end_delay[0] = $cycle_start_delay[0] + rand($delay_min, $delay_max);
                $cycle_end_delay[1] = $cycle_start_delay[1];

                
                while ($cycle_end_delay[0] > $current_time[0]) {
                    if (Shmb::getSingle('b', 'run') == 0) {
                        break 2; 
                    }
                    usleep(200000);
                    $current_time = hrtime(false);
                    $next_in_sec = $cycle_end_delay[0] - $current_time[0];
                    if ($next_in_sec < 0) { $next_in_sec = 0; } 
                    Shmb::setSingle('s', 'next_in_sec', $next_in_sec); // записать в shmb (статус) кол-во оставшихся секунд ожидания
                }                                             
            }                    
        }
        
        // Shmb::setSingle('b', 'running', '0'); // это лишнее
        Shmb::setSingle('b', 'run', '0');
        Shmb::setSingle('s', 'running', '0');
        Shmb::setSingle('s', 'times_left', '0');
        Shmb::setSingle('s', 'next_in_sec', '0');
        
        $status = (($retval != 0) ? 'nok' : 'ok');
        /*if ($status == 'nok') {
            var_dump($params);
        }*/
        
        echo $status;
        return $status;
    }    
}