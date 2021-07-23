<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Главная страница</title>
            <meta name='viewport' content='width=device-width, initial-scale=1'>
            <link rel='stylesheet' href='styles.css'>
        </head>
        <body>            
            <div class='container' id='mainControlForm'>
                <div class='header'>
                    <span>Raspberry Pi</span>
                    <hr>
                </div>
            
                <?php // включение основного содержимого в шаблон 
                    if (isset($content) == true) {
                        include ROOT_DIR.DS.'views'.DS.$content;            
                    }
                ?>
            </div>
            
            <?php // включение скрипта в шаблон
                if (isset($script) == true) {
                    echo('<script>');
                    include ROOT_DIR.DS.'scripts'.DS.$script;
                    echo('</script>');            
                }
            ?>
            
        </body>
        </html>