<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:check-duplicates',
    description: 'Vérifie les fichiers en double et les migrations dupliquées',
)]
class CheckDuplicatesCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔍 Vérification des doublons');

        $duplicates = [];

        // Vérifier les migrations en double
        $migrationDuplicates = $this->findDuplicateMigrations();
        if (!empty($migrationDuplicates)) {
            $duplicates['Migrations'] = $migrationDuplicates;
        }

        // Vérifier les fichiers JS en double
        $jsDuplicates = $this->findDuplicateJsFiles();
        if (!empty($jsDuplicates)) {
            $duplicates['JavaScript'] = $jsDuplicates;
        }

        // Vérifier les fichiers CSS en double
        $cssDuplicates = $this->findDuplicateCssFiles();
        if (!empty($cssDuplicates)) {
            $duplicates['CSS'] = $cssDuplicates;
        }

        // Vérifier les configurations en double
        $configDuplicates = $this->findDuplicateConfigs();
        if (!empty($configDuplicates)) {
            $duplicates['Configuration'] = $configDuplicates;
        }

        if (empty($duplicates)) {
            $io->success('Aucun doublon détecté !');
            return Command::SUCCESS;
        }

        $io->warning('Doublons détectés :');

        foreach ($duplicates as $type => $files) {
            $io->section($type);
            foreach ($files as $file) {
                $io->writeln(sprintf('  • %s', $file));
            }
        }

        $io->note('Vérifiez ces doublons et supprimez les fichiers obsolètes.');

        return Command::SUCCESS;
    }

    private function findDuplicateMigrations(): array
    {
        $finder = new Finder();
        $finder->files()->name('Version*.php')->in('migrations');

        $migrations = [];
        $duplicates = [];

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Extraire le nom de la classe
            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                $className = $matches[1];

                if (isset($migrations[$className])) {
                    $duplicates[] = sprintf(
                        'Classe %s dupliquée dans %s et %s',
                        $className,
                        $migrations[$className],
                        $file->getRelativePathname()
                    );
                } else {
                    $migrations[$className] = $file->getRelativePathname();
                }
            }
        }

        return $duplicates;
    }

    private function findDuplicateJsFiles(): array
    {
        $duplicates = [];

        // Vérifier s'il y a des fichiers JS dans public/js et assets/js
        $publicJsFiles = [];
        $assetsJsFiles = [];

        if (is_dir('public/js')) {
            $finder = new Finder();
            $finder->files()->name('*.js')->in('public/js');
            foreach ($finder as $file) {
                $publicJsFiles[] = $file->getFilename();
            }
        }

        if (is_dir('assets/js')) {
            $finder = new Finder();
            $finder->files()->name('*.js')->in('assets/js');
            foreach ($finder as $file) {
                $assetsJsFiles[] = $file->getFilename();
            }
        }

        // Trouver les fichiers en double
        $commonFiles = array_intersect($publicJsFiles, $assetsJsFiles);
        foreach ($commonFiles as $file) {
            $duplicates[] = sprintf(
                'Fichier JS %s présent dans public/js/ et assets/js/',
                $file
            );
        }

        return $duplicates;
    }

    private function findDuplicateCssFiles(): array
    {
        $duplicates = [];

        // Vérifier s'il y a des fichiers CSS dans public/css et assets/styles
        $publicCssFiles = [];
        $assetsCssFiles = [];

        if (is_dir('public/css')) {
            $finder = new Finder();
            $finder->files()->name('*.css')->in('public/css');
            foreach ($finder as $file) {
                $publicCssFiles[] = $file->getFilename();
            }
        }

        if (is_dir('assets/styles')) {
            $finder = new Finder();
            $finder->files()->name('*.css')->in('assets/styles');
            foreach ($finder as $file) {
                $assetsCssFiles[] = $file->getFilename();
            }
        }

        // Trouver les fichiers en double
        $commonFiles = array_intersect($publicCssFiles, $assetsCssFiles);
        foreach ($commonFiles as $file) {
            $duplicates[] = sprintf(
                'Fichier CSS %s présent dans public/css/ et assets/styles/',
                $file
            );
        }

        return $duplicates;
    }

    private function findDuplicateConfigs(): array
    {
        $duplicates = [];

        // Vérifier les configurations de build
        if (file_exists('webpack.config.js') && file_exists('vite.config.js')) {
            $duplicates[] = 'webpack.config.js et vite.config.js coexistent';
        }

        // Vérifier les fichiers de configuration PHP
        $configFiles = [
            'phpstan.neon',
            '.phpstan.neon',
            'phpstan.neon.dist',
            '.phpstan.neon.dist'
        ];

        $existingConfigs = [];
        foreach ($configFiles as $config) {
            if (file_exists($config)) {
                $existingConfigs[] = $config;
            }
        }

        if (count($existingConfigs) > 1) {
            $duplicates[] = sprintf(
                'Plusieurs fichiers de configuration PHPStan détectés: %s',
                implode(', ', $existingConfigs)
            );
        }

        return $duplicates;
    }
}
