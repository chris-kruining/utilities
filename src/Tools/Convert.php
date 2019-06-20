<?php

namespace CPB\Utilities\Tools
{
    use League\HTMLToMarkdown\HtmlConverter;
    use Soundasleep\Html2Text;
    use xprt64\HtmlTableToMarkdownConverter\TableConverter;

    final class Convert
    {
        public static function htmlToMarkdown(string $html): string
        {
            $environment = League\HTMLToMarkdown\Environment::createDefaultEnvironment([
                'strip_tags' => true,
                'suppress_errors' => true,
                'remove_nodes' => 'style img'
            ]);
            $environment->addConverter(new TableConverter());

            return (new HtmlConverter($environment))->convert($html);
        }

        public static function htmlToText(string $html): string
        {
            return Html2Text::convert($html, ['ignore_errors' => true]);
        }

        public static function markdownToHtml(string $markdown): string
        {
            return (new \Parsedown())->text($markdown);
        }
    }
}