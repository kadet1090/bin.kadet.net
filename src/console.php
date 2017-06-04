<?php

use Doctrine\DBAL\Types\Type;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

function saveTableToDb(\Doctrine\DBAL\Schema\AbstractSchemaManager $schema, \Doctrine\DBAL\Schema\Table $table) {
    static $comparator = false;
    if(!$comparator) {
        $comparator = new \Doctrine\DBAL\Schema\Comparator();
    }

    if($schema->tablesExist([ $table->getName() ])) {
        $diff = $comparator->diffTable($schema->listTableDetails($table->getName()), $table);
        if($diff !== false) {
            $schema->alterTable($diff);
        }
    } else {
        $schema->createTable($table);
    }
}

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

        $pastes = new \Doctrine\DBAL\Schema\Table('pastes');
        $pastes->addColumn('slug', Type::STRING, ['length' => 32]);
        $pastes->addColumn('author', Type::STRING, ['length' => 32])->setNotnull(false);

        $pastes->addColumn('title', Type::STRING, ['length' => 128])->setNotnull(false);
        $pastes->addColumn('description', Type::TEXT)->setNotnull(false);

        $pastes->addColumn('key', Type::STRING, ['length' => 60]);

        $pastes->addColumn('added', Type::DATETIME);

        $pastes->addColumn('language', Type::STRING, ['length' => 48])->setNotnull(false);
        $pastes->addColumn('lines', Type::STRING, ['length' => 255])->setNotnull(false);

        $pastes->addIndex(['slug'], 'slug_idx');

        saveTableToDb($db->getSchemaManager(), $pastes);
    })
;

return $console;
