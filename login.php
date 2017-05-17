<?php
require_once 'vendor/autoload.php';

if (file_exists('.env')) {
    $dotenv = new Dotenv\Dotenv(getcwd());
    $dotenv->load();
}
$settings = json_decode(getenv('MTPROTO_SETTINGS'), true) ?: [];

$MadelineProto = false;
try {
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    var_dump($e->getMessage());
}

if($MadelineProto === false) {
    $checkedPhone = $MadelineProto->auth->checkPhone([// auth.checkPhone becomes auth->checkPhone
        'phone_number'     => getenv('MTPROTO_NUMBER'),
    ]);
    
    \danog\MadelineProto\Logger::log([$checkedPhone], \danog\MadelineProto\Logger::NOTICE);
    $sentCode = $MadelineProto->phone_login(getenv('MTPROTO_NUMBER'));
    \danog\MadelineProto\Logger::log([$sentCode], \danog\MadelineProto\Logger::NOTICE);
    echo 'Enter the code you received: ';
    $code = fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1);
    $authorization = $MadelineProto->complete_phone_login($code);
    \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
    if ($authorization['_'] === 'account.noPassword') {
        throw new \danog\MadelineProto\Exception('2FA is enabled but no password is set!');
    }
    if ($authorization['_'] === 'account.password') {
        \danog\MadelineProto\Logger::log(['2FA is enabled'], \danog\MadelineProto\Logger::NOTICE);
        $authorization = $MadelineProto->complete_2fa_login(readline('Please enter your password (hint '.$authorization['hint'].'): '));
    }
    if ($authorization['_'] === 'account.needSignup') {
        \danog\MadelineProto\Logger::log(['Registering new user'], \danog\MadelineProto\Logger::NOTICE);
        $authorization = $MadelineProto->complete_signup(readline('Please enter your first name: '), readline('Please enter your last name (can be empty): '));
    }
    
    echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
}