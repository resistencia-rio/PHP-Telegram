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
        $telegram->delContact(base64_decode($_GET['user']));
        break;
    case 'user-list':
        $users = $telegram->getContactList();
        asort($users);
        foreach($users as $name) {
            echo $name.
                ' <a href="?action=edit-contact&contact='.base64_encode($name).'">edit</a>'.
                ' <a href="?action=send-message&peer='.trim(base64_encode($name),'=').'">send message</a>'.
                ' <a href="?action=del-contact&user='.base64_encode($name).'">del</a><br />';
        }
        break;
    case 'user-create':
        if(count($_POST)) {
            $telegram->addContact(
                $_POST['phoneNumber'],
                $_POST['firstName'],
                $_POST['lastName']
            );
        }
        echo '<form method="post">';
        echo 'Nome: <input type="text" name="firstName"><br />';
        echo 'Sobenome: <input type="text" name="lastName"><br />';
        echo 'Telefone: <input type="text" name="phoneNumber"><br />';
        ?><input type="submit">
        </form><?php
        break;
    case 'edit-contact':
        if(count($_POST)) {
            $telegram->renameContact(
                base64_decode($_POST['contact']),
                $_POST['firstName'],
                $_POST['lastName']
            );
        }
        echo '<form method="post">';
        echo '<input type="hidden" name="contact" value="'.$_GET['contact'].'"><br />';
        $contact = base64_decode($_GET['contact']);
        echo '<pre>';
        print_r($telegram->getUserInfo($contact));
        echo '</pre>';
        $contact = array(
            substr($contact, 0, strpos($contact, ' ')),
            substr($contact, strpos($contact, ' '))
        );
        echo 'Nome: <input type="text" name="firstName" value="'.$contact[0].'"><br />';
        echo 'Sobenome: <input type="text" name="lastName" value="'.$contact[1].'"><br />';
        ?><input type="submit">
        </form><?php
        break;
    case 'group-create':
        if(count($_POST)) {
            $telegram->createGroupChat($_POST['chat'], $_POST['users']);
        }
        echo '<form method="post">';
        echo 'Nome: <input type="text" name="chat"><br />';
        ?>Usuários: <select id="users" name="users[]" multiple><?php
        $users = $telegram->getContactList('User');
        asort($users);
        foreach($users as $name) {
            echo '<option value="'.$name.'">'.$name.'</option>';
        }
        ?></select>
        <input type="submit">
        </form><?php
        break;
    case 'group-rename':
        if(isset($_POST['new'])) {
            $telegram->renameChat($_POST['current'], $_POST['new']);
        }
        echo '<form method="post">';
        echo '<input type="hidden" name="current" value="'.base64_decode($_GET['group']).'"><br />';
        echo 'Nome: <input type="text" name="new"><br />';
        ?><input type="submit">
        </form><?php
        break;
    case 'group-user-add':
        if(count($_POST)) {
            $telegram->chatAddUser(base64_decode($_GET['group']), base64_decode($_POST['user']));
        }
        echo '<strong>Grupo:</strong> '.base64_decode($_GET['group']).'<br />';
        echo '<form method="post">';
        ?>Usuários: <select id="user" name="user" multiple><?php
        $users = $telegram->getContactList();
        asort($users);
        foreach($users as $name) {
            echo '<option value="'.base64_encode($name).'">'.$name.'</option>';
        }
        ?></select>
        <br /><input type="submit">
        </form><?php
        $users = $telegram->chatInfo(base64_decode($_GET['group']));
        foreach($users as $key => $name) {
            $sort[$key] = $name['name'];
        }
        asort($sort);
        foreach($sort as $key => $name) {
            echo $users[$key]['name'].' invited by '.$users[$key]['by'].' at '.$users[$key]['at'].' '.($users[$key]['admin']?' ADMIN':'').'<br />';
        }
        break;
    case 'group-create-meeting':
        if(count($_POST)) {
            $users_for_add = $_POST['users'];
            // add members new group
            foreach($telegram->chatInfo(base64_decode($_GET['group'])) as $key => $name) {
                $users_for_add[] = $name['name'];
            }
            $telegram->createGroupChat($_POST['chat'], $users_for_add);
            // remove members old group
            foreach($telegram->chatInfo($_GET['old']) as $key => $name) {
                $telegram->chatDelUser($_GET['old'], $name);
            }
            echo 'Sala "'.$_POST['chat'].'" criada';
            return;
        }
        echo '<form method="post">';
        echo 'Nome: <input type="text" name="chat" value="'.'Sala de reunião - '.base64_decode($_GET['group']).'"><br />';

        $chats = $telegram->getDialogList();
        asort($chats);
        echo 'Sala anterior: (Todos serão removidos da sala anterior)<br />';
        foreach($chats as $name) {
            echo ' <input type="radio" name="old" value='.$name.'">'.$name.'<br /> ';
        }

        ?>Usuários convidados: <select id="users" name="users[]" multiple><?php
        $users = $telegram->getContactList('User');
        asort($users);
        foreach($users as $name) {
            echo '<option value="'.$name.'">'.$name.'</option>';
        }
        ?></select>
        <br /><input type="submit">
        </form><?php
        break;
    case 'group-get-link':
        echo 'Para invalidar este link, gere um novo link. <br />';
        echo $telegram->exportChatLink(base64_decode($_GET['group']));
        break;
    case 'group-user-remove':
        if(isset($_GET['user'])) {
            $telegram->chatDelUser(base64_decode($_GET['group']), base64_decode($_GET['user']));
        }
        echo '<strong>Grupo:</strong> '.base64_decode($_GET['group']).'<br />';
        echo '<form method="post">';
        ?>Usuários:<br /><?php
        $users = $telegram->getContactList('User');
        $users = $telegram->chatInfo(base64_decode($_GET['group']));
        foreach($users as $key => $name) {
            $sort[$key] = $name['name'];
        }
        asort($sort);
        foreach($sort as $key => $name) {
            echo $users[$key]['name'].' invited by '.$users[$key]['by'].' at '.$users[$key]['at'].' '.
                ($users[$key]['admin']?
                    :'<a href="?action=group-user-remove&group='.$_GET['group'].
                        '&user='.base64_encode($users[$key]['name']).'">remove</a>'
                ).'<br />';
        }
        ?><br /><input type="submit">
        </form><?php
        break;
    case 'group-list':
        $chats = $telegram->getDialogList();
        asort($chats);
        foreach($chats as $name) {
            echo $name;
            echo ' <a href="?action=group-user-add&group='.trim(base64_encode($name),'=').'">add user</a> | ';
            echo '<a href="?action=group-user-remove&group='.trim(base64_encode($name),'=').'">remove user</a> | ';
            echo '<a href="?action=group-get-link&group='.trim(base64_encode($name),'=').'">get link</a> | ';
            echo '<a href="?action=send-message&peer='.trim(base64_encode($name),'=').'">send message</a> | ';
            echo '<a href="?action=group-rename&group='.trim(base64_encode($name),'=').'">rename group</a> | ';
            echo '<a href="?action=group-create-meeting&group='.trim(base64_encode($name),'=').'">create meeting</a><br />';
        }
        break;
    case 'send-message':
        if(count($_POST)) {
            $telegram->msg(base64_decode($_GET['peer']), $_POST['msg']);
        }
        echo '<form method="post">';
        echo 'Destino: '.base64_decode($_GET['peer']).'<br />';
        echo 'Mensagem: <textarea name="msg"></textarea>';
        ?><br /><input type="submit">
        </form><?php
    case 'global-search':
        if(count($_POST)) {
            echo '<pre>';
            print_r($telegram->globalSearch($_POST['q']));
            echo '</pre>';
        }
        echo '<form method="post">';
        echo 'Termo: <input type="text" name="q"><br />';
        ?><input type="submit">
        </form><?php
        break;
    default:
        break;
}

//}

//$result = $telegram->exec('chat_info Tropa_de_Elite');
$break = '';