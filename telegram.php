<?php
/**
 * @package TelegramClient
 */
/*
 Plugin Name: Telegram Client
 Plugin URI: http://resistenciarj.com.br/
 Description: Plugin for administrate telegram groups
 Version: 0.1
 Author: VitorMattos
 Author URI: http://github.com/vitormattos
 License: GPLv2 or later
 Text Domain: telegramclient
 */

return;
use PhpTelegram\Client;
require('vendor/autoload.php');
$telegram = new Client('tcp://localhost:2015');

?>Selecione um grupo para administrar:
<select><?php
$chats = $telegram->getContactList('Chat');
foreach($chats as $user) {
    echo '<option>'.$user.'</option>';
}
?></select><?php

//}

//$result = $telegram->exec('chat_info Tropa_de_Elite');
$break = '';