<?php
namespace PhpTelegram;
class Client extends \Zyberspace\Telegram\Cli\Client{
    public function getDialogList($type = null) {
        $return = array();
        $list = parent::getDialogList();
        foreach($list as $row) {
            preg_match('/(?P<type>User|Chat)\ (?P<name>.*):/', $row, $matches);
            if($matches['type'] == 'Chat') {
                $return[] = $matches['name'];
            }
        }
        return $return;
    }

    public function chatAddUser($chat, $user) {
        return $this->exec('chat_add_user ' . $this->escapePeer($chat) . ' ' . $this->escapePeer($user));
    }

    public function chatDelUser($chat, $user) {
        $ok = $this->exec('chat_del_user ' . $this->escapePeer($chat) . ' ' . $this->escapePeer($user));
        return $ok;
    }

    public function chatInfo($chat) {
        $return = array();
        $chat = $this->exec('chat_info ' . $this->escapePeer($chat));
        $chat = \explode(PHP_EOL, $chat);
        foreach($chat as $key => $row) {
            if($key == 0) continue;
            preg_match('/\t\t(?P<name>.*)\ invited\ by\ (?P<by>.*)\ at \[(?P<at>.*)\](\ (?P<admin>admin))?/', $row, $matches);
            $return[] = array(
                'name' => $matches['name'],
                'by' => $matches['by'],
                'at' => $matches['at'],
                'admin' => \array_key_exists('admin', $matches)
            );
        }
        return $return;
    }

    /**
     * Delete a user from the contact list
     *
     * @param string $nameame The name of the new contact
     *
     * @return boolean true on success, false otherwise
     *
     * @uses exec()
     * @uses escapePeer()
     */
    public function delContact($name)
    {
        return $this->exec('del_contact ' . $this->escapePeer($name));
    }

    public function createGroupChat($chat, $users)
    {
        foreach($users as $key => $user) {
            $users[$key] =$this->escapePeer($user);
        }
        return $this->exec('create_group_chat ' . $this->escapePeer($chat) .
            ' '  . \implode(' ', $users));
    }

    public function renameChat($current, $new)
    {
        return $this->exec('rename_chat ' . $this->escapePeer($current). ' ' . $this->escapeStringArgument($new));
    }
}