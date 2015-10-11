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

use PhpTelegram\Client;
require('vendor/autoload.php');
$telegram = new Client('tcp://localhost:2015');

$action = $_GET['action'];

?><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script>
jQuery(document).ready(function(){
	jQuery('#group').change(function(){
	    window.location = window.location.href.split("?")[0]+'?group='+$(this).val();
	});
});
</script>
<style>
select{
height: 250px;
}
</style>
</head>
<body>
<a href="?action=group-create">Criar grupo</a> |
<a href="?action=group-list">Listar grupos</a> |
<a href="?action=user-create">Adicionar contato</a> |
<a href="?action=user-list">Listar contatos</a> |
<a href="?action=global-search">Busca Global</a>
<br />
<?php
switch($action) {
    case 'del-contact':
        echo '<pre>';
        print_r($telegram->deleteContact('user#'.$_GET['id']));
        echo '</pre>';
        break;
    case 'user-list':
        $users = $telegram->getContactList();
        usort($users, function($a,$b){return $a->print_name>$b->print_name;});
        echo '<table border="1">';
        echo "<thead><tr><th>first name</th><th>last name</th><th>phone</th><th>username</th><th>edit</th><th>msg</th><th>del</th></tr></thead>";
        foreach($users as $user) {
            echo '<tr>';
            echo "<td>$user->first_name</td>";
            echo "<td>$user->last_name</td>";
            echo "<td>$user->phone</td>";
            echo "<td>$user->username</td>";
            echo '<td><a href="?action=edit-contact&id='.$user->id.'"">edit</a></td>';
            echo '<td><a href="?action=send-message&type='.$user->type.'&id='.$user->id.'">msg</a></td>';
            echo '<td><a href="?action=del-contact&id='.$user->id.'">del</a></td>';
            echo '</tr>';
        }
        echo '</table>';
        break;
    case 'user-create':
        if(count($_POST)) {
            echo '<pre>';
            print_r($telegram->addContact(
                $_POST['phoneNumber'],
                $_POST['firstName'],
                $_POST['lastName']
            ));
            echo '</pre>';
        }
        echo '<form method="post">';
        echo 'Nome: <input type="text" name="firstName"><br />';
        echo 'Sobenome: <input type="text" name="lastName"><br />';
        echo 'Telefone: <input type="text" name="phoneNumber"><br />';
        echo '<input type="submit"></form>';
        break;
    case 'edit-contact':
        if(count($_POST)) {
            $telegram->renameContact(
                'user#'.$_GET['id'],
                $_POST['firstName'],
                $_POST['lastName']
            );
        }
        echo '<form method="post">';
        echo '<pre>';
        print_r($user = $telegram->getUserInfo('user#'.$_GET['id']));
        echo '</pre>';
        echo 'first name: <input type="text" name="firstName" value="'.$user->first_name.'"><br />';
        echo 'last name: <input type="text" name="lastName" value="'.$user->last_name.'"><br />';
        echo '<input type="submit"></form>';
        break;
    case 'group-create':
        if(count($_POST)) {
            $telegram->createGroupChat($_POST['chat'], $_POST['users']);
        }
        echo '<form method="post">';
        echo 'Nome: <input type="text" name="chat"><br />';
        ?>Usuários: <select id="users" name="users[]" multiple><?php
        $users = $telegram->getContactList();
        usort($users, function($a,$b){return $a->print_name>$b->print_name;});
        foreach($users as $user) {
            echo '<option value="user#'.$user->id.'">'.$user->first_name.$user->last_name.'</option>';
        }
        ?></select><?php
        echo '<input type="submit"></form>';
        break;
    case 'group-rename':
        if(isset($_POST['new'])) {
            $telegram->renameChat('chat#'.$_GET['id'], $_POST['new']);
        }
        echo '<form method="post">';
        echo 'Nome: <input type="text" name="new"><br />';
        ?><input type="submit">
        </form><?php
        break;
    case 'group-user-add':
        if(count($_POST)) {
            $telegram->chatAddUser(base64_decode($_GET['group']), base64_decode($_POST['user']));
        }
        echo '<form method="post">';
        ?>Usuários: <select id="user" name="user" multiple><?php
         $users = $telegram->getContactList();
         usort($users, function($a,$b){return $a->print_name>$b->print_name;});
         foreach($users as $user) {
             echo '<option value="'.$user->id.'">'.$user->first_name.' '.$user->last_name.'</option>';
         }
        ?></select><?php
        echo '<input type="submit"></form>';
        $chat = $telegram->chatInfo('chat#'.$_GET['id']);
        echo '<strong>Grupo:</strong> '.$chat->title.'<br />';
        echo '<strong>Admin:</strong> <pre>'.print_r($chat->admin, true).'</pre><br />';
        echo '<strong>Membros:</strong> '.$chat->members_num.'<br />';
        echo '<table border="1">';
        echo "<thead><tr><th>first name</th><th>last name</th><th>phone</th><th>username</th><th>invited by</th></thead>";
        usort($chat->members, function($a,$b){return $a->print_name>$b->print_name;});
        foreach($chat->members as $key => $user) {
            if(!is_numeric($key)) continue;
            echo '<tr>';
            echo "<td>$user->first_name</td>";
            echo "<td>$user->last_name</td>";
            echo "<td>$user->phone</td>";
            echo "<td>$user->username</td>";
            echo "<td>{$user->inviter->first_name} {$user->inviter->last_name}</td>";
            echo '</tr>';
        }
        break;
    case 'group-create-meeting':
        $originalChat = $telegram->chatInfo('chat#'.$_GET['id']);
        if(count($_POST)) {
            $users_for_add = $_POST['users'];
            // add members new group
            foreach($originalChat->members as $key => $user) {
                $users_for_add[] = 'user#'.$user->id;
            }
            $users_for_add = array_unique($users_for_add);
            $telegram->createGroupChat($_POST['chat'], $users_for_add);
            // remove members old group
            foreach($telegram->chatInfo('chat#'.$_POST['old'])->members as $user) {
                $telegram->chatDeleteUser('chat#'.$_POST['old'], 'user#'.$user->id);
            }
            echo 'Sala "'.$_POST['chat'].'" criada';
            return;
        }
        echo '<form method="post">';
        echo 'Nome: <input type="text" name="chat" value="'.'Sala de reunião - '.$originalChat->title.'"><br />';

        $chats = $telegram->getDialogList('chat');
        usort($chats, function($a,$b){return $a->title>$b->title;});
        echo 'Sala anterior: (Todos serão removidos da sala anterior)<br />';
        foreach($chats as $chat) {
            echo ' <input type="radio" name="old" value="'.$chat->id.'">'.$chat->title.'<br /> ';
        }

        ?>Usuários convidados: <select id="users" name="users[]" multiple><?php
        $users = $telegram->getContactList();
        asort($users);
        foreach($users as $user) {
            echo '<option value="user#'.$user->id.'">'.$user->first_name.' '.$user->last_name.'</option>';
        }
        ?></select><br /><?php
        echo '<input type="submit"></form>';
        break;
    case 'group-get-link':
        echo 'Para invalidar este link, gere um novo link. <br />';
        echo $telegram->exportChatLink('chat#'.$_GET['id'])->result;
        break;
    case 'group-user-remove':
        if(isset($_GET['user'])) {
            $telegram->chatDeleteUser('chat#'.$_GET['id'], 'user#'.$_GET['user']);
            if($telegram->getErrorMessage()) {
                echo $telegram->getErrorMessage();
            }
        }
        $chat = $telegram->chatInfo('chat#'.$_GET['id']);
        $me = $telegram->getSelf();
        usort($chat->members, function($a,$b){return $a->print_name>$b->print_name;});
        echo '<table border="1">';
        echo '<thead><tr><th>first name</th><th>last name</th><th>phone</th><th>username</th><th>invited by</th><th>command</th></tr></thead>';
        foreach($chat->members as $key => $user) {
            if(!is_numeric($key)) continue;
            echo '<tr>';
            echo "<td>$user->first_name</td>";
            echo "<td>$user->last_name</td>";
            echo "<td>$user->phone</td>";
            echo "<td>$user->username</td>";
            echo "<td>{$user->inviter->first_name} {$user->inviter->last_name}</td>";
            echo '<td>';
                if($chat->inviter->id == $me->id || $chat->admin->id == $me->id || $user->id == $me->id) {
                    echo '<a href="?action=group-user-remove&id='.$_GET['id'].
                            '&user='.$user->id.'">remove</a>';
                }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        break;
    case 'group-list':
        $chats = $telegram->getDialogList('chat');
        usort($chats, function($a,$b){return $a->title>$b->title;});
        echo '<table border="1">';
        echo '<thead><tr><th>id</th><th>title</th><th>members</th><th colspan="6">commands</th></tr></thead>';
        foreach($chats as $chat) {
            echo '<tr>';
            echo "<td>$chat->id</td>";
            echo "<td>$chat->title</td>";
            echo "<td>$chat->members_num</td>";
            echo '<td><a href="?action=group-user-add&id='.$chat->id.'">add user</a></td>';
            echo '<td><a href="?action=group-user-remove&id='.$chat->id.'">remove user</a></td>';
            echo '<td><a href="?action=group-get-link&id='.$chat->id.'">get link</a></td>';
            echo '<td><a href="?action=send-message&type='.$chat->type.'&id='.$chat->id.'">send message</a></td>';
            echo '<td><a href="?action=group-rename&id='.$chat->id.'">rename group</a></td>';
            echo '<td><a href="?action=group-create-meeting&id='.$chat->id.'">create meeting</a></td>';
            echo '</tr>';
        }
        echo '</table>';
        break;
    case 'send-message':
        if(count($_POST)) {
            $telegram->msg($_GET['type'].'#'.$_GET['id'], $_POST['msg']);
        }
        echo '<form method="post">';
        echo 'Destino: <pre>';
        if($_GET['type'] == 'user') {
            print_r($telegram->getUserInfo('user#'.$_GET['id']));
        } else {
            print_r($telegram->chatInfo('chat#'.$_GET['id'])->title);
        }
        echo '</pre>';
        echo 'Mensagem: <textarea name="msg"></textarea>';
        ?><br /><?php
        echo '<input type="submit"></form>';
        break;
    case 'global-search':
        if(count($_POST)) {
            echo '<pre>';
            print_r($telegram->globalSearch($_POST['q']));
            echo '</pre>';
        }
        echo '<form method="post">';
        echo 'Termo: <input type="text" name="q"><br />';
        echo '<input type="submit"></form>';
        break;
    default:
        break;
}

//}

//$result = $telegram->exec('chat_info Tropa_de_Elite');
$break = '';