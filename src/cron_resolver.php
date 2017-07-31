<?php
#20min+2

require_once __DIR__ . '/config.php';

/** @var \Service\MatchResolverService $mr */
$mr = $container->get('match_resolver_service');
$mr->resolve();
