<?php

namespace App\Support;

use App\Support\CommonMarkExtensions\MathExpressionExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

trait MarkdownParser
{
    public function parse(?string $markdown = null): string
    {
        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'external_link' => [
                'internal_hosts' => config('app.url'),
                'open_in_new_window' => true,
                'html_class' => 'external-link',
                'nofollow' => '',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new ExternalLinkExtension);
        $environment->addExtension(new AttributesExtension);
        $environment->addExtension(new MathExpressionExtension);

        $converter = new MarkdownConverter($environment);

        // Pre-process: Replace every single newline with double newline
        $markdown = $markdown ?? '';
        // Replace all CRLF and CR with LF for consistency
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        // Replace every single newline with double newline
        $markdown = preg_replace('/([^\n])\n([^\n])/m', "$1\n\n$2", $markdown);

        return $converter->convert($markdown);
    }
}
