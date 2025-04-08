<?php
namespace Deployer;

require 'recipe/common.php';
require 'recipe/symfony.php';

// Config
set('repository', 'https://github.com/Axelou26/pitcrew.git');
set('git_tty', true);
set('keep_releases', 5);

// Partagé entre les déploiements
set('shared_files', [
    '.env.local',
    '.env.prod.local'
]);
set('shared_dirs', [
    'var/log',
    'var/sessions',
    'public/uploads'
]);

// Répertoires à écraser entre les déploiements
set('writable_dirs', [
    'var',
    'var/cache',
    'var/log',
    'var/sessions',
    'public/uploads'
]);

// Hôtes
host('local')
    ->setHostname('localhost')
    ->set('remote_user', get_current_user())
    ->set('deploy_path', getcwd() . '/deploy')
    ->set('bin/php', 'php')
    ->set('composer_options', '--no-dev --optimize-autoloader')
    ->set('branch', 'main');

// Désactiver SSH pour le déploiement local
set('use_relative_symlink', true);
set('use_ssh', false);

// Tâches
task('build', function () {
    cd('{{release_path}}');
    run('composer install --no-dev --optimize-autoloader');
    run('npm install');
    run('npm run build');
});

// Nettoyage du cache
task('app:cache:clear', function () {
    cd('{{release_path}}');
    run('php bin/console cache:clear');
});

// Tâches de qualité et performance
task('quality:check', function () {
    cd('{{release_path}}');
    run('php vendor/bin/php-cs-fixer fix --dry-run --diff');
    run('php vendor/bin/phpstan analyse src');
});

task('performance:check', function () {
    cd('{{release_path}}');
    run('php bin/console cache:warmup');
    run('php bin/console doctrine:schema:validate');
});

// Hooks
after('deploy:failed', 'deploy:unlock');
before('deploy:symlink', 'database:migrate');
after('deploy:symlink', 'app:cache:clear');

// Pipeline de déploiement
desc('Deploy project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:shared',
    'deploy:writable',
    'build',
    'quality:check',
    'performance:check',
    'database:migrate',
    'app:cache:clear',
    'deploy:publish'
]); 