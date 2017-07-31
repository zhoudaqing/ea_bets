<?php

namespace Parser;

use DateTime;
use DateTimeZone;

class WettportalEventListParser {

    public function getAllSoccerEvents($mysqli_client) {
        $sql      = $mysqli_client->prepare("INSERT IGNORE INTO `wettportalEvents` (`link`, `startDate`, `l`, `title`, `timestamp`) VALUES (?, ?, ?, ?, ?)");
        $html     = $this->load('https://mobile.bet365.com.au/V6/sport/splash/splash.aspx?key=1');
        $doc      = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath    = new \DomXPath($doc);
        $key      = $xpath->query('//h3[text()="Main Lists"]');
        $main_div = $key->item(0)->parentNode->childNodes;
// Обходим страны/континенты
        for ($i = 0; $i < $main_div->length; $i++) {
            if ($main_div->item($i)->nodeName === 'h3') {
                if (trim($main_div->item($i)->textContent) === 'Main Lists') {
                    continue;
                }
                echo '=' . trim($main_div->item($i)->textContent) . ' - ' . $main_div->item($i)->getAttribute('data-sportskey') . PHP_EOL;
                $league      = $this->load('https://mobile.bet365.com/V6/Sport/Splash/SplashPartial.aspx?key=' . $main_div->item($i)->getAttribute('data-sportskey') . '&iscode=USD&isocode=SE&lng=1&zone=3&sst=0&apptype=&appversion=&lvl=1');
                //var_dump($league);
                $leaguedoc   = new \DOMDocument();
                @$leaguedoc->loadHTML($league);
                $leaguexpath = new \DomXPath($leaguedoc);
                $leaguekey   = $leaguexpath->query('//div[@data-nav="couponLink"]');
                // Обходим лиги в каждой стране
                for ($li = 0; $li < $leaguekey->length; $li++) {
                    $league_name   = trim($leaguekey->item($li)->textContent);
                    echo '==' . trim($leaguekey->item($li)->textContent) . ' - ' . $leaguekey->item($li)->getAttribute('data-sportskey') . PHP_EOL;
                    $matches_html  = $this->load('https://mobile.bet365.com/V6/sport/coupon/coupon.aspx?key=' . $leaguekey->item($li)->getAttribute('data-sportskey'));
                    $matches_doc   = new \DOMDocument();
                    //var_dump($matches_html);
                    @$matches_doc->loadHTML($matches_html);
                    $matches_xpath = new \DOMXPath($matches_doc);
                    $matches_key   = $matches_xpath->query('//div[@id="Coupon"]/div/div');
                    $date_time     = '';
                    // Обходим матчи
                    for ($mi = 0; $mi < $matches_key->length; $mi++) {
                        $node = $matches_key->item($mi);
                        //echo '===' . $node->no;
                        $t    = [];
                        if ($node->nodeName === 'div') {
                            if ($node->hasAttribute('class') && $node->getAttribute('class') === 'podHeaderRow') {
                                $d         = explode(' ', $node->childNodes->item(1)->textContent);
                                $date_time = $d[0] . ', ' . $d[1] . ' ' . $d[2] . ' ' . date('Y');
                                if ($d[1] > date('j')) {
                                    break;
                                }
                                //echo date('d.m.Y H:i:s', 1490994000);
                            }
                            elseif ($node->hasAttribute('data-nav')) {
                                $t['team1']    = $this->clear_tild(trim($matches_key->item($mi)->childNodes->item(1)->childNodes->item(1)->childNodes->item(1)->textContent));
                                $t['team2']    = $this->clear_tild(trim($matches_key->item($mi)->childNodes->item(1)->childNodes->item(1)->childNodes->item(3)->textContent));
                                $t['code']     = @explode(',', trim($matches_key->item($mi)->getAttribute('data-nav')))[2];
                                //$t['time']     = strtotime($date_time . ' ' . trim($matches_key->item($mi)->childNodes->item(1)->childNodes->item(1)->childNodes->item(4)->childNodes->item(1)->textContent));
                                $t['time_str'] = $date_time . ' ' . trim($matches_key->item($mi)->childNodes->item(1)->childNodes->item(1)->childNodes->item(4)->childNodes->item(1)->textContent);
                                $t['league']   = $this->clear_tild($league_name);

                                if (!empty($t['code'])) {
                                    $strtotime          = DateTime::createFromFormat("D, d M Y G:i:s O", $t['time_str'] . ':00 +01:00');
                                    $t['timestamp']     = $strtotime->getTimestamp();
                                    $events[$t['code']] = [
                                        'startDate' => $t['time_str'],
                                        'link'      => $t['code'],
                                        'l'         => $t['league'],
                                        'title'     => $t['team1'] . ' - ' . $t['team2'],
                                    ];
                                    $sql->bind_param('ssssi', $t['code'], $t['time_str'], $t['league'], $events[$t['code']]['title'], $t['timestamp']);
                                    $sql->execute();
                                }
                                //exit;
                            }
                        }
                        //break;
                    }
                }
            }
        }
        return $events;
    }

    function clear_tild($str) {
        return mb_convert_encoding(preg_replace('/&(.)(tilde|uml|acute|circ);/u', "$1", mb_convert_encoding(trim($str), 'HTML-ENTITIES', 'utf-8')), 'utf-8', 'HTML-ENTITIES');
    }

    function load($url) {
        $ch     = curl_init();
        $header = [];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 YaBrowser/16.9.1.1131 Yowser/2.5 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $html   = curl_exec($ch);
        curl_close($ch);
        return $html;
    }

}
