<?php

/**   JDy&xM}OWZ5p
 * Description of 365
 *
 * @author VVB
 */
class bet365 {

    public $login = 'kvetlorien';
    public $pass  = 'Colliekiller4!';
    public $country, $commands, $score_bet, $good_team, $betguid;

    function get_country() {
        $data = json_decode($this->load('http://www.stats.betradar.com/s4/menu.php?clientid=3&language=en&state=2_1&type=&id='));

        foreach ($data as $row) {
            if ($row->type == 'category') {
                $this->country[$row->name] = $row->id;
            }
        }
    }

    function calc_bet($code = false) {
        /**
         * Если кеф на 0-1 на 4 и больше пункта выше, чем на 1-0, 
         * то берем 1-0 1-1 0-1 1-2, во всех остальных случаях берем 1-0 1-1 0-1 2-1
         */
        if (empty($this->score_bet) || !is_array($this->score_bet)) return FALSE;
        if ($code) {
            $bet = [
                '1-0' => ['code' => $this->score_bet['1-0']['code'], 'bet_slip' => $this->score_bet['1-0']['bet_slip']],
                '1-1' => ['code' => $this->score_bet['1-1']['code'], 'bet_slip' => $this->score_bet['1-1']['bet_slip']],
                '0-1' => ['code' => $this->score_bet['0-1']['code'], 'bet_slip' => $this->score_bet['0-1']['bet_slip']],
            ];
        }
        else {
            $bet = [
                $this->score_bet['1-0']['code'],
                $this->score_bet['1-1']['code'],
                $this->score_bet['0-1']['code'],
            ];
        }
        if ($this->score_bet['0-1']['bet_slip'] - $this->score_bet['1-0']['bet_slip'] >= 4) {
            if ($code) $bet['1-2'] = ['code' => $this->score_bet['1-2']['code'], 'bet_slip' => $this->score_bet['1-2']['bet_slip']];
            else $bet[]      = $this->score_bet['1-2']['code'];
        }
        else {
            if ($code) $bet['2-1'] = ['code' => $this->score_bet['2-1']['code'], 'bet_slip' => $this->score_bet['2-1']['bet_slip']];
            else $bet[]      = $this->score_bet['2-1']['code'];
        }
        return $bet;
    }

    function add_bet() {
        $this->auth();
        if (!empty($this->score_bet) && $this->check_auth()) {
            $cookie  = new CookiejarEdit('cookie.txt', 'mobile.bet365.com');
            $code    = $this->calc_bet();
            $aFields = [
                'bs',
                'bt=1||&ns=' . implode('||', $code) . '#||&'
            ];
            var_dump($aFields);
            $cookie->setCookie($aFields);
            $html    = $this->load('https://mobile.bet365.com/betslip/?op=0&ck=bs&betsource=Lite&streaming=1&fulltext=1&refreshbal=0&isocode=GBP&qb=0');
            $doc     = new DOMDocument();
            @$doc->loadHTML($html);
            $xpath   = new DomXPath($doc);
            $div     = $xpath->query('//ul');
            if ($div->length > 0 && $div->item(0)->hasAttribute('data-betguid')) {
                $this->betguid = $div->item(0)->getAttribute('data-betguid');
                return $this->betguid;
            }
        }
        return FALSE;
    }

    function add_price($price) {
        $cookie = new CookiejarEdit(__DIR__ . '/cookie.txt', 'mobile.bet365.com');
        $bs     = $cookie->getCookie('bs');
        var_dump($bs);
        if (is_array($bs)) {
            $a = explode('&', urldecode($bs[6]));
            $b = explode('||', $a[1]);
            foreach ($b as $k => $value) {
                $t = explode('#', $value);
                foreach ($t as $key => $tt) {
                    $q            = explode('=', $tt);
                    $c[$k][$q[0]] = $q[1] . (isset($q[2]) ? '=' . $q[2] : NULL);
                }
            }
            foreach ($c as $key => $value) {
                unset($c[$key][""]);
                $coef = explode('/', $value['o']);
                if (isset($value['es'])) {
                    $c[$key]['es']  = 1;
                    $c[$key]['st']  = $price;
                    $c[$key]['tr']  = $price * ($coef[0] / $coef[1] + 1);
                    $c[$key]['ust'] = $price;
                }
            }

            foreach ($c as $k => $value) {
                $tk = [];
                foreach ($value as $key => $tt) {
                    $q    = $key . '=' . $tt;
                    $tk[] = $q;
                }
                $ttt[$k] = implode('#', $tk);
            }
            $cook    = $a[0] . '&' . implode('||', $ttt) . '&' . $a[2];
            $aFields = [
                'bs',
                $cook
            ];
            var_dump($aFields);
            //exit;
            $cookie->setCookie($aFields);
            return $this->load('https://mobile.bet365.com/betslip/?op=2&ck=bs&betsource=Lite&streaming=1&fulltext=1&betguid=' . $this->betguid . '&refreshbal=0&isocode=GBP&qb=0', array(), 1);
        }
    }

    function get_match($t) {
        if ($t['code'] === NULL) return;
        $html  = $this->load('https://mobile.bet365.com/V6/sport/coupon/coupon.aspx?ot=2&key=' . $t['code']);
        $doc   = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DomXPath($doc);
        $div   = $xpath->query('//div[@class="priceColumn qtr light B4_2"]');
        if ($div->length > 0) {
            for ($i = 0; $i < 12; $i++) {
                $score         = explode('-', trim($div->item($i)->childNodes->item(1)->textContent));
                $t['score']    = (($i - 2) > -1 && ($i - 2) % 3 === 0) ? $score[1] . '-' . $score[0] : $score[0] . '-' . $score[1];
                $t['bet_slip'] = (float) trim($div->item($i)->childNodes->item(2)->textContent);
                $t['code']     = trim($div->item($i)->getAttribute('data-nav'));

                $this->score_bet[$t['score']] = $t;
                //break;
            }
        }
        else {
            var_dump($t);
            var_dump($html);
            exit('Проверь прокси, не могу прочитать страницу!');
        }
    }

    function load_all_games() {
        $html  = $this->load('https://mobile.bet365.com.au/V6/sport/splash/splash.aspx?key=1');
        $doc   = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DomXPath($doc);
        $key   = $xpath->query('//h3[text()="Main Lists"]');
        //var_dump($key->item(0)->parentNode->childNodes->item(3)->childNodes->item(1)->getAttribute('class'));
        if ($key->length < 1) {
            exit('Проверь прокси, не могу прочитать страницу!');
        }
        $code = $key->item(0)->parentNode->childNodes->item(3)->childNodes->item(1)->getAttribute('data-sportskey');
        if ($key->length > 0 && !empty($code)) {
            $html  = $this->load('https://mobile.bet365.com.au/V6/sport/coupon/coupon.aspx?key=' . $code);
//            echo 'https://mobile.bet365.com.au/V6/sport/coupon/coupon.aspx?key=' . $code;
//            var_dump($html);
//            exit;
            $doc   = new DOMDocument();
            @$doc->loadHTML($html);
            $xpath = new DomXPath($doc);
            $div   = $xpath->query('//div[@class="ippg-Market_GameDetail"]');
//            var_dump($div->length);
//            exit;
            for ($i = 0; $i < $div->length; $i++) {
                $t['team1']       = trim($div->item($i)->childNodes->item(1)->textContent);
                $t['team2']       = trim($div->item($i)->childNodes->item(3)->textContent);
                $t['code']        = @explode(',', trim($div->item($i)->parentNode->parentNode->getAttribute('data-nav')))[2];
                $this->commands[] = $t;
                //break;
            }
        }
    }

    function search_365($team1, $team2) {

        $team1 = preg_replace('/&(.)(tilde|uml|acute);/', "$1", mb_convert_encoding(trim($team1), 'HTML-ENTITIES', 'utf-8'));
        $team2 = preg_replace('/&(.)(tilde|uml|acute);/', "$1", mb_convert_encoding(trim($team2), 'HTML-ENTITIES', 'utf-8'));
        if (empty($this->commands)) {
            $this->load_all_games();
        }
        foreach ($this->commands as $row) {
            if ($row['team1'] === $team1 || $row['team2'] === $team2) {
                $this->good_team[] = $row;
            }
        }
        if (count($this->good_team) == 1) {
            return $this->get_match($this->good_team[0]);
        }
        else {
            return FALSE;
        }
    }

    function search($team1, $tt) {
        $return  = array();
        $t       = explode(' ', $tt);
        $country = array_shift($t);
        $league  = implode(' ', $t);
        $team1   = preg_replace('/&(.)(tilde|uml|acute);/', "$1", mb_convert_encoding(trim($team1), 'HTML-ENTITIES', 'utf-8'));
        $country = preg_replace('/&(.)(tilde|uml|acute);/', "$1", mb_convert_encoding(trim($country), 'HTML-ENTITIES', 'utf-8'));
        $html    = $this->load('http://www.stats.betradar.com/s4/gismo.php?&html=0&id=2299&language=en&clientid=3&child=4&state=2_searchengine%2C327_' . urlencode(trim($team1)));
        $doc     = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath   = new DomXPath($doc);
        $input   = $xpath->query('//c[@builderclass="SR_S4_Feed_Html_Search_Results"]');
        if ($input->length < 1) {
            $html = $this->load('http://www.stats.betradar.com/s4/sphinx/searchengine.php?i=3_en_6666&term=' . trim($team1));
            $data = json_decode($html);
            if (count($data) > 0) {
                foreach ($data as $row) {
                    if (strpos($row->c, 's-1,') !== FALSE || $row->c === 's-1') {
                        $team['name']    = $row->label;
                        $team['country'] = ''; //$temp[2];
                        $team['league']  = $row->d;
                        $team['nid']     = $row->nid;

                        $l = str_replace('"', '', $this->load('http://www.stats.betradar.com/s4/?clientid=3&uniqueteamid=' . $team['nid'] . '&returnstate=1'));
                        $c = $this->load('http://www.stats.betradar.com/s4/gismo.php?&html=1&id=2301&language=en&clientid=3&state=' . $l . '&child=1');
                        $c = explode('&gt;', $c);
                        $c = trim($c[1]);
                        $c = explode(' ', $c);
                        if ($c[0] === $country) {
                            $ht            = $this->load('http://www.stats.betradar.com/s4/gismo.php?&html=0&id=3885&language=en&clientid=3&state=' . $l . '&child=9');
                            $doc           = new DOMDocument();
                            @$doc->loadHTML($ht);
                            $xpath         = new DomXPath($doc);
                            $input         = $xpath->query('//i[@v="' . $team['nid'] . '"]');
                            $team['label'] = $input->item(0)->getAttribute('name');
                            $return[]      = $team;
                        }
                    }
                }
            }
        }
        else {
            $data = unserialize($input->item(0)->getAttribute('data'));
            //var_dump($data);
            if (!empty($data[4])) {
                foreach ($data[4]['data'] as $row) {
                    if (strpos($row['c'], 's-1,') !== FALSE) {
                        $temp = explode(' - ', $row['label']);
                        if ($country === $temp[2]) {
                            $team['name']    = $temp[0];
                            $team['country'] = $temp[2];
                            $team['league']  = $temp[1];
                            $team['nid']     = $row['nid'];

                            $l             = str_replace('"', '', $this->load('http://www.stats.betradar.com/s4/?clientid=3&uniqueteamid=' . $team['nid'] . '&returnstate=1'));
                            $ht            = $this->load('http://www.stats.betradar.com/s4/gismo.php?&html=0&id=3885&language=en&clientid=3&state=' . $l . '&child=9');
                            $doc           = new DOMDocument();
                            @$doc->loadHTML($ht);
                            $xpath         = new DomXPath($doc);
                            $input         = $xpath->query('//i[@v="' . $team['nid'] . '"]');
                            $team['label'] = $input->item(0)->getAttribute('name');
                            $return[]      = $team;
                        }
                    }
                }
            }
        }
        if (empty($return)) {
            $new_term = explode(' ', $team1);
            if (count($new_term) > 1) {
                for ($i = 0; $i < count($new_term); $i++) {
                    if (mb_strlen($new_term[$i]) > 2) {
                        $return = array_merge($return, $this->search($new_term[$i], $tt));
                    }
                }
            }
        }
        return $return;
    }

    function auth() {
        $data = [
            'txtUserName'     => $this->login,
            'txtPassword'     => $this->pass,
            'txtType'         => 85,
            //'txtTKN'          => 'F6FBC1A8B7B9E522855FF3014C0CBEF8000003',
            'txtHPFV'         => 'NOTSET NOTSET',
            'txtScreenSize'   => '1600 x 900',
            'txtFlashVersion' => 'NOTSET',
            'IS'              => 1,
        ];
        if (!$this->check_auth()) {
            $this->load('https://members.bet365.com/Members/lp/default.aspx', $data, 1);
            $this->load('https://www.bet365.com/home/iplr.asp?IS=1');
        }
        return $this->check_auth();
    }

    function get_balance() {
        // ctl00_main_deposit_ProdBal_lbBal
        $data  = $this->load('https://members.bet365.com/Members/Authenticated/Bank/Deposit/');
        $doc   = new DOMDocument();
        @$doc->loadHTML($data);
        $xpath = new DomXPath($doc);
        $input = $xpath->query('//span[@id="ctl00_main_deposit_ProdBal_lbBal"]');
        if ($input->length > 0) {
            return $input->item(0)->textContent;
        }
        else {
            return FALSE;
        }
    }

    function getMyBets() {
        $data = $this->load('https://mobile.bet365.com/default.aspx');
        $doc   = new DOMDocument();
        @$doc->loadHTML($data);
        $xpath = new DomXPath($doc);
        $input = $xpath->query('//script[@id="WideHeaderData"]');
        if ($input->length > 0) {
            return $input->item(0)->textContent;
        }
        else {
            return FALSE;
        }
    }

    function check_auth() {
        return $this->get_balance() ? TRUE : FALSE;
    }

    function load($url, $data = array(), $post = FALSE) {
        $ch     = curl_init();
        $header = [];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 YaBrowser/16.9.1.1131 Yowser/2.5 Safari/537.36');
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, $post);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $header['Content-Type'] = 'application/x-www-form-urlencoded';
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $html      = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Получаем HTTP-код
        if ($http_code == 500) {
            //         save_log($url . ' - ' . $http_code);
//            exit($url . ' - ' . $http_code);
        }
        //echo strlen($html) . '-' . $http_code . PHP_EOL;
        curl_close($ch);
        return $html;
    }

}
