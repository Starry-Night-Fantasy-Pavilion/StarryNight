<?php

namespace Core;

class ThemeBase
{
    protected $themeId;
    protected $themePath;
    protected $config = [];

    public function __construct(string $themeId, string $themePath, array $config = [])
    {
        $this->themeId = $themeId;
        $this->themePath = $themePath;
        $this->config = $config;
    }

    public function getId(): string
    {
        return $this->themeId;
    }

    public function getThemePath(): string
    {
        return $this->themePath;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getConfig(): array
    {
        $defaults = [];
        $form = $this->getConfigForm();
        foreach ($form as $key => $field) {
            if (is_array($field) && array_key_exists('default', $field)) {
                $defaults[$key] = $field['default'];
            }
        }
        return array_merge($defaults, $this->config);
    }

    public function getConfigForm(): array
    {
        return [];
    }

    public function getIconSvg(string $iconName, array $attributes = []): string
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $iconName)) {
            return '';
        }

        $iconPath = $this->themePath . '/assets/icons/' . $iconName . '.svg';
        if (!is_readable($iconPath)) {
            return '';
        }

        $svgContent = file_get_contents($iconPath);
        if (!is_string($svgContent) || $svgContent === '') {
            return '';
        }

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($svgContent);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$loaded) {
            return '';
        }

        $svg = $dom->getElementsByTagName('svg')->item(0);
        if (!$svg) {
            return '';
        }

        $defaultAttributes = [
            'width' => '18',
            'height' => '18',
            'fill' => 'none',
            'stroke' => 'currentColor',
            'stroke-width' => '2',
            'stroke-linecap' => 'round',
            'stroke-linejoin' => 'round',
        ];

        $finalAttributes = array_merge($defaultAttributes, $attributes);
        foreach ($finalAttributes as $key => $value) {
            $svg->setAttribute($key, (string) $value);
        }

        return $dom->saveHTML($svg);
    }
}
