<?php
namespace PhpTelegram;
class Client extends \Zyberspace\Telegram\Cli\Client{

    /**
     * Returns an array of all your dialogs. Every dialog is an object with type "user" or "chat".
     * If you need filter the dialog list, inform a type.
     *
     * @param string user|chat
     *
     * @return array|boolean An array with your dialogs; false if somethings goes wrong
     *
     * @uses exec()
     *
     * @see getUserInfo()
     */
    public function getDialogList($type = null) {
        $return = parent::getDialogList();
        if($type) {
            foreach($return as $key => $row) {
                if($row->type != $type) {
                    unset($return[$key]);
                }
            }
        }
        return $return;
    }

    /**
     * Generate a link for invite
     *
     * @param string $chat The chat you want generate link. Gets escaped with escapePeer().
     *
     * @return object|boolean An object with link; false otherwise
     *
     * @uses exec()
     * @uses escapePeer()
     */
    public function exportChatLink($chat)
    {
        return $this->exec('export_chat_link ' . $this->escapePeer($chat));
    }

    /**
     * Deletes a user from a chat
     *
     * @param string $chat The chat you want the user to delete from. Gets escaped with escapePeer().
     * @param string $user The user you want to delete. Gets escaped with escapePeer().
     *
     * @return boolean true on success, false otherwise
     *
     * @uses exec()
     * @uses escapePeer()
     */
    public function chatDeleteUser($chat, $user)
    {
        return $this->exec('chat_del_user', $this->escapePeer($chat), $this->escapePeer($user));
    }

    /**
     * Get our user info
     *
     * @return object|boolean An object with informations about the user; false if somethings goes wrong
     */
    public function getSelf()
    {
        return $this->exec('get_self');
    }

    public function globalSearch($q, $local = '*')
    {
        return $this->exec('search '.$this->escapePeer($local).' ' . $this->escapeStringArgument($q));
    }
}