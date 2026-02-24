<?php

namespace BookSourceManager\Core;

class Config
{
    private array $config = [];

    public function __construct()
    {
        // Load default config or something
    }

    /**
     * Load configuration from a file.
     * @param string $filePath
     */
    public function load(string $filePath): void
    {
        if (file_exists($filePath)) {
            $this->config = require $filePath;
        }
    }

    /**
     * Get a configuration value.
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }
        $keys = explode('.', $key);
        $value = $this->config;
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }

    /**
     * Set a configuration value.
     * @param string|array $key
     * @param mixed $value
     */
    public function set(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->config = array_merge($this->config, $key);
            return;
        }
        $keys = explode('.', $key);
        $array = &$this->config;
        while (count($keys) > 1) {
            $k = array_shift($keys);
            if (!isset($array[$k]) || !is_array($array[$k])) {
                $array[$k] = [];
            }
            $array = &$array[$k];
        }
        $array[array_shift($keys)] = $value;
    }
}
