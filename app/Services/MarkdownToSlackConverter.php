<?php

namespace App\Services;

class MarkdownToSlackConverter
{
    public function convert(string $markdown): string
    {
        $text = $markdown;

        // Strip images first: ![alt](url)
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '', $text);

        // Convert links: [text](url) → <url|text>
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<$2|$1>', $text);

        // Strip HTML tags (but not Slack-formatted <url|text> links)
        $text = preg_replace('/<(?![a-z]+:\/\/)[^>]+>/', '', $text);

        // Convert bold: **text** or __text__ → *text*
        $text = preg_replace('/\*\*(.+?)\*\*/', '*$1*', $text);
        $text = preg_replace('/__(.+?)__/', '*$1*', $text);

        // Convert headings: # Heading → *Heading*
        $text = preg_replace('/^#{1,6}\s+(.+)$/m', '*$1*', $text);

        // Convert code blocks: ```lang\ncode\n``` → ```code```
        $text = preg_replace('/```[a-zA-Z]*\n/', "```\n", $text);

        // Convert horizontal rules
        $text = preg_replace('/^[-*_]{3,}$/m', '---', $text);

        // Convert unordered list markers to bullet
        $text = preg_replace('/^[\t ]*[-*+]\s+/m', '  - ', $text);

        // Clean up excessive blank lines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}
