<?php

use Doctrine\DBAL\Types\Type;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

$console = new Application('Kadet\s pastebin', 'n/a');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
$console->setDispatcher($app['dispatcher']);
$console
    ->register('migrate')
    ->setDefinition(array(
        // new InputOption('some-option', null, InputOption::VALUE_NONE, 'Some help'),
    ))
    ->setDescription('My command description')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        /** @var Doctrine\DBAL\Connection $db */
        $db = $app['db'];

        $posts = new \Doctrine\DBAL\Schema\Table('pastes');
        $posts->addColumn('slug', Type::STRING, ['length' => 32]);
        $posts->addColumn('author', Type::STRING, ['length' => 32])->setNotnull(false);

        $posts->addColumn('title', Type::STRING, ['length' => 128])->setNotnull(false);
        $posts->addColumn('description', Type::TEXT)->setNotnull(false);

        $posts->addColumn('key', Type::STRING, ['length' => 60]);

        $posts->addColumn('added', Type::DATETIME);

        $posts->addColumn('language', Type::STRING, ['length' => 48])->setNotnull(false);

        $posts->addIndex(['slug']);

        $db->getSchemaManager()->createTable($posts);
    })
;

return $console;
