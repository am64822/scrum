<?php

namespace app\engine;

class Autoload
{
    public function loadClass($className) {
        //echo('load classname<br>');
        //echo($className);
        //echo('<br><br>');
        $ds = DIRECTORY_SEPARATOR;
        $classFile = (str_replace(['app', '\\'], [ROOT_DIR, $ds], $className) . '.php');
        
        if (!file_exists($classFile)) {
            throw new \Exception("Недопустимое имя файла класса {$classFile}", 100);
        } else {
            include str_replace(['app', '\\'], [ROOT_DIR, $ds], $className) . '.php';
        }
    }
}