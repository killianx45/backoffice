<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HtmlSanitizerExtension extends AbstractExtension
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'ul', 'li', 'ol',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'a', 'img', 'div', 'span',
        'figure', 'figcaption', 'pre', 'code'
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'target', 'rel', 'title'],
        'img' => ['src', 'alt', 'width', 'height', 'class', 'style'],
        'figure' => ['class', 'style'],
        'div' => ['class', 'data-*'],
        'span' => ['class', 'style'],
        'pre' => ['class'],
        'code' => ['class'],
    ];

    private const ALLOWED_IMAGE_CLASSES = [
        'image',
        'image-style-side',
        'image-style-align-left',
        'image-style-align-right',
        'image-style-align-center',
        'image-style-block-align-center',
        'image-style-block-align-left',
        'image-style-block-align-right',
        'image_resized',
    ];

    public function getFilters(): array
    {
        return [
            new TwigFilter('safe_html', [$this, 'sanitizeHtml'], ['is_safe' => ['html']]),
        ];
    }

    public function sanitizeHtml(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div>' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $this->sanitizeNode($dom->documentElement);

        $result = '';
        $wrapper = $dom->getElementsByTagName('div')->item(0);
        if ($wrapper) {
            foreach ($wrapper->childNodes as $child) {
                $result .= $dom->saveHTML($child);
            }
        }

        return $result;
    }

    private function sanitizeNode(\DOMNode $node): void
    {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            /** @var \DOMElement $node */
            $tagName = strtolower($node->tagName);

            if (!in_array($tagName, self::ALLOWED_TAGS, true)) {
                $this->unwrapNode($node);
                return;
            }

            $this->sanitizeAttributes($node, $tagName);
        }

        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            $this->sanitizeNode($child);
        }
    }

    private function sanitizeAttributes(\DOMElement $element, string $tagName): void
    {
        $allowedAttrs = self::ALLOWED_ATTRIBUTES[$tagName] ?? [];
        $attributesToRemove = [];

        foreach ($element->attributes as $attr) {
            $attrName = $attr->name;
            $isAllowed = false;

            foreach ($allowedAttrs as $allowedAttr) {
                if ($allowedAttr === $attrName) {
                    $isAllowed = true;
                    break;
                }
                if (str_ends_with($allowedAttr, '*') && str_starts_with($attrName, rtrim($allowedAttr, '*'))) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                $attributesToRemove[] = $attrName;
            }
        }

        foreach ($attributesToRemove as $attrName) {
            $element->removeAttribute($attrName);
        }

        if (in_array($tagName, ['figure', 'img'], true) && $element->hasAttribute('class')) {
            $classes = explode(' ', $element->getAttribute('class'));
            $filteredClasses = array_filter($classes, function ($class) {
                $class = trim($class);
                foreach (self::ALLOWED_IMAGE_CLASSES as $allowedClass) {
                    if ($class === $allowedClass || str_starts_with($class, 'image')) {
                        return true;
                    }
                }
                return false;
            });

            if (empty($filteredClasses)) {
                $element->removeAttribute('class');
            } else {
                $element->setAttribute('class', implode(' ', $filteredClasses));
            }
        }

        if ($element->hasAttribute('style')) {
            $style = $element->getAttribute('style');
            $sanitizedStyle = $this->sanitizeStyle($style);
            if ($sanitizedStyle) {
                $element->setAttribute('style', $sanitizedStyle);
            } else {
                $element->removeAttribute('style');
            }
        }
    }

    private function sanitizeStyle(string $style): string
    {
        $allowedProperties = ['width', 'height', 'max-width', 'margin', 'margin-left', 'margin-right', 'float', 'text-align', 'aspect-ratio'];
        $declarations = explode(';', $style);
        $sanitized = [];

        foreach ($declarations as $declaration) {
            $declaration = trim($declaration);
            if (empty($declaration)) {
                continue;
            }

            $parts = explode(':', $declaration, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $property = trim(strtolower($parts[0]));
            $value = trim($parts[1]);

            if (in_array($property, $allowedProperties, true)) {
                if (preg_match('/^[\w\s%.,\-()\/]+$/', $value)) {
                    $sanitized[] = "$property: $value";
                }
            }
        }

        return implode('; ', $sanitized);
    }

    private function unwrapNode(\DOMNode $node): void
    {
        $parent = $node->parentNode;
        if ($parent === null) {
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }
}
