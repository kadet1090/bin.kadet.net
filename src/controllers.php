<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @var \Silex\Application $app */

//Request::setTrustedProxies(array('127.0.0.1'));

function is_subset_of($a, $b) {
    return array_intersect($a, $b) == $a;
}

function paste_path($slug) {
    return __DIR__.'/../var/pastes/'.$slug;
}

function get_range($token) {
    return strpos($token, ':') !== false ? explode(':', $token) : [$token, $token];
}

function process_mappings($lines) {
    $result = [];

    if(empty($lines)) {
        return $result;
    }

    foreach(preg_split('/\s+/', $lines) as $i => $token) {
        if($token[0] == '!') {
            list($start, $end) = get_range(substr($token, 1));
            for($i = (int)$start; $i <= (int)$end; $i++) {
                $result[$i]['highlight'] = true;
            }
        } else {
            list($from, $to) = explode(':', $token);
            $result[$from]['line'] = $to;
        }
    }

    return $result;
}

/** @var \Doctrine\DBAL\Connection $db */
$db = $app['db'];

$app->get('/', function () use ($app) {
    return $app['twig']->render('create.html.twig');
})->bind('create');

$app->get('/{slug}', function ($slug) use ($app, $db) {
    if(!file_exists(paste_path($slug))) {
        return $app->abort(404);
    }

    $paste = file_get_contents(paste_path($slug));
    $meta = $db->fetchAssoc('SELECT * FROM pastes WHERE slug = :slug', [ 'slug' => $slug ]);
    $language = $meta['language'] ?? 'text';
    $meta['added'] = DateTime::createFromFormat('Y-m-d H:i:s', $meta['added']);
    $meta['mappings'] = process_mappings($meta['lines']);

    return $app['twig']->render('paste.html.twig', compact('paste', 'meta', 'language'));
})->bind('paste');

$app->get('/{slug}/raw', function ($slug) use ($app, $db) {
    if(!file_exists(paste_path($slug))) {
        return $app->abort(404);
    }

    return new Response(file_get_contents(paste_path($slug)), 200, ['Content-Type' => 'text/plain']);
})->bind('paste-raw');

$app->post('/', function(Request $request) use ($db, $app) {
    $slug = uniqid(null, true);

    if($request->request->has('paste')) {
        file_put_contents(paste_path($slug), $request->get('paste'));
    } elseif($request->files->has('paste')) {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
        $file = $request->files->get('paste');
        $file->move(__DIR__.'/../var/pastes/', $slug);
    } else {
        throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Paste is required.');
    }

    $db->insert('pastes', [
        'slug' => $slug,
        'author' => $request->get('author'),
        'title' => $request->get('title'),
        'description' => $request->get('description'),
        'key' => md5($request->get('key', time())),
        'language' => $request->get('language'),
        'added' => date('Y-m-d H:i:s'),
        'lines' => $request->get('lines')
    ]);

    return new RedirectResponse("/{$slug}");
})->bind('add');

$app->post('/{slug}', function(Request $request, $slug) use ($db, $app) {
    $update = [
        'author' => $request->get('author'),
        'title' => $request->get('title'),
        'description' => $request->get('description'),
        'language' => $request->get('language'),
        'lines' => $request->get('lines')
    ];

    $db->update('pastes', $update, [
        'slug' => $slug,
        'key' => md5($request->get('key'))
    ]);

    return new RedirectResponse("/{$slug}");
})->bind('update');

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});