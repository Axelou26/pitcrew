<?php

if (file_exists(dirname(__DIR__) . '/var/cache/prod/App_KernelProdContainer.preload.php')) {
    require dirname(__DIR__) . '/var/cache/prod/App_KernelProdContainer.preload.php';
}

if (file_exists(dirname(__DIR__) . '/var/cache/prod/srcApp_KernelProdContainer.preload.php')) {
    require dirname(__DIR__) . '/var/cache/prod/srcApp_KernelProdContainer.preload.php';
}

// Préchargement des classes fréquemment utilisées
$preload = [
    // Entities
    dirname(__DIR__) . '/src/Entity/User.php',
    dirname(__DIR__) . '/src/Entity/Notification.php',
    dirname(__DIR__) . '/src/Entity/Post.php',
    
    // Repositories
    dirname(__DIR__) . '/src/Repository/UserRepository.php',
    dirname(__DIR__) . '/src/Repository/NotificationRepository.php',
    dirname(__DIR__) . '/src/Repository/PostRepository.php',
    
    // Services
    dirname(__DIR__) . '/src/Service/NotificationService.php',
    
    // Controllers
    dirname(__DIR__) . '/src/Controller/NotificationController.php',
];

foreach ($preload as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}
