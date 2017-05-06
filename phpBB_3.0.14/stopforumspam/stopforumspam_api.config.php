<?php
/*
    Project: StopForumSpam.SUBNETS.RU
    PHP client configuration file

    (c) 2015 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>
    
    Encoding for the comments is UTF-8.
*/
//////////////////////////////// CONFIG ///////////////////////////////////////////////////////////////
define( 'StopForumSpam_API_DEBUG','0' );	//0 - отключить debug режим
						//1 - включить debug режим и записывать в лог

define( 'StopForumSpam_API_DEBUG_CURL', '0');	//0 - отключить debug режим
						//1 - включить debug режим + записывать в лог

define( 'StopForumSpam_API_LOGFILEDIR','stopforumspam/logs');	//Путь до папки, в которую будут сохраняться логи при включенном debug режиме
								//путь относительно корня форума
								//не забудьте создать директорию и выставить на нее права записи для пользователя от которого запущена ваша HTTP служба
								//например пользователь www: chown -R www:www stopforumspam/logs

define( 'StopForumSpam_API_METHOD', 'XML');		//Варианты: 
							// XML
							// JSON

define( 'StopForumSpam_API_UID', '0' );			//Ваш API ID
define( 'StopForumSpam_API_PASSWORD', 'password' );	//Ваш API пароль
define( 'StopForumSpam_API_TIMEOUT', '60' );		//Таймаут в секундах при обращении к API

define( 'StopForumSpam_HIT_COUNTER','2');		//Кол-во совпадений для отправки на 404 страницу. Значение по умолчанию 2
							//Возможные значения:
							    //1 - одно совпадение по базе (любое одно из: username, email, ip)
							    //2 - два совпадения по базе (любые два из: username, email, ip)
							    //3 - три совпадения по базе (все три: username, email, ip)

define( 'StopForumSpam_EMAIL_CATEGORICAL','1');        // Если email найден в базе
							// Возможные значения:
							    // 0 - игнорирование (применяются другие методы блокировки)
							    // 1 - блокировать, не обращая внимания на другие опции
?>