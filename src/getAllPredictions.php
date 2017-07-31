<?php

require_once __DIR__ . '/../vendor/autoload.php';

$mongo = new \MongoDB\Client('mongodb://mongo/');

$wettportal = $mongo->selectDatabase('wettportal');

$predictionsCollection = $wettportal->selectCollection('betstudyPredictions');

$predictions = $predictionsCollection->find();

echo json_encode($predictions->toArray());


