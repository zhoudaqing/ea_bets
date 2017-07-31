<?php

namespace Service;

//require_once __DIR__ . '/bet365.php';

class MatchResolverService {

    const PERCENT_LIMIT = 64;

    private $predictionService;
    private $mysqli_client;

    public function __construct(PredictionService $predictionService, $mysqli_client) {
        $this->predictionService = $predictionService;
        $this->mysqli_client     = $mysqli_client;
    }

    public function resolve() {
        //$bet = new \bet365();

        $predictions = $this->predictionService->getNotResolvedPredictions();

        $update = $this->mysqli_client->prepare("UPDATE `betstudyPredictions` SET `wettportalLink`=?, `country`=?, `league`=? WHERE  `betstudyId`=?");
        foreach ($predictions as $prediction) {
            if (!$prediction['timestamp']) {
                continue;
            }
            $events = [];
            $time   = $prediction['timestamp'] - 60 * 60;
            $time2  = $prediction['timestamp'] - 60 * 60 * 2;
            $time3  = $prediction['timestamp'] - 60 * 60 * 3;
            $time4  = $prediction['timestamp'] - 60 * 60 * 4;
            $time5  = $prediction['timestamp'] - 60 * 60 * 13;
            $sql    = $this->mysqli_client->query("SELECT * FROM `wettportalEvents` "
                    . "WHERE `timestamp` = '" . ($prediction['timestamp']) . "' OR "
                    . "`timestamp` = '" . ($time2) . "' OR "
                    . "`timestamp` = '" . ($time3) . "' OR "
                    . "`timestamp` = '" . ($time4) . "' OR "
                    . "`timestamp` = '" . ($time5) . "'");
            while ($row    = $sql->fetch_assoc()) {
                $events[] = $row;
            }
            if (empty($events)) continue;

            $minLevenstein = 100;
            $requiredEvent = null;
            foreach ($events as $event) {
                $levenshtein = levenshtein($prediction['title'], $event['title']);
                echo '<br>' . $prediction['title'] . ' --- ' . $event['title'] . '<br>';

                if ($levenshtein < $minLevenstein) {
                    $minLevenstein = $levenshtein;
                    $requiredEvent = $event;
                }
            }

            $matches = explode(' ', $requiredEvent['l'], 2);
            similar_text($prediction['title'], $requiredEvent['title'], $percent);
            echo PHP_EOL . $prediction['title'] . ' | ' . $requiredEvent['title'] . ' = ' . $percent . '% - ' . $levenshtein;
            if ($percent > self::PERCENT_LIMIT) {
                echo ' +';
                $update->bind_param('sssi', $requiredEvent['link'], $matches[0], $matches[1], $prediction['betstudyId']);
                $update->execute();
                //return;
            }
//            else {
//                $match = explode(' - ', $prediction['title'],2);
//                $t1 = $bet->searchName($match[0]);
//            }
        }
    }

}
