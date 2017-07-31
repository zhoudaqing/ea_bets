<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/container.php';

require_once __DIR__ . '/bet365.php';
require_once __DIR__ . '/CookiejarEdit.php';

$bet    = new bet365();
$cookie = new CookiejarEdit(__DIR__ . '/cookie.txt', 'mobile.bet365.com');

/** @var \MongoDB\Client $mc */
$mc = $container->get('mysqli_client');

/** @var  \Telegram\Bot\Api */
$telegram           = $container->get('telegram');
$telegramUsers      = [['chatId' => '370196177'], ['chatId' => '-201868565']]; //$mc->selectCollection(DB_NAME, 'telegramUsers')->find();
/** @var \Service\GoogleSheetsService $googleSheetService */
$googleSheetService = $container->get('google_sheets_service');

$lastNotifiedEvent = $mc->query("SELECT `excelLineNumber` FROM `wettportalOdds` WHERE `telegramNotified`=1 AND `excelLineNumber` IS NOT NULL ORDER BY `excelLineNumber` DESC LIMIT 1")->fetch_row();
if (!is_array($lastNotifiedEvent)) {
    $lastNotifiedEvent   = [];
    $lastNotifiedEvent[] = 0;
}
$sheet = $googleSheetService->getSheet();

$notNotifiedEvents = array_slice($sheet, $lastNotifiedEvent[0], NULL, true);

$needNotifyIds = [];
foreach ($notNotifiedEvents as $id => $row) {
    $betPlaced = isset($row[BET_PLACED_COLUMN_NUMBER]) && ($row[BET_PLACED_COLUMN_NUMBER] === 'YES');

    if ($betPlaced) {
        $needNotifyIds[]      = (int) $row[0];
        $price[(int) $row[0]] = ((float) $row[BET_SUM]) > 0 ? round((float) $row[BET_SUM] / 4, 2) : 2.5;
    }
}
if (!$needNotifyIds) {
    exit;
}
$needNotifyEvents = [];

$sql = $mc->query("SELECT * FROM `wettportalOdds` WHERE `betstudyId` IN ('" . implode("','", $needNotifyIds) . "') AND `telegramNotified`=0 AND `result` IS NULL AND `timestamp`>'" . time() . "'");
while ($row = $sql->fetch_assoc()) {
    $needNotifyEvents[] = $row;
}
var_dump($needNotifyEvents);

$message = [];
foreach ($needNotifyEvents as $key => $event) {
    $mc->query("UPDATE `wettportalOdds` SET `telegramNotified`=1 WHERE `betstudyId`='" . $event['betstudyId'] . "'");
    $cookie->clearAllCookie();
	$bet->auth();
    $balance = $bet->get_balance();
// Очистим куку ставок, на всякий случай.
    echo $event['betstudyId'] . PHP_EOL;
    $bet->get_match(['code' => $event['wettportalLink']]);
    foreach ($bet->score_bet as $key => $value) {
        //var_dump($key, $value['bet_slip']);
        $googleSheetService->updateCoefficientByLineNumber($event['excelLineNumber'], $key, $value['bet_slip']);
    }
    if ($balance >= $price[$event['betstudyId']] * 4) {
        // тут надо ставить ставки.
        if ($add_bet = $bet->add_bet()) {
            //var_dump($add_bet);
            if ($add_price = $bet->add_price($price[$event['betstudyId']])) {
                //var_dump($add_price);
                $mess = 'Ставки сделаны!';
            }
            else {
                //var_dump($add_price);
                $mess = 'Ошибка, ставки не сделаны.'; // . PHP_EOL . serialize($bet->good_team) . PHP_EOL . serialize($bet->score_bet));
            }
        }
        else {
            //var_dump($add_bet);
            $mess = 'Ошибка, ставки не добавлены.'; // . PHP_EOL . serialize($bet->good_team) . PHP_EOL . serialize($bet->score_bet));
        }
    }
    else {
        $mess = 'Недостаточно денег на балансе!';
    }
    //exit;
    #\xF0\x9F\x9A\x80 - its utf8 bytes from this table http://apps.timwhitlock.info/emoji/tables/unicode
    $message[] = sprintf(
            "Ставка \xF0\x9F\x9A\x80 \xF0\x9F\x9A\x80 \xF0\x9F\x9A\x80 \r\n %s) %s %s \r\n %s \r\n %s \r\n %s \r\n На балансе - %s", $event['excelLineNumber'], $event['country'], $event['league'], $event['title'], $notNotifiedEvents[$event['excelLineNumber']][BET_SUM], $mess, $bet->get_balance()
    );
}
var_dump($message);
foreach ($telegramUsers as $user) {
    $telegram->sendMessage([
        'chat_id' => $user['chatId'],
        'text'    => implode("\r\n", $message)
    ]);
}
//
//$mc->selectCollection(DB_NAME, 'wettportalOdds')
//        ->updateMany(
//                ['betstudyId' => ['$in' => $needNotifyIds]], ['$set' => ['telegramNotified' => true]]
//);
