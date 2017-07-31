<?php

#20min+4

require_once __DIR__ . '/config.php';

/** @var \MongoDB\Client $mc */
$mc = $container->get('mysqli_client');

/** @var \Service\GoogleSheetsService $googleSheetService */
$googleSheetService = $container->get('google_sheets_service');

$oddsToExcel = [];
$all         = $mc->query("SELECT * FROM `wettportalOdds` WHERE `excelLineNumber` IS NULL");

while ($row = $all->fetch_assoc()) {
    $row['odds']   = json_decode($row['odds']);
    $oddsToExcel[] = $row;
}
$update = $mc->prepare("UPDATE `wettportalOdds` SET `excelLineNumber`=? WHERE  `betstudyId`=?");
foreach ($oddsToExcel as $odd) {
    $excelLineNumber = $googleSheetService->addEventToSheet($odd);
    var_dump($excelLineNumber);
    if ($excelLineNumber) {
        $update->bind_param('ii', $excelLineNumber, $odd['betstudyId']);
        $update->execute();
    }
}