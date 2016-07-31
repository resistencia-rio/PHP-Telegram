<?php
namespace PhpTelegram;
class Client extends \vitormattos\Telegram\Cli\Client{


    public function globalSearch($q, $local = '*')
    {
        return $this->exec('search '.$this->escapePeer($local).' ' . $this->escapeStringArgument($q));
    }
}
