<?php

namespace App\Services;

use DOMDocument;
use DOMElement;

class RichTextSanitizer
{
    private const TAGS = '<p><br><strong><b><em><i><u><s><ul><ol><li><blockquote><h2><h3><h4><a>';

    public function sanitize(string $html): string
    {
        $html = strip_tags($html, self::TAGS);
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach ($document->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }
            foreach (iterator_to_array($element->attributes) as $attribute) {
                if ($element->tagName !== 'a' || $attribute->name !== 'href') {
                    $element->removeAttribute($attribute->name);

                    continue;
                }
                if (! preg_match('/^(https?:\/\/|mailto:|\/|#)/i', $attribute->value)) {
                    $element->removeAttribute('href');
                }
            }
        }

        $container = $document->getElementsByTagName('div')->item(0);
        $result = '';
        if ($container) {
            foreach ($container->childNodes as $child) {
                $result .= $document->saveHTML($child);
            }
        }

        return trim($result);
    }
}
