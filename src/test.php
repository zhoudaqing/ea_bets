<?php

#every minute

require_once __DIR__ . '/config.php';

/** @var \MongoDB\Client $mc */
$mc = $container->get('mysqli_client');

/** @var \Service\GoogleSheetsService $googleSheetService */
$googleSheetService = $container->get('google_sheets_service');

if ($excelLineNumber = $googleSheetService->getLineNumberByDateAndResult()) {
    $excelLineNumber = implode(', ', $excelLineNumber);
    $all             = $mc->query("SELECT `excelLineNumber`,`result` FROM `wettportalOdds` WHERE  `result` IS NOT NULL AND `excelLineNumber` IN ($excelLineNumber)");

    while ($row = $all->fetch_assoc()) {
        $googleSheetService->postResultByLineNumber($row['excelLineNumber'], $row['result']);
        $finishedEvents[] = $row;
    }
    var_dump($finishedEvents);
}
