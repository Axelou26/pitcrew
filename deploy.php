<?php
namespace Deployer;

require 'recipe/symfony.php';

// Config
set('repository', 'https://github.com/Axelou26/pitcrew.git');
set('git_tty', true);
set('keep_releases', 5);

// Partagé entre les déploiements
add('shared_files', [
    '.env.local',
]);
add('shared_dirs', [
    'var/log',
    'var/sessions',
    'public/uploads'
]);

// Répertoires à écraser entre les déploiements
add('writable_dirs', [
    'var',
    'var/cache',
    'var/log',
    'var/sessions',
    'public/uploads'
]);

// Hôtes
host('production')
    ->setHostname('votre-serveur.com')
    ->set('remote_user', 'votre-user')
    ->set('deploy_path', '/var/www/pitcrew');

// Tâches
task('build', function () {
    run('cd {{release_path}} && build');
});

// Hooks
after('deploy:failed', 'deploy:unlock');

// Migration de base de données
before('deploy:symlink', 'database:migrate');

// Nettoyage du cache
after('deploy:symlink', 'symfony:cache:clear');

// Pipeline de déploiement personnalisé
desc('Deploy project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'deploy:cache:clear',
    'deploy:cache:warmup',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
]); 