<?php
#every 10 minute

require_once __DIR__ . '/config.php';

/** @var \MongoDB\Client $mc */
$mc = $container->get('mongo_client');

/** @var \Service\GoogleSheetsService $googleSheetService */
$googleSheetService = $container->get('google_sheets_service');

/** @var \Service\WettportalService $wettportalService */
$wettportalService = $container->get('wettportal_service');

/** @var \Service\OddsService $oddsService */
$oddsService = $container->get('odds_service');

/** @var \Parser\WettportalOddsParser $wettportalOddsParser */
$wettportalOddsParser = $container->get('wettportal_odds_parser');

$postedToExcelNotStartedEvents = $mc->selectCollection(DB_NAME, 'wettportalOdds')
    ->find([
        'excelLineNumber' => ['$gt' => 1],
        'under' => ['$gte' => 60],
        'betstudyId' => ['$exists' => 1],
        'timestamp' => [
            '$gte' => time(),
            '$lte' => (time() + 10*60)
        ] //10 minutes before match start
    ]);

foreach ($postedToExcelNotStartedEvents as $event) {
    $bet365odds = $oddsService->getBet365Odds($event);

    $newOdds = $wettportalOddsParser->fetchOdds($event['wettportalLink'], 'correctScore');

    $needUpdate = false;
    foreach (['2-1', '2-0', '1-0', '0-0', '1-1', '0-1', '0-2'] as $score) {
        $oddsForScore = $oddsService->getOddsForScore($bet365odds->odds->correctScore, $score);

        $newOddsForScore = $oddsService->getNewBet365OddsForScore($newOdds, $score);

        if ($newOddsForScore && $newOddsForScore != $oddsForScore) {
            $googleSheetService->updateCoefficientByLineNumber($event['excelLineNumber'], $score, $newOddsForScore);
            $needUpdate = true;
        }
    }

    if ($needUpdate) {
        $mc->selectCollection(DB_NAME, 'wettportalOdds')
            ->updateOne(
                ['betstudyId' => $event['betstudyId']],
                ['$set' => ['odds.correctScore' => $newOdds]]
            );
    }
}