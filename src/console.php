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

$console = new Application('Kadet\'s pastebin', '1.0');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
$console->setDispatcher($app['dispatcher']);
$console
    ->register('migrate')
    ->setDescription('Migrates DB to proper version')
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

$console
    ->register('list')
    ->setDefinition([
         new InputOption('author', 'a', InputOption::VALUE_OPTIONAL, 'Filters by author'),
         new InputOption('language', 'l', InputOption::VALUE_OPTIONAL, 'Filters by language'),
         new InputOption('url', 'u', InputOption::VALUE_OPTIONAL, 'Page url'),
    ])
    ->setDescription('Lists all pastes')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        /** @var Doctrine\DBAL\Connection $db */
        $db = $app['db'];

        $query = $db->createQueryBuilder()->from('pastes')->select('*');

        if($input->getOption('author')) {
            $query
                ->andWhere('author = :author')
                ->setParameter('author', $input->getOption('author'))
            ;
        }

        if($input->getOption('language')) {
            $query
                ->andWhere('language LIKE :language')
                ->setParameter('language', $input->getOption('language'))
            ;
        }

        $url = $input->getOption('url') ? 'http://'.$input->getOption('url').'/' : null;

        $found = $db->fetchAll($query, $query->getParameters());
        $table = new \Symfony\Component\Console\Helper\Table($output);
        $table
            ->setHeaders(['Title', 'Author', 'Language', 'Url'])
            ->setRows(array_map(function($paste) use ($app, $url) {
                return [
                    $paste['title'] ?: 'Untitled',
                    $paste['author'] ?: 'unknown',
                    $paste['language'] ?: 'plaintext',
                    $url.$paste['slug']
                ];
            }, $found))
        ;
        $table->render();
    })
;

return $console;
