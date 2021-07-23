<?php

namespace app\engine;

abstract class Render {
    public static function render(string $template, string $content, string $script = null, array $params = null) {
        if (!is_null($params)) { 
            if (count($params) > 0) {
                extract($params);
            }
        }
        
        include ROOT_DIR.DS.'views'.DS.$template; // включение содержимого и скриптов в шаблон - в шаблоне (при помощи php)
    
    }
}