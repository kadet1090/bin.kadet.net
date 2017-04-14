<?php

// configure your app for the production environment

$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');

if (!file_exists("local.php")) {
    die("No local config, fix that u moron.");
}

include "local.php";
