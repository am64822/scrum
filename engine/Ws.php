<?php

/* демон WebSocket. Отправляет клиентам содержимое Shared Memory Block (shmb) c текщим состоянием при изменении. Запускается в фоновом режиме при старте ОС. 
Алгоритм:
- загрузить конфиги и автозагрузчик
- инициализировать shmb, загрузить начальное состояние (json) из файла (чтобы был список свойств объекта json) 
- записать в свойство 'wsPID' PID процесса (для мониторинга и отладки)
- инициализировать различные переменные
- создать объект Socket, приаязать IP, ждать входящих коннектов (слушать)
- бесконечный цикл
    - обновить PID в shmb    
    - если через shmb не пришел реальный адрес, то задержка и следующая итерация цикла
    - если поменялся IP, то текущий объект Socket удалить (закрыть сокет), создать новый объект Socket, приаязать IP, ждать входящих коннектов (слушать)
    - новые входящие коннекты выявляются при помощи функции socket_select
    - при новом коннекте ( = клиенте) выполнить handshake, добавить нового клиента в массив [сокетов] клиентов, выставить флаг нового клиента
    - при новом клиенте или при изменении содержимого shmb отправить всем клиентам содержимое shmb. Если сообщение не удается отправить клиенту, удалить клиента из массива [сокетов] клиентов. В конце сбросить флаг нового клиента
    - задержка для снижения нагрузки на процессор
*/

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config/config.php';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'engine/Autoload.php';

use app\engine\{Autoload, Shmb};

spl_autoload_register(function ($className) {
    (new Autoload())->loadClass($className);
});


// функции---------------------------------------------
function mask($text) // маскирование передаваемого сообщения, см. https://learn.javascript.ru/websockets
{
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);

    if($length <= 125)
        $header = pack('CC', $b1, $length);
    elseif($length > 125 && $length < 65536)
        $header = pack('CCn', $b1, 126, $length);
    elseif($length >= 65536)
        $header = pack('CCNN', $b1, 127, $length);
    return $header.$text;
}

//Unmask incoming framed message, см. https://learn.javascript.ru/websockets
function unmask($text) {
    $length = ord($text[1]) & 127;
    if($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    }
    elseif($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    }
    else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i%4];
    }
    return $text;
}


function perform_handshaking($receved_header,$client_conn, $host, $port)
{
    $headers = array();
    $lines = preg_split("/\r\n/", $receved_header);
    foreach($lines as $line)
    {
        $line = chop($line);
        if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
        {
            $headers[$matches[1]] = $matches[2];
        }
    }
    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    //hand shaking header
    $upgrade  = "HTTP/1.1 101 Switching Protocols\r\n" .
    "Upgrade: websocket\r\n" .
    "Connection: Upgrade\r\n" .
    "WebSocket-Origin: $host\r\n" .
    "WebSocket-Location: ws://$host:$port/websocket/websocket.php\r\n".
    "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
    socket_write($client_conn,$upgrade,strlen($upgrade));
} 


function closeSocket($sock) {
    $arrOpt = array('l_onoff' => 1, 'l_linger' => 0); // очистка Socket, чтобы он мог быть закрыт. См. комментарии на странице https://www.php.net/manual/ru/function.socket-close.php
    socket_set_block($sock);
    socket_set_option($sock, SOL_SOCKET, SO_LINGER, $arrOpt);
    //socket_shutdown($sock, 2);           
    socket_close($sock);    
}
// ---------------------------------------------


// основное тело программы ------------------------------

    // считать оперативные настройки. Идентификатор shmb -> b = to Be
    Shmb::actionLoad('b'); // если в shmb пусто, то считать в shmb из файла с командами 
    $settings = Shmb::getAll('b');

    Shmb::actionLoad('s'); // s = status. Инициализировать shmb, загрузить из файла
    Shmb::setSingle('s', 'wsPID', getmypid());


    $clients = array(); // массив [сокетов] клиентов. У каждого клиента уникальный порт!!! См. socket_getpeername($socket, $ip2, $port2);
    $newClient = false; // флаг нового клиента
    $stats = ''; // json состояний
    // задать хост и порт
    $host= Shmb::getSingle('s', 'myIP');
    if ($host == 0) { // реальный собственный адрес появляется при первой загрузке основной страницы клиентом. Данная операция необходима для первоначальной инициализации ws сокета до первой загрузки основной страницы клиентом. 
        $host= '127.0.0.1';
    }

    $port= 9000;
    $null = NULL; //null var
	//set_time_limit(0);
    
    
    // создать сокет с параметрами. Возвращает экземпляр Socket в случае успешного выполнения, или false в случае возникновения ошибки. 
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); //or die("Could not create socket");
    socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1); //or die("Could not create socket");

    // привязать сокет к хосту и порту. Возвращает true в случае успешного завершения или false в случае возникновения ошибки.
    socket_bind($socket, $host, $port); //or die("Could not bind to socket");
 
    // ожидать подключение. Возвращает true в случае успешного завершения или false в случае возникновения ошибки.
    socket_listen($socket); //or die("Could not set up socket listener");
    $blowCounter = 0;

while (true) {    
    $blowCounter += 1;
    if ($blowCounter == 30) {
        Shmb::setSingle('s', 'blow', !Shmb::getSingle('s', 'blow'));
        $blowCounter = 0;
    }
    
    Shmb::setSingle('s', 'wsPID', getmypid());
        
    $previousHost = $host;
    $host = Shmb::getSingle('s', 'myIP');
    if ($host == '127.0.0.1') {
        usleep(100000);
        continue;
    }
    
    if ($previousHost != $host) {
        socket_close($socket);
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); //or die("Could not create socket");
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1); //or die("Could not create socket");
        socket_bind($socket, $host, $port); // or die("Could not bind to socket");
        socket_listen($socket); // or die("Could not set up socket listener");
    }
    
    $initialSocketArray = array($socket);
    socket_select($initialSocketArray, $null, $null, 0, 10);
    // Вызываем socket_select и передаем этой функции все открытые сокеты. Эта функция вернет вам список тех сокетов, которые готовы к чтению (ну или записи). Готовы - в том смысле, что оттуда уже есть что читать (лежит в буфере ОС). Например, для сокета созданного socket_create наличие "чего-то читать" значит, что там есть новое соединение - дергаем socket_accept, что бы его принять. А для сокета, уже ранее созданного через socket_accept - это значит, что поступили данные.

    if (in_array($socket, $initialSocketArray)) {
        
        $socket_new = socket_accept($socket); //accpet new socket
        $clients[] = $socket_new; //add socket to client array

        $header = socket_read($socket_new, 1024); //read data sent by the socket
        perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake
        
        $newClient = true;
    }    
    
    
    $previousSettings = $settings;
    $settings = Shmb::getAll('b');
    
    $previousStats = $stats;
    $stats = Shmb::getAll('s');
    
    if (($previousStats != $stats) OR ($previousSettings != $settings) OR ($newClient == true)) {
        if (is_array($settings) AND is_array($stats)) {
            $toSend = array_merge($settings, $stats);
            $response = mask(json_encode($toSend)); //prepare data
            
            foreach ($clients as $client) {
                // Возвращает количество байт, успешно записанных в сокет или false в случае возникновения ошибки. 
                if (socket_write($client, $response, strlen($response)) == false) {           
                    closeSocket($client);
                    $found_socket = array_search($client, $clients);
                    unset($clients[$found_socket]);            
                }
            }
        }
    
        $newClient = false;
    }
    
    usleep(100000);
}