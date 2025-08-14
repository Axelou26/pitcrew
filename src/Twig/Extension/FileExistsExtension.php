<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FileExistsExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('file_exists', [$this, 'fileExists']),
        ];
    }

    public function fileExists(string $path): bool
    {
        return file_exists($path);
    }
}
