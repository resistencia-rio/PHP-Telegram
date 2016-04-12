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
require 'config.php';
try {
    if($config['telegram.cli']['tcp-port']) {
        $remoteSocket = 'tcp://localhost:'.$config['telegram.cli']['tcp-port'];
    } elseif($config['telegram.cli']['udp-socket']) {
        $remoteSocket = 'unix://'.$config['telegram.cli']['udp-socket'];
    } else {
        throw new Exception('Inform the type of connection (tcp || socket)');
    }
    $telegram = new Client($remoteSocket);
} catch(Exception $e) {
    echo $e->getMessage();
    die();
}

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
        usort($users, function($a,$b){return $a->print_name > $b->print_name;});
        echo '<table border="1">';
        echo "<thead><tr><th>first name</th><th>last name</th><th>phone</th><th>username</th><th>edit</th><th>msg</th><th>del</th><th>rm group</th></tr></thead>";
        foreach($users as $user) {
            echo '<tr>';
            echo "<td>$user->first_name</td>";
            echo "<td>$user->last_name</td>";
            echo "<td>$user->phone</td>";
            echo "<td>$user->username</td>";
            echo '<td><a href="?action=edit-contact&id='.$user->peer_id.'"">edit</a></td>';
            echo '<td><a href="?action=send-message&type='.$user->type.'&id='.$user->peer_id.'">msg</a></td>';
            echo '<td><a href="?action=del-contact&id='.$user->peer_id.'">del</a></td>';
            echo '<td><a href="?action=remove-from-groups&id='.$user->peer_id.'">rm group</a></td>';
            echo '</tr>';
        }
        echo '</table>';
        break;
    case 'remove-from-groups':
        $chats = $telegram->getDialogList('chat');
        $filtered = array_filter($chats, function($v, $k) use ($telegram) {
            $chat = $telegram->chatInfo('chat#'.$v->peer_id);
            $filtered = array_filter($chat->members, function($v, $k) {
                return $v->peer_id == $_GET['id'];
            }, ARRAY_FILTER_USE_BOTH);
            return $filtered ? true : false;
        }, ARRAY_FILTER_USE_BOTH);
        usort($filtered, function($a,$b){return $a->title > $b->title;});
        foreach($filtered as $chat) {
            echo $chat->title . ' <a href="?action=group-user-remove' .
                '&id='.$chat->peer_id.
                '&user='.$_GET['id'].'">remove</a><br />';
        }
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
            echo '<option value="user#'.$user->peer_id.'">'.$user->first_name.$user->last_name.'</option>';
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
            if($_POST['usernames']) {
                $usernames = explode(' ', $_POST['usernames']);
                foreach($usernames as $username) {
                    $username = trim($username, '@');
                    $user = $telegram->exec('resolve_username '.$telegram->escapePeer($username));
                    if($user) {
                        $_POST['user'][] = 'user#'.$user->peer_id;
                    }
                }
            }
            foreach($_POST['user'] as $user) {
                if(!$telegram->chatAddUser('chat#'.$_GET['id'], $user, $forwardmessages)) {
                    var_dump(array(
                        'user' => $user,
                        'message' =>$telegram->getErrorMessage(),
                        'code'=>$telegram->getErrorCode()
                    ));
                    break;
                }
            }
        }
        echo '<form method="post">';
        ?>Usuários: <select id="user" name="user[]" multiple><?php
         $users = $telegram->getContactList();
         usort($users, function($a,$b){return $a->print_name>$b->print_name;});
         foreach($users as $user) {
             echo '<option value="user#'.$user->peer_id.'">'.$user->first_name.' '.$user->last_name.'</option>';
         }
        ?></select><br /><?php
        echo 'Usernames: <input type="text" name="usernames" /><br />';
        echo 'Forward messages: <input type="text" name="forwardmessages" value="100" />';
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
                $users_for_add[] = 'user#'.$user->peer_id;
            }
            $users_for_add = array_unique($users_for_add);
            $telegram->createGroupChat($_POST['chat'], $users_for_add);
            // remove members old group
            foreach($telegram->chatInfo('chat#'.$_POST['old'])->members as $user) {
                $telegram->chatDeleteUser('chat#'.$_POST['old'], 'user#'.$user->peer_id);
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
            echo ' <input type="radio" name="old" value="'.$chat->peer_id.'">'.$chat->title.'<br /> ';
        }

        ?>Usuários convidados: <select id="users" name="users[]" multiple><?php
        $users = $telegram->getContactList();
        asort($users);
        foreach($users as $user) {
            echo '<option value="user#'.$user->peer_id.'">'.$user->first_name.' '.$user->last_name.'</option>';
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
                if($chat->inviter->peer_id == $me->peer_id || $chat->admin->peer_id == $me->peer_id || $user->peer_id == $me->peer_id) {
                    echo '<a href="?action=group-user-remove&id='.$_GET['id'].
                            '&user='.$user->peer_id.'">remove</a>';
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
            echo "<td>$chat->peer_id</td>";
            echo "<td>$chat->title</td>";
            echo "<td>$chat->members_num</td>";
            echo '<td><a href="?action=group-user-add&id='.$chat->peer_id.'">add user</a></td>';
            echo '<td><a href="?action=group-user-remove&id='.$chat->peer_id.'">remove user</a></td>';
            echo '<td><a href="?action=group-get-link&id='.$chat->peer_id.'">get link</a></td>';
            echo '<td><a href="?action=send-message&type='.$chat->type.'&id='.$chat->peer_id.'">send message</a></td>';
            echo '<td><a href="?action=group-rename&id='.$chat->peer_id.'">rename group</a></td>';
            echo '<td><a href="?action=group-create-meeting&id='.$chat->peer_id.'">create meeting</a></td>';
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
