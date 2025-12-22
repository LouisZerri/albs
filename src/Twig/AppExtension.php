<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('autolink', [$this, 'autolink'], ['is_safe' => ['html']]),
            new TwigFilter('forum_content', [$this, 'formatForumContent'], ['is_safe' => ['html']]),
        ];
    }

    public function autolink(string $text): string
    {
        // Convertir les URLs en liens cliquables
        $pattern = '/(https?:\/\/[^\s<>"\']+)/i';
        return preg_replace($pattern, '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 underline">$1</a>', $text);
    }

    public function formatForumContent(string $text): string
    {
        // Échapper le HTML
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Convertir les sauts de ligne en <br>
        $text = nl2br($text);

        // Convertir les URLs en liens cliquables
        $pattern = '/(https?:\/\/[^\s<>"\']+)/i';
        $text = preg_replace($pattern, '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 underline break-all">$1</a>', $text);

        return $text;
    }
}