<?php

/* демон управления вентилятором. Раз в 10 секунд опрашивает температуру центрального процессора. Результаты записываются в Shared Memory Block (shmb). Если фактическая температура процессора больше пороговой (задается в файле config), то указанный GPIO устанавливается в 1, в противном случае - в 0.
Алгоритм:
- загрузить конфиги и автозагрузчик
- инициализировать GPIO
- инициализировать shmb, загрузить начальное состояние (json) из файла (чтобы был список свойств объекта json) 
- записать в свойство 'fanPID' PID процесса (для мониторинга и отладки)
- инициализировать различные переменные
- бесконечный цикл
    - считать температуру CPU
    - если температура CPU не считывается 10 раз, выключить RPI
    - если темп. CPU > аварийной, то выключить RPI
    - если темп. CPU > пороговой, то установить указанный GPIO (включить вентилятор)
    - если темп. CPU <= пороговой, то сбросить указанный GPIO (выключить вентилятор)
    - обновить PID в shmb
    - обновить значение GPIO в shmb
    - обновить значение температуры CPU в shmb
    - задержка для снижения нагрузки на процессор
*/

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config/config.php';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'engine/Autoload.php';

use app\engine\{Autoload, Shmb, GPIO};
use app\controllers\{PowerController};

spl_autoload_register(function ($className) {
    (new Autoload())->loadClass($className);
});


// функции-----------------------------------------------

// основное тело программы ------------------------------

    // инициализировать GPIO
    GPIO::initialize();

    // считать shmb статуса. Идентификатор shmb -> s = status
    Shmb::actionLoad('s'); // попытаться инициализировать shmb (открыть, если не открывается - создать). Если в shmb пусто, то считать в shmb из файла со статусами 
    
    // записать в свойство 'fanPID' PID процесса (для мониторинга и отладки)
    Shmb::setSingle('s', 'fanPID', getmypid());

    // инициализировать различные переменные
    $cantGetTempCount = 0;
    $CPUtemp = 0;
    $GPIOnr = 17;
    $powCtrl = new PowerController();

while (true) {    

    // считать температуру CPU
    
    $output = shell_exec(escapeshellcmd("sudo /home/www-data/getTemperature"));
    //echo($output);
    
    if (!is_null($output)) {
        
        $cantGetTempCount = 0;
        $CPUtemp = +substr(substr($output, 0, -3), 5);
        
        //echo($CPUtemp); echo(PHP_EOL);
        //echo(CpuTempFanThreshold); echo(PHP_EOL);
        
        Shmb::setSingle('s', 'cpuTemp', $CPUtemp);
        
        if ($CPUtemp > CpuTempShutdownThreshold) { // если темп. CPU > аварийной, то выключить RPI
            $powCtrl->actionShutdown_reboot('shutdown'); 
        }
        
        if (+$CPUtemp > +CpuTempFanThreshold) {
            echo('here');
            if (GPIO::readGPIO($GPIOnr) != 1) {
                GPIO::writeGPIO($GPIOnr, 1);
                Shmb::setSingle('s', 'fan', 1);              
            }
        } else {
            if (GPIO::readGPIO($GPIOnr) != 0) {
                GPIO::writeGPIO($GPIOnr, 0);
                Shmb::setSingle('s', 'fan', 0);              
            }            
        }
        
    } else {
        $cantGetTempCount += 1;
    }
    
    if ($cantGetTempCount >= 10) { // если температура CPU не считывается 10 раз, выключить RPI
        $powCtrl->actionShutdown_reboot('shutdown');  
    }

    sleep(10);
}