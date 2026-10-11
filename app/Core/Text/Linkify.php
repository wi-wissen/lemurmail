<?php

namespace App\Core\Text;

final class Linkify
{
    /**
     * Splits plain text into text and link segments. Only http:// and https:// URLs are links.
     * Trailing punctuation . , ; : ! ? ) ] ' " is not part of the URL (a ")" stays if the URL
     * contains a matching "("). The segments concatenated give back the input exactly.
     * No escaping happens here; the caller escapes values when rendering.
     *
     * @return list<array{type:'text'|'link', value:string}>
     */
    public static function segments(string $text): array
    {
        $segments = [];
        $position = 0;
        preg_match_all('~https?://[^\s<>"]+~', $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$raw, $offset]) {
            $url = self::trimTrailing($raw);
            if (preg_match('~^https?://.~', $url) !== 1) {
                continue;
            }
            if ($offset > $position) {
                $segments[] = ['type' => 'text', 'value' => substr($text, $position, $offset - $position)];
            }
            $segments[] = ['type' => 'link', 'value' => $url];
            $position = $offset + strlen($url);
        }

        if ($position < strlen($text)) {
            $segments[] = ['type' => 'text', 'value' => substr($text, $position)];
        }

        return $segments;
    }

    private static function trimTrailing(string $url): string
    {
        while ($url !== '') {
            $last = $url[-1];
            if ($last === ')' && substr_count($url, '(') >= substr_count($url, ')')) {
                break;
            }
            if (! in_array($last, ['.', ',', ';', ':', '!', '?', ')', ']', "'", '"'], true)) {
                break;
            }
            $url = substr($url, 0, -1);
        }

        return $url;
    }
}
