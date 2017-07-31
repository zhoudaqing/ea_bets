<?php
require 'bet365.php';
$bet = new bet365();
$bet->auth();
echo $bet->get_balance();
exit;
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/container.php';

//require_once __DIR__ . '/cron_getBetstudyPredictions.php';
$mr = $container->get('match_resolver_service');
var_dump($mr->resolve());
exit;
require_once __DIR__ . '/cron_getWettportalEvents.php';
require_once __DIR__ . '/cron_resolver.php';
require_once __DIR__ . '/cron_getWettportalOdds.php';