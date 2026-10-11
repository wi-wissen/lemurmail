<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use HTMLPurifier;
use HTMLPurifier_Config;
use HTMLPurifier_URIScheme;
use HTMLPurifier_URISchemeRegistry;
use Illuminate\Support\Facades\File;

/**
 * Makes mail HTML safe for the sandboxed inbox frame. HTMLPurifier allow-lists elements and
 * attributes; the URL policy (links only http/https/mailto, images only inline data URIs)
 * is enforced afterwards on the DOM, because it depends on the element, not on the scheme alone.
 */
final class HtmlSanitizer
{
    private const ALLOWED_ELEMENTS = 'p,br,b,strong,i,em,u,s,small,sub,sup,span,div,blockquote,pre,code,hr,'
        .'h1,h2,h3,h4,h5,h6,ul,ol,li,table,thead,tbody,tfoot,tr,th,td,caption,'
        .'a[href],img[src|alt|width|height]';

    private const CONTENT_SECURITY_POLICY = "default-src 'none'; style-src 'unsafe-inline'; img-src data:";

    private ?HTMLPurifier $purifier = null;

    public function clean(string $html): string
    {
        if ($html === '') {
            return '';
        }

        return $this->enforceUrlPolicy($this->purifier()->purify($html));
    }

    /** Wraps an already cleaned body into a complete document for the sandboxed iframe. */
    public function document(string $cleanBody): string
    {
        $csp = self::CONTENT_SECURITY_POLICY;

        return <<<HTML
        <!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="{$csp}"><base target="_blank"><style>body{font-family:system-ui,sans-serif;color:#16211D;word-wrap:break-word;overflow-wrap:anywhere;margin:12px}img{max-width:100%;height:auto}</style></head><body>{$cleanBody}</body></html>
        HTML;
    }

    private function purifier(): HTMLPurifier
    {
        if ($this->purifier === null) {
            $cachePath = storage_path('framework/cache/htmlpurifier');
            File::ensureDirectoryExists($cachePath);

            $config = HTMLPurifier_Config::createDefault();
            $config->set('Cache.SerializerPath', $cachePath);
            $config->set('HTML.Allowed', self::ALLOWED_ELEMENTS);
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'data' => true]);

            HTMLPurifier_URISchemeRegistry::instance()->register('data', self::inlineDataScheme());

            $this->purifier = new HTMLPurifier($config);
        }

        return $this->purifier;
    }

    /**
     * HTMLPurifier's own data: check decodes the image and drops anything it cannot parse. The URL
     * policy in enforceUrlPolicy() already admits data: URIs only as image sources, so the value is
     * passed through unchanged here.
     */
    private static function inlineDataScheme(): HTMLPurifier_URIScheme
    {
        return new class extends HTMLPurifier_URIScheme
        {
            public $browsable = true;

            public $may_omit_host = true;

            public function doValidate(&$uri, $config, $context): bool
            {
                return true;
            }
        };
    }

    /** Drops remote images and non-web links; marks every link as noopener noreferrer. */
    private function enforceUrlPolicy(string $html): string
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $dom->getElementsByTagName('body')->item(0);

        foreach (iterator_to_array($body->getElementsByTagName('img')) as $image) {
            if (! preg_match('~^data:image/~i', trim($image->getAttribute('src')))) {
                $image->parentNode->removeChild($image);
            }
        }

        foreach (iterator_to_array($body->getElementsByTagName('a')) as $link) {
            /** @var DOMElement $link */
            if (preg_match('~^(https?:|mailto:)~i', trim($link->getAttribute('href')))) {
                $link->setAttribute('rel', 'noopener noreferrer');
            } else {
                $link->removeAttribute('href');
            }
        }

        $clean = '';
        foreach ($body->childNodes as $node) {
            $clean .= $dom->saveHTML($node);
        }

        return $clean;
    }
}
