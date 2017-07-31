<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/container.php';

define('DB_NAME', 'wettportal');


/** @var \MongoDB\Client $mc */
$mc = $container->get('mongo_client');
$d = $mc->selectDatabase(DB_NAME);

$d->createCollection('betstudyPredictions');
$d->createCollection('wettportalEvents');
$d->createCollection('wettportalOdds');
$d->createCollection('telegramUsers');

$mc->selectCollection(DB_NAME, 'betstudyPredictions')->createIndex(['link' => 1], ['unique' => true]);
$mc->selectCollection(DB_NAME, 'wettportalEvents')->createIndex(['link' => 1], ['unique' => true]);
$mc->selectCollection(DB_NAME, 'wettportalOdds')->createIndex(['link' => 1], ['unique' => true]);
$mc->selectCollection(DB_NAME, 'telegramUsers')->createIndex(['username' => 1], ['unique' => true]);
