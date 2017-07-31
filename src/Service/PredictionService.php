<?php

namespace Service;

use Parser\BetstudyPredictionsParser;

class PredictionService {

    private $betStudyPredictionsParser;
    private $mysqli_client;

    public function __construct(BetstudyPredictionsParser $betstudyPredictionsParser, $mysqli_client) {
        $this->betStudyPredictionsParser = $betstudyPredictionsParser;
        $this->mysqli_client             = $mysqli_client;
    }

    public function getAndSavePredictions() {
        $predictions = $this->betStudyPredictionsParser->parse();

        $add = $this->mysqli_client->prepare("INSERT IGNORE INTO `betstudyPredictions` (`betstudyId`, `timestamp`, `1`, `2`, `X`, `over`, `under`, `prediction`, `wettportalLink`, `country`, `league`, `link`, `title`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, ?, ?)");
        foreach ($predictions as $prediction) {
            if (empty($prediction)) {
                continue;
            }
            preg_match('/\/prediction\/(\d*)\//', $prediction['link'], $matches);
            $prediction['betstudyId'] = (int) $matches[1];
            if ((int) trim($prediction['under'], '%') < 60 || $prediction['betstudyId'] === 0) {
                continue;
            }
            $prediction['timestamp'] = (int) $prediction['timestamp'];
            $prediction['1']         = (int) trim($prediction['1'], '%');
            $prediction['2']         = (int) trim($prediction['2'], '%');
            $prediction['X']         = (int) trim($prediction['X'], '%');
            $prediction['over']      = (int) trim($prediction['over'], '%');
            $prediction['under']     = (int) trim($prediction['under'], '%');

            $add->bind_param('iiiiiiisss', $prediction['betstudyId'], $prediction['timestamp'], $prediction['1'], $prediction['2'], $prediction['X'], $prediction['over'], $prediction['under'], $prediction['prediction'], $prediction['link'], $prediction['title']);
            $add->execute();
        }

        return $predictions;
    }

    public function getNotResolvedPredictions() {
        $predictions = [];
        $this->mysqli_client->query("DELETE FROM `betstudyPredictions` WHERE `timestamp`<" . (time() - 60 * 60));
        $this->mysqli_client->query("DELETE FROM `wettportalOdds` WHERE `timestamp`<" . (time() - 172800));
        $this->mysqli_client->query("DELETE FROM `wettportalEvents` WHERE `timestamp`<" . (time() - 60 * 60));
        $sql         = $this->mysqli_client->query("SELECT * FROM `betstudyPredictions` WHERE `timestamp`>" . time() . " AND `wettportalLink` IS NULL");
        while ($row         = $sql->fetch_assoc()) {
            $predictions[] = $row;
        }
        return $predictions;
    }

}
