<?php

#every minute

require_once __DIR__ . '/config.php';

/** @var \MongoDB\Client $mc */
$mc = $container->get('mysqli_client');

/** @var \Service\GoogleSheetsService $googleSheetService */
$googleSheetService = $container->get('google_sheets_service');

/** @var \Service\WettportalService $wettportalService */
$wettportalService = $container->get('wettportal_service');
$finishedEvents    = [];
//`timestamp`<" . (time() - (120 * 60)) . " AND
$all               = $mc->query("SELECT * FROM `wettportalOdds` WHERE  `result` IS NULL AND `excelLineNumber` IS NOT NULL");

while ($row = $all->fetch_array()) {
    $finishedEvents[] = $row;
}
//$update = $mc->prepare("UPDATE `wettportalOdds` SET `result`=? WHERE  `betstudyId`=?");
$sql  = 'UPDATE `wettportalOdds` SET `result` = CASE `betstudyId`';
$sql1 = '';
foreach ($finishedEvents as $event) {

    echo PHP_EOL . $event['betstudyId'] . PHP_EOL;
    $_res   = $mc->query("SELECT `result` FROM `wettportalOdds` WHERE `excelLineNumber` = {$event['excelLineNumber']}")->fetch_assoc();
    var_dump($_res);
    $result = $wettportalService->getResult($event['wettportalLink']);

    if (!trim($result) || (trim($result) == 'vs.')) {
        continue;
    }
    echo $event['betstudyId'];
    $googleSheetService->postResultByLineNumber($event['excelLineNumber'], $result);
    $sql1 .= " WHEN '{$event['betstudyId']}' THEN '$result' ";
//    $update->bind_param('si', $result, $event['betstudyId']);
//    $update->execute();
}

if (!empty($sql1)) {
    $sql = $sql . $sql1 . ' ELSE `result` END';
    $mc->query($sql);
}
if ($excelLineNumber = $googleSheetService->getLineNumberByDateAndResult()) {
    $excelLineNumber = implode(', ', $excelLineNumber);
    $all             = $mc->query("SELECT `excelLineNumber`,`result` FROM `wettportalOdds` WHERE  `result` IS NOT NULL AND `excelLineNumber` IN ($excelLineNumber)");

    while ($row = $all->fetch_assoc()) {
        $googleSheetService->postResultByLineNumber($row['excelLineNumber'], $row['result']);
    }
}