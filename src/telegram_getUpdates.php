<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/container.php';


/** @var  \Telegram\Bot\Api */
$telegram = $container->get('telegram');
$updates  = $telegram->getUpdates();
var_dump($updates);
if (count($updates) < 1) {
    exit;
}
require_once __DIR__ . '/bet365.php';
$bet   = new bet365();
$bet->auth();
$array = explode('|', $bet->getMyBets());
foreach ($array as $key => $value) {
    if (strpos($value, 'LoggedIn') !== false) {
        $balance = @trim(end(explode('LoggedIn;D2=', $value)), ';');
    }
    if (strpos($value, 'MyBets') !== false) {
        $mybets = ((int) end(explode('MyBets;PC=', $value))) / 4;
        break;
    }
}
$text = 'Баланс: ' . $balance . "\n" . 'Матчей: ' . $mybets;
foreach ($updates as $update) {
    $chat      = $update->getMessage()->getChat();
    $telegram->sendMessage([
        'chat_id' => $chat->get('id'),
        'text'    => $text,
    ]);
    $update_id = $update->get('update_id');
}
$telegram->getUpdates(['offset' => $update_id + 1]);

