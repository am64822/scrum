<?php

namespace app\engine;

/* Назначение класса: 
- управления областью разделяемой памяти (далее - "shmb") для хранения оперативных настроек
- обеспечения доступа чтение-запись к отдельным оперативным настройкам
Данные в области разделяемой памяти хранятся в формате JSON <---------
Во всех методах на входе - строкоый индентификатор (один символ) области разделяемой памяти.  
Поддержтваемые методы:
- создание области разделяемой памяти при ее отсутствии
- чтение одиночного значения из shmb
- изменение (запись) одиночного значения в shmb
- чтение всех значений из shmb
- изменение (запись) всех значений в shmb
- загрузка json из внешнего файла в shmb
- выгрузка из shmb во внешний файл (json) 
Для упрощения реализации семафоры не используются (пока).
*/

abstract class Shmb {
    private static $mykey;
    
    private static function setIPCkey($id) {
        $mykey = ftok(ROOT_DIR, $id); // convert a pathname and a project identifier to a System V IPC key
        self::$mykey = $mykey;
        //echo "IPC key: ".$mykey."<br>"; 
    }
    
    public static function initialize($id, $params = null): string { // на входе: строкоый индентификатор (один символ) области разделяемой памяти, список свойств объекта json. На выходе - ok или nok
        // Пытаемся создать shmb с идентификатором, равным $mykey, размером 1024 байт. Use this flag ("n") when you want to create a new shared memory segment but if one already exists with the same flag, fail. 

        self::setIPCkey($id);          
        
        // Отключаем вывод Warning, т.к. если shmb не существует, то будет Warning
        ini_set('display_errors', 0);
        
        $shm_id = shmop_open(self::$mykey, "a", 0, 0); // false, если не удается открыть, т.е. скорее всего, отсутствует

        if ($shm_id == false) { // если false, то пытаемся создать
            $shm_id = shmop_open(self::$mykey, "n", 0644, 1024);
        }        

        // Включаем вывод Warning обратно
        ini_set('display_errors', 1);        
        
        if ($shm_id == false) { // не создается
            return 'nok';
        }
        
        shmop_close($shm_id);
        return 'ok';
    }
    
    
    public static function setSingle($id, $key, $value): string { // на входе: строкоый индентификатор (один символ) области разделяемой памяти, имя существующей настройки (ключ записи) и значение, на выходе ok или nok.               
        $settings = self::getAll($id);
        if ($settings == 'nok') { return 'nok'; } // настройки не читаются
        if (!isset($settings[$key])) {
           return 'nok'; // настройки с требуемым ключом нет
        }
        $settings[$key] = $value;
        return self::setAll($id, $settings);
    }
    
    
    public static function getSingle($id, $key): string { // на входе: строкоый индентификатор (один символ) области разделяемой памяти, имя существующей настройки (ключ записи), на выходе - значение или nok 
        $settings = self::getAll($id);
        if ($settings == 'nok') { return 'nok'; } // настройки не читаются
        if (!isset($settings[$key])) {
           return 'nok'; // настройки с требуемым ключом нет
        }
        return $settings[$key];
    }

    
    public static function setAll($id, $settings_array): string { // запись всех настроек из массива на входе в shmb (в json). На входе: строкоый индентификатор (один символ) области разделяемой памяти, массив, на выходе: ok или nok
        self::setIPCkey($id); 
        
        $interim = json_encode($settings_array, true);
        if ($interim == false) { return 'nok'; } // массив не конвертируется в json
        
        $shm_id = shmop_open(self::$mykey, "w", 0, 0);
        if ($shm_id == false) { return 'nok'; } // не открывается        
        
        $bytes_written = shmop_write($shm_id, str_repeat(' ',1024), 0); // очистить shmb, иначе может остаться мусор
        $bytes_written = shmop_write($shm_id, $interim, 0);
        shmop_close($shm_id);
        
        if ($bytes_written == 0) { return 'nok'; } // ничего не записалось*/
        return 'ok';
    }
    
    
    public static function getAll($id) { // чтение всех настроек из shmb (в json) в массив на выходе. На входе: строкоый индентификатор (один символ) области разделяемой памяти, на выходе: массив, nok или empty
        
        self::setIPCkey($id); 
        
        $shm_id = shmop_open(self::$mykey, "a", 0, 0);
        if ($shm_id == false) { return 'nok'; } // не открывается        
        
        $interim = shmop_read($shm_id, 0, 0);
        if ($interim == false) { return 'nok'; } // не читается
        
        shmop_close($shm_id);
        
        $interim = trim($interim); // возвращается весь shmb, поэтому нужно убрать ненужные пробелы (trim)
        if (strlen($interim) == 0) { return 'empty'; }
        
        $interim = json_decode($interim, true); 
        
        if (is_null($interim)) { return 'nok'; } // не конвертируется
        return $interim;
    }

    
    public static function remove($id) { // удаление shmb. На входе: строкоый индентификатор (один символ) области разделяемой памяти, на выходе: ok или nok
        self::setIPCkey($id); 
        
        $shm_id = shmop_open(self::$mykey, "a", 0, 0);
        if ($shm_id == false) { return 'nok'; } // не открывается        
        if (shmop_delete($shm_id)) {
            return 'ok'; // удаление успешно
        } else { return 'nok'; }
    }
    
    
    public static function actionLoad($id) { // попытаться инициализировать shmb (открыть, если не открывается - создать). Если в shmb пусто, то считать json из файла shmb_{$id} и записать в shmb. На входе: строкоый индентификатор (один символ) области разделяемой памяти. На выходе: ok или nok
        if (self::initialize($id) == 'nok') { return 'nok'; } // shmb не инициализируется 
        
        if (self::getAll($id) == 'empty') { // если shmb пусто, то считать из файла с настройками в shmb
            $settingsPath = ROOT_DIR . DS . 'config' . DS;
            $settings = file_get_contents("{$settingsPath}shmb_{$id}"); // json

            if($settings == false) { return 'nok'; } // не конвертируется 

            $settings = json_decode($settings, true);

            if (self::setAll($id, $settings) == 'nok') { return 'nok'; } // в shmb не записывается  
        }                 
        return 'ok';
    }

    
    public static function actionSave($id, $settingsFileStorage = null) { // ok или nok
    // При перезагрузке, файлы скриптом копируются из хранилища в директорию проекта. Поэтому настройки нужно менять не только в файле проекта, но и в файле хранилища.
        
        $settings = self::getAll($id); // массив, nok или empty 
        
        if ($settings == 'nok') { // настроки не читаются из shmb
            return 'nok';
        } elseif ($settings == 'empty') { // в shmb пусто
            return 'nok';
        } else {
            $settings = json_encode($settings);
  
            $settingsPath = ROOT_DIR . DS . 'config' . DS;
            if (file_put_contents("{$settingsPath}shmb_{$id}", $settings) == false) { // json 
                return 'nok'; // данные не записываются в файл настроек в проекте
            }
            
            if (!is_null($settingsFileStorage)) {
                if (file_put_contents($settingsFileStorage, $settings) == false) {
                    return 'nok'; // данные не записываются в файл настроек в хранилище    
                }
            }
        }              
        return 'ok';        
    }    
    
    
}