<?php
#every minute
require_once __DIR__ . '/config.php';

/** @var \MongoDB\Client $mc */
$mc = $container->get('mongo_client');

/** @var  \Telegram\Bot\Api */
$telegram = $container->get('telegram');

/** @var \Service\GoogleSheetsService $googleSheetService */
$googleSheetService = $container->get('google_sheets_service');

$sheet = $googleSheetService->getSheet();

$emptyResultIds = [];
foreach ($sheet as $id => $row) {
    $isResultEmpty = !isset($row[RESULT_COLUMN_NUMBER]) ||
                    $row[RESULT_COLUMN_NUMBER] == '' ||
                    $row[RESULT_COLUMN_NUMBER] == 'vs.';

    if ($isResultEmpty) {
        $emptyResultIds[] = (int)$row[0];
    }
}

$emptyResultEvents = $mc->selectCollection(DB_NAME, 'wettportalOdds')
    ->find([
        'betstudyId' => ['$in' => $emptyResultIds],
        'timestamp' => ['$lt' => time() - (120*60)], //after ending match
        'excelLineNumber' => ['$ne' => null],
    ]);

$telegramUsers = $mc->selectCollection(DB_NAME, 'telegramUsers')->find();

foreach ($emptyResultEvents as $event) {
    if ($event['result']) {
        $googleSheetService->postResultByLineNumber($event['excelLineNumber'], $event['result']);

        //todo: extract to TelegramService
        /*foreach ($telegramUsers as $user) {
            $telegram->sendMessage([
                'chat_id' => $user['chatId'],
                'text' => sprintf(
                    "%s) %s %s \r\n %s \r\n %s",
                    $event['excelLineNumber'],
                    $event['country'],
                    $event['league'],
                    $event['title'],
                    $event['result']
                )
            ]);
        }*/
    }
}