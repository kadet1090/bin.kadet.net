<?php
namespace Deployer;

require 'recipe/common.php';

// Project name
set('application', 'pastebin');
set('default_stage', 'production');
set('keep_releases', 3);

// Project repository
set('repository', 'git@github.com:kadet1090/bin.kadet.net.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 

// Shared files/dirs between deploys 
set('shared_files', ['var/id.next']);
set('shared_dirs', ['var/pastes', 'var/database']);

// Writable dirs by web server 
set('writable_dirs', ['var']);
set('php_version', '7.1');

// Hosts
localhost()
    ->roles('build')
    ->set('deploy_path', '/tmp/deployer/{{ application }}')
    ->set('keep_releases', 1)
;

inventory($_ENV['DEPLOYER_INVENTORY'] ?? 'hosts.yml');

task('assets:build', function () {
    run('yarn install');
    run('yarn build');
})->onRoles('build');

task('assets:upload', function () {
    foreach (['css', 'fonts', 'img'] as $folder) {
        upload("web/$folder/", "{{ release_path }}/web/$folder");
    }
});

task('assets', ['assets:build', 'assets:upload']);

task('deploy:migrate', function () {
    run('cd {{ release_path }} && {{ bin/console }} migrate');
});

task('deploy:create_version_file', function () {
    run('cd {{ release_path }} && git rev-parse --short HEAD > .version');
});

// Tasks
desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'assets',
    'deploy:create_version_file',
    'deploy:migrate',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
