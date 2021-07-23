<?php

namespace app\engine;

class GPIO {
    
    public static function initialize() {
        // !!! входы подтягиваются к 1 (переменная $pullup)        
        
        //чтение config
        //установка прав на /sys/class/gpio/export, unexport (внешний скрипт /home/www-data/setExpUnexpDir)
        //чтобы ОС RPi успела отработать, циклы последовательно
        //цикл: если не nu (not in use), то записать номер GPIO в export. Посчитать кол-во $usedGPIOs (не- nu).
        // если $usedGPIOs > 1, то
        //    установка прав (внешний скрипт /home/www-data/setGpioDir) на 
        //    /sys/devices/platform/soc/fe200000.gpio/gpiochip0/gpio
        //    /sys/devices/platform/soc/fe200000.gpio/gpiochip0/gpio/*/direction
        //    /sys/devices/platform/soc/fe200000.gpio/gpiochip0/gpio/*/value
        //    цикл: если не nu (not in use), то записать in или out в direction
        
        //var_dump(static::$GPIOs); // для отладки
        $usedGPIOs = 0;
        $GPIOs = include(ROOT_DIR . DS . CONFGPIO); // массив из файла
        $pathGPIO = PATHGPIO;
        $GPIOexpFile = GPIOEXPFILE;
        $pullup = 'high';
        
        exec('sudo /home/www-data/setExpUnexpDir', $output, $retval); // изменение прав на export и unexport 
        
        foreach($GPIOs as $key => $value) { // цикл: если не nu (not in use), то записать номер GPIO в export.
            switch ($value) {
                case 'nu':
                    break;
                case 'in':                   
                case 'out':
                    $usedGPIOs += 1;
                    exec("echo {$key} > {$GPIOexpFile}", $output, $retval);
                    break;
            }
        }
        
        if ($usedGPIOs == 0) {return;}
        
        exec('sudo /home/www-data/setGpioDir', $output, $retval);
        foreach($GPIOs as $key => $value) {
            switch ($value) {
                case 'nu':
                    break;
                case 'in':
                    //exec("echo {$pullup} > {$pathGPIO}{$key}/direction", $output, $retval); // pull-up входа
                    exec("echo {$value} > {$pathGPIO}{$key}/direction", $output, $retval);
                    break;
                case 'out':
                    exec("echo {$value} > {$pathGPIO}{$key}/direction", $output, $retval);
                    break;
            }
        }        
    }
    
    
    public static function readGPIO($num) {
        $pathGPIO = PATHGPIO;
        return file_get_contents("{$pathGPIO}{$num}/value"); // false или текст
    }
    
    public static function writeGPIO($num, $value) {
        $pathGPIO = PATHGPIO;
        $nok = false;
        
        // проверить, является ли выходом
        exec("cat {$pathGPIO}{$num}/direction", $output, $retval); 
        
        if ($retval != 0) {return 'nok';}
        if (!is_array($output)) {return 'nok';}
        if ($output[0] != 'out') {return 'nok';}
        
        // записать, проверить статус        
        exec("echo {$value} > {$pathGPIO}{$num}/value", $output, $retval);
        
        if ($retval != 0) {return 'nok';}
        //if (!is_array($output)) {return 'nok';}
        //if ($output[0] != $value) {return 'nok';}
        return 'ok';
    }
    
}