<?php

set_time_limit(0);
error_reporting(-1);
ini_set('display_errors', 1);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/container.php';

define('CLIENT_SECRET_PATH', __DIR__ . '/../client_secret1.json');
define('RESULT_COLUMN_CHAR', 'P');
define('RESULT_COLUMN_NUMBER', '15'); #P - 15th letter of alphabet (start from 0)
define('BET_PLACED_COLUMN_NUMBER', '18'); #S - 18th letter of alphabet (start from 0)
define('BET_SUM', '19'); #T - 19th letter of alphabet (start from 0)

define('SCORE_COLUMN', [
    '2-1' => 'F',
    '1-0' => 'G',
    '2-0' => 'H',
    '0-0' => 'I',
    '1-1' => 'J',
    '0-1' => 'K',
    '0-2' => 'L',
    '1-2' => 'M',
]);
