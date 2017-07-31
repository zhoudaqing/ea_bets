<?php

namespace Service;

use Parser\WettportalEventListParser;
use Parser\WettportalResultParser;
use Parser\WettportalSearchParser;

class WettportalService {

    private $wettportalSearchParser;
    private $wettportalEventlistParser;
    private $wettportalResultParser;
    private $mysqli_client;

    public function __construct(WettportalSearchParser $wettportalSearchParser, WettportalEventListParser $wettportalEventlistParser, WettportalResultParser $wettportalResultParser, $mysqli_client) {
        $this->wettportalSearchParser    = $wettportalSearchParser;
        $this->wettportalEventlistParser = $wettportalEventlistParser;
        $this->wettportalResultParser    = $wettportalResultParser;
        $this->mysqli_client             = $mysqli_client;
    }

    public function getAllSoccerEvents() {
        $events = $this->wettportalEventlistParser->getAllSoccerEvents($this->mysqli_client);
//        $sql    = $this->mysqli_client->prepare("INSERT IGNORE INTO `wettportalEvents` (`link`, `startDate`, `l`, `title`, `timestamp`) VALUES (?, ?, ?, ?, ?)");
//        foreach ($events as $event) {
//            $event['timestamp'] = (new \DateTime($event['startDate']))->getTimestamp();
//            $sql->bind_param('ssssi', $event['link'], $event['startDate'], $event['l'], $event['title'], $event['timestamp']);
//            $sql->execute();
//        }

        return $events;
    }

    public function getResult($url) {
        $result = $this->wettportalResultParser->getResult($url);

        echo $result;

        $result = str_replace(' ', '', trim($result));

        return $result;
    }

    /**
     * For search by wettportal
     * @deprecated
     */
    public function searchEvent($eventTitle, $eventLink) {
        $teams    = explode('-', $eventTitle);
        $homeTeam = trim($teams[0]);
        $awayTeam = trim($teams[1]);

        $link = $this->wettportalSearchParser->parse($homeTeam);

        if (!$link) {
            return null;
        }

        $this->saveFoundLink($link, $eventLink);

        return $link;
    }

    /**
     * For search by wettportal
     * @deprecated
     */
    private function saveFoundLink(string $wettportalLink, string $betstudyLink): bool {
        try {
            $this->mongoClient
                    ->selectCollection(DB_NAME, 'betstudyPredictions')
                    ->updateOne(
                            ['link' => $betstudyLink], ['$set' => ['wettportalLink' => $wettportalLink]]
            );

            $this->mongoClient->selectCollection(DB_NAME, 'wettportalOdds')->insertOne(['link' => $wettportalLink]);
        } catch (BulkWriteException $exception) {
            echo 'Prediction with link "' . $wettportalLink . '" was skipped. Possible duplicate.' . PHP_EOL;
            return false;
        }

        return true;
    }

    private function saveEvent(array $event): bool {
        try {
            $this->mongoClient->selectCollection(DB_NAME, 'wettportalEvents')->insertOne($event);
        } catch (BulkWriteException $exception) {
            echo 'Prediction with link "' . $event['link'] . '" was skipped. Possible duplicate.' . PHP_EOL;
            return false;
        }

        return true;
    }

}
