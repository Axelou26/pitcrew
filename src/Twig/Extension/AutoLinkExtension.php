<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AutoLinkExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('autolink', [$this, 'autoLink'], ['is_safe' => ['html']]),
        ];
    }

    public function autoLink(string $text, array $options = []): string
    {
        $target = $options['target'] ?? '_blank';
        $pattern = '/(https?:\/\/[^\s<]+)/i';
        
        return preg_replace_callback($pattern, function($matches) use ($target) {
            $url = $matches[0];
            return sprintf('<a href="%s" target="%s" rel="noopener noreferrer">%s</a>', $url, $target, $url);
        }, $text);
    }
} 