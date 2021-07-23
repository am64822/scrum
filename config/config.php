<?php

define('ROOT_DIR', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
define('CONTROLLERS_NAMESPACE', "app\\controllers\\");
define('TEMPLATE_DIR', dirname(__DIR__) . DS . 'views' . DS);

define('CONFGPIO', "config/configGPIOs.php");
define('GPIOEXPFILE', '/sys/class/gpio/export');
define('PATHGPIO', '/sys/devices/platform/soc/fe200000.gpio/gpiochip0/gpio/gpio'); // это начало имени директории! в конце добавить номер GPIO. Поддиректории: direction и value
define('CpuTempFanThreshold', '45');
define('CpuTempShutdownThreshold', '70');
define('MAX_FAILED_LOGINS', 3); // кол-во неудачных логинов, после которого на указанное время активируется блокировка на вход 
define('LOGIN_TIMEOUT', 900); // время блокировки на вход в секундах
