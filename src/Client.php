<?php
namespace PhpTelegram;
class Client extends \Zyberspace\Telegram\Cli\Client{
    public function getContactList($type = null) {
        $list = parent::getDialogList();
        if(!$type) {
            return $list;
        } else {
            foreach($list as $row) {
                preg_match('/(?P<type>User|Chat)\ (?P<name>.*):/', $row, $matches);
                switch($matches['type']) {
                    case 'User':
                    case 'Chat':
                        ${$matches['type']}[] = $matches['name'];
                }
            }
            if(isset($$type)) {
                return $$type;
            }
        }
    }
}