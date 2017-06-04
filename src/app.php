<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());

function wrap($source) {
    $stack = [];

    return implode('\n', array_map(function($line) use (&$stack) {
        
    }, explode('\n', $source)));
}

$app['twig'] = $app->extend('twig', function (Twig_Environment $twig, $app) {
    $twig->addFilter(new Twig_Filter('highlight', function($source, $language) {
        return \Kadet\Highlighter\KeyLighter::get()->highlight(
            $source,
            \Kadet\Highlighter\KeyLighter::get()->getLanguage($language),
            new \Kadet\Highlighter\Formatter\LineContainedHtmlFormatter()
        );
    }, ['is_safe' => ['html']]));

    $twig->addFilter(new Twig_Filter('lineify', function($source, $mapping = []) {
        $no = 1;
        $result = "";
        foreach(preg_split('/\R/', $source) as $i => $line) {
            if(isset($mapping[$i+1])) {
                $no = $i+1;
            }

            $result .= '<div class="line"><code><span class="counter" data-ln="'.$no.'"></span>'.$line."\n</code></div>";
            $no++;
        }

        return $result;
    }, ['is_safe' => ['html']]));

    $twig->addGlobal('version', trim(`git rev-parse --short HEAD`));

    return $twig;
});

return $app;
