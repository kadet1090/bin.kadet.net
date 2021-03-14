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

function get_pastebin_version() {
    if (file_exists($versionFile = __DIR__ . '/../.version')) {
        return file_get_contents($versionFile);
    }

    if (`which git`) {
        return trim(`git rev-parse --short HEAD`);
    }

    return '0.x';
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
        foreach(preg_split('/\R/u', $source) as $i => $line) {
            $class = ['line'];

            if(isset($mapping[$i+1]['line'])) {
                $no = $mapping[$i+1]['line'];
            }

            if(isset($mapping[$i+1]['highlight']) && $mapping[$i+1]['highlight']) {
                $class[] = "highlight";
            }

            $result .= sprintf(
                "<div class=\"%s\"><code><span class=\"counter\" data-ln=\"%d\" title=\"%d\"></span>%s\n</code></div>",
                implode(' ', $class), $no, $i+1, $line
            );

            $no++;
        }

        return $result;
    }, ['is_safe' => ['html']]));

    $twig->addGlobal('version', get_pastebin_version());
    return $twig;
});

return $app;
