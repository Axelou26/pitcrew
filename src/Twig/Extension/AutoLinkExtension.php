<?php

declare(strict_types=1);

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

    /**
     * Convertit automatiquement les URLs en liens.
     *
     * @param array<string, mixed> $options
     */
    public function autoLink(string $text, array $options = []): string
    {
        $defaultOptions = [
            'target' => '_blank',
            'rel'    => 'noopener noreferrer',
            'class'  => 'auto-link',
        ];

        $options = array_merge($defaultOptions, $options);

        $pattern     = '/(https?:\/\/[^\s<]+)/i';
        $replacement = '<a href="$1" target="' . $options['target'] . '" rel="' . $options['rel'] .
            '" class="' . $options['class'] . '">$1</a>';

        return preg_replace($pattern, $replacement, $text) ?? $text;
    }
}
