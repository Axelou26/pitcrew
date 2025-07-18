<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:check-orphaned-files',
    description: 'Vérifie les fichiers orphelins dans le projet',
)]
class CheckOrphanedFilesCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🧹 Vérification des fichiers orphelins');

        $orphanedFiles = [];

        // Vérifier les fichiers CSS orphelins
        $cssFiles = $this->findCssFiles();
        foreach ($cssFiles as $cssFile) {
            if (!$this->isCssFileReferenced($cssFile)) {
                $orphanedFiles[] = [
                    'type' => 'CSS',
                    'file' => $cssFile,
                    'reason' => 'Non référencé dans les templates'
                ];
            }
        }

        // Vérifier les fichiers JS orphelins
        $jsFiles = $this->findJsFiles();
        foreach ($jsFiles as $jsFile) {
            if (!$this->isJsFileReferenced($jsFile)) {
                $orphanedFiles[] = [
                    'type' => 'JS',
                    'file' => $jsFile,
                    'reason' => 'Non référencé dans les templates'
                ];
            }
        }

        // Vérifier les fichiers PHP orphelins (hors tests)
        $phpFiles = $this->findPhpFiles();
        foreach ($phpFiles as $phpFile) {
            if (!$this->isPhpFileReferenced($phpFile)) {
                $orphanedFiles[] = [
                    'type' => 'PHP',
                    'file' => $phpFile,
                    'reason' => 'Non référencé dans le code'
                ];
            }
        }

        if (empty($orphanedFiles)) {
            $io->success('Aucun fichier orphelin détecté !');
            return Command::SUCCESS;
        }

        $io->warning(sprintf('%d fichiers orphelins détectés :', count($orphanedFiles)));

        foreach ($orphanedFiles as $file) {
            $io->writeln(sprintf(
                '  • %s: %s (%s)',
                $file['type'],
                $file['file'],
                $file['reason']
            ));
        }

        $io->note('Vérifiez manuellement ces fichiers avant suppression.');

        return Command::SUCCESS;
    }

    private function findCssFiles(): array
    {
        $finder = new Finder();
        $finder->files()->name('*.css')->in('public/css');

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRelativePathname();
        }

        return $files;
    }

    private function findJsFiles(): array
    {
        $finder = new Finder();
        $finder->files()->name('*.js')->in('assets/js');

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRelativePathname();
        }

        return $files;
    }

    private function findPhpFiles(): array
    {
        $finder = new Finder();
        $finder->files()->name('*.php')->in('src')->exclude('tests');

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRelativePathname();
        }

        return $files;
    }

    private function isCssFileReferenced(string $cssFile): bool
    {
        $finder = new Finder();
        $finder->files()->name('*.twig')->in('templates');

        foreach ($finder as $file) {
            $content = $file->getContents();
            if (strpos($content, $cssFile) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isJsFileReferenced(string $jsFile): bool
    {
        $finder = new Finder();
        $finder->files()->name('*.twig')->in('templates');

        foreach ($finder as $file) {
            $content = $file->getContents();
            if (strpos($content, $jsFile) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isPhpFileReferenced(string $phpFile): bool
    {
        // Ignorer les fichiers de base (Entity, Repository, etc.)
        if ($this->isBasePhpFile($phpFile)) {
            return true;
        }

        return $this->isPhpFileUsedInCode($phpFile);
    }

    private function isBasePhpFile(string $phpFile): bool
    {
        $baseDirectories = [
            'Entity/',
            'Repository/',
            'Controller/',
            'Command/',
            'Form/',
            'Service/',
            'Security/',
            'Twig/',
            'DataFixtures/',
            'Migrations/'
        ];

        foreach ($baseDirectories as $directory) {
            if (strpos($phpFile, $directory) === 0) {
                return true;
            }
        }

        return false;
    }

    private function isPhpFileUsedInCode(string $phpFile): bool
    {
        $finder = new Finder();
        $finder->files()->name('*.php')->in('src')->exclude('tests');

        $className = pathinfo($phpFile, PATHINFO_FILENAME);

        foreach ($finder as $file) {
            if ($file->getRelativePathname() === $phpFile) {
                continue;
            }

            $content = $file->getContents();
            if (strpos($content, $className) !== false) {
                return true;
            }
        }

        return false;
    }
}
