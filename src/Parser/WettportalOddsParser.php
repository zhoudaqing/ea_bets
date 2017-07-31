<?php

namespace Parser;

class WettportalOddsParser {

    const API_URL_TEMPLATE = 'https://uk.wettportal.com/lib/ajax/getOddsJSON.php?partner=wettportal&lang=en&period=fulltime&betgame=&eventmode=upcoming&event_id=%s&bettype=%s';

    private $mysqli_client;
    private $bettypes                      = [
        '3way', '2way', 'correctscore', 'overunder', 'handicap', 'spread', 'asianhandicap',
        'drawnobet', 'doublechance', 'halftimefulltime', 'oddeven',
    ];
    private $betTypesMappingWettportalToDb = [
        '3way'         => '3way',
        '2way',
        'correctscore' => 'correctScore',
        'overunder'    => 'overUnder',
        'handicap',
        'spread',
        'asianhandicap',
        'drawnobet',
        'doublechance',
        'halftimefulltime',
        'oddeven',
    ];
    private $betTypesMappingDbToWettportal = [
        '3way'         => '3way',
        '2way',
        'correctScore' => 'correctscore',
        'overUnder'    => 'overunder',
        'handicap',
        'spread',
        'asianhandicap',
        'drawnobet',
        'doublechance',
        'halftimefulltime',
        'oddeven',
    ];
    private $period                        = [
        'fulltime', '1half', '2half', 'minute15', 'minute30', 'minute60', 'minute75'
    ];

    public function __construct($mysqli_client) {
        $this->mysqli_client = $mysqli_client;
    }

    public function parse() {
        //http://uk.wettportal.com/Soccer/Kuwait/Crown_Prince_Cup/Al-Qadsia_-_Yarmouk_2101323.html
        //http://uk.wettportal.com/lib/ajax/getOddsJSON.php?partner=wettportal&lang=en&period=fulltime&betgame=&eventmode=upcoming&event_id=2101323&bettype=overunder
        //http://uk.wettportal.com/lib/ajax/getOddsJSON.php?partner=wettportal&lang=en&period=fulltime&betgame=&eventmode=upcoming&event_id=2101323&bettype=overunder
        //http://uk.wettportal.com/lib/ajax/getOddsJSON.php?partner=wettportal&lang=en&period=fulltime&betgame=&eventmode=upcoming&event_id=2101323&bettype=overunder
        //http://uk.wettportal.com/lib/ajax/getOddsJSON.php?partner=wettportal&lang=en&period=fulltime&betgame=&eventmode=upcoming&event_id=2101323&bettype=overunder
        //http://uk.wettportal.com/lib/ajax/getOddsJSON.php?partner=wettportal&lang=en&period=fulltime&betgame=&eventmode=upcoming&event_id=2095313&bettype=correctscore
        //http://uk.wettportal.com/lib/ajax/getOddsJSON.php?partner=wettportal&lang=en&period=fulltime&betgame=&eventmode=upcoming&event_id=2095313&bettype=3way
    }

    public function getOdds() {
        $sql = $this->mysqli_client->query("SELECT * FROM `betstudyPredictions` WHERE `wettportalLink` IS NOT NULL AND `timestamp`>" . time() . " AND `timestamp`<" . (time() + (24 * 60 * 60)) . " AND `betstudyId` NOT IN (SELECT `betstudyId` FROM `wettportalOdds`)");

        $predictions = [];
        while ($row         = $sql->fetch_assoc()) {
            $predictions[] = $row;
        }

        $add = $this->mysqli_client->prepare("INSERT INTO `wettportalOdds` (`timestamp`, `link`, `title`, `1`, `X`, `2`, `over`, `under`, `prediction`, `wettportalLink`, `betstudyId`, `country`, `league`, `odds`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($predictions as $prediction) {

//            $prediction['excelLineNumber']  = null;
//            $prediction['result']           = null;
//            $prediction['telegramNotified'] = false;

            $html  = $this->doRequest('https://mobile.bet365.com/V6/sport/coupon/coupon.aspx?ot=2&key=' . $prediction['wettportalLink']);
            $doc   = new \DOMDocument();
            @$doc->loadHTML($html);
            $xpath = new \DomXPath($doc);
            $div   = $xpath->query('//div[@class="priceColumn qtr light B4_2"]');
            $odds  = $t     = [];
            if ($div->length > 0) {
                for ($i = 0; $i < 12; $i++) {
                    $score         = explode('-', trim($div->item($i)->childNodes->item(1)->textContent));
                    $t['score']    = (($i - 2) > -1 && ($i - 2) % 3 === 0) ? $score[1] . '-' . $score[0] : $score[0] . '-' . $score[1];
                    $t['bet_slip'] = trim($div->item($i)->childNodes->item(2)->textContent);
                    $t['code']     = trim($div->item($i)->getAttribute('data-nav'));

                    $odds['correctScore'][$t['score']] = $t;
                    //break;
                }
            }
            $t        = [];
            $fulltime = $xpath->query('//div[@class="priceColumn third light B1"]');
            if ($fulltime->length > 0) {
                for ($i = 0; $i < 3; $i++) {
                    $t['score']    = trim($fulltime->item($i)->childNodes->item(1)->textContent);
                    $t['bet_slip'] = trim($fulltime->item($i)->childNodes->item(2)->textContent);
                    $t['code']     = trim($fulltime->item($i)->getAttribute('data-nav'));

                    $odds['3way'][$t['score']] = $t;
                    //break;
                }
            }
            $t         = [];
            $overunder = $xpath->query('//div[@data-plbtid="981"]/div');
            if ($overunder->length > 0) {
                $t['score']      = trim($overunder->item(0)->textContent);
                $t['over_slip']  = trim($overunder->item(1)->textContent);
                $t['over_code']  = trim($overunder->item(1)->getAttribute('data-nav'));
                $t['under_slip'] = trim($overunder->item(2)->textContent);
                $t['under_code'] = trim($overunder->item(2)->getAttribute('data-nav'));

                $odds['underover'][$t['score']] = $t;
            }
            $prediction['odds'] = json_encode($odds);
            $add->bind_param('issiiiiississs', $prediction['timestamp'], $prediction['link'], $prediction['title'], $prediction['1'], $prediction['X'], $prediction['2'], $prediction['over'], $prediction['under'], $prediction['prediction'], $prediction['wettportalLink'], $prediction['betstudyId'], $prediction['country'], $prediction['league'], $prediction['odds']);
            $add->execute();
//            try {
//                $this->mongoClient
//                        ->selectCollection(DB_NAME, 'wettportalOdds')
//                        ->insertOne($prediction);
//            } catch (\MongoDB\Driver\Exception\BulkWriteException $exception) {
//                echo 'Odds with link "' . $prediction['link'] . '" was skipped. Possible duplicate.' . PHP_EOL;
//                continue;
//            }
        }
    }

    public function fetchOdds($wettportalLink, $type) {
        $odds = $this->getOddsByBettype($wettportalLink, $this->betTypesMappingDbToWettportal[$type]);

        //todo: update db

        return $odds;
    }

    private function getOddsByBettype($wettportalLink, $bettype) {
        preg_match('/_([0-9]*).html/', $wettportalLink, $matches);

        $eventId = $matches[1];

        $url = sprintf(self::API_URL_TEMPLATE, $eventId, $bettype);

        $content = $this->doRequest($url);

        $json = json_decode($content);

        return $json->odds;
    }

    private function doRequest($url) {
        //curl 'http://uk.wettportal.com/lib/ajax/getOddsJSON.php?partner=wettportal&lang=en&period=fulltime&betgame=&eventmode=upcoming&event_id=2103138&bettype=overunder'
        // -H 'X-Requested-With: XMLHttpRequest'
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER         => false, // don't return headers
            CURLOPT_USERAGENT      => "Chrome/54.0.2840.98",
            CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
            CURLOPT_TIMEOUT        => 120, // timeout on response
            CURLOPT_MAXREDIRS      => 2, // stop after 10 redirects
            CURLOPT_SSL_VERIFYHOST => 0, // don't verify ssl
            CURLOPT_SSL_VERIFYPEER => false, //
            CURLOPT_HTTPHEADER     => ['X-Requested-With: XMLHttpRequest']
        ]);

        $content = curl_exec($ch);

        $error = curl_error($ch);
        $info  = curl_getinfo($ch);

        curl_close($ch);

        return $content;
    }

}
