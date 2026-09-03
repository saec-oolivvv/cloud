<?php

declare(strict_types=1);

namespace Saec\Core;

class Translation
{
    private static ?self $instance = null;
    private string $lang = 'en';
    private array $translations = [];
    private array $fallback = [];

    private const AVAILABLE = [
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'dir' => 'ltr'],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'dir' => 'ltr'],
        'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪', 'dir' => 'ltr'],
        'es' => ['name' => 'Español', 'flag' => '🇪🇸', 'dir' => 'ltr'],
        'it' => ['name' => 'Italiano', 'flag' => '🇮🇹', 'dir' => 'ltr'],
        'pt' => ['name' => 'Português', 'flag' => '🇵🇹', 'dir' => 'ltr'],
        'nl' => ['name' => 'Nederlands', 'flag' => '🇳🇱', 'dir' => 'ltr'],
        'pl' => ['name' => 'Polski', 'flag' => '🇵🇱', 'dir' => 'ltr'],
        'ro' => ['name' => 'Română', 'flag' => '🇷🇴', 'dir' => 'ltr'],
        'cs' => ['name' => 'Čeština', 'flag' => '🇨🇿', 'dir' => 'ltr'],
        'sv' => ['name' => 'Svenska', 'flag' => '🇸🇪', 'dir' => 'ltr'],
        'el' => ['name' => 'Ελληνικά', 'flag' => '🇬🇷', 'dir' => 'ltr'],
        'hu' => ['name' => 'Magyar', 'flag' => '🇭🇺', 'dir' => 'ltr'],
        'bg' => ['name' => 'Български', 'flag' => '🇧🇬', 'dir' => 'ltr'],
        'da' => ['name' => 'Dansk', 'flag' => '🇩🇰', 'dir' => 'ltr'],
    ];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setLang(string $lang): void
    {
        if (!isset(self::AVAILABLE[$lang])) {
            $lang = 'en';
        }
        $this->lang = $lang;
        $this->load($lang);
        $_SESSION['lang'] = $lang;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public static function getAvailable(): array
    {
        return self::AVAILABLE;
    }

    public function getLangInfo(string $lang): ?array
    {
        return self::AVAILABLE[$lang] ?? null;
    }

    private function load(string $lang): void
    {
        $file = dirname(__DIR__, 1) . "/lang/{$lang}.php";
        $fallbackFile = dirname(__DIR__, 1) . "/lang/en.php";

        if (file_exists($fallbackFile)) {
            $this->fallback = require $fallbackFile;
        }

        if (file_exists($file)) {
            $this->translations = require $file;
        } else {
            $this->translations = $this->fallback;
        }
    }

    public function get(string $key, array $replace = []): string
    {
        $value = $this->resolve($key, $this->translations)
            ?? $this->resolve($key, $this->fallback)
            ?? $key;

        if (!empty($replace)) {
            foreach ($replace as $k => $v) {
                $value = str_replace("{{$k}}", $v, $value);
            }
        }

        return $value;
    }

    private function resolve(string $key, array $data): ?string
    {
        $keys = explode('.', $key);
        $current = $data;

        foreach ($keys as $k) {
            if (!isset($current[$k])) {
                return null;
            }
            $current = $current[$k];
        }

        return is_string($current) ? $current : null;
    }

    public function detectLang(): string
    {
        // 1. Session
        if (isset($_SESSION['lang']) && isset(self::AVAILABLE[$_SESSION['lang']])) {
            return $_SESSION['lang'];
        }

        // 2. Cookie
        if (isset($_COOKIE['lang']) && isset(self::AVAILABLE[$_COOKIE['lang']])) {
            return $_COOKIE['lang'];
        }

        // 3. Accept-Language header
        $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if ($accept) {
            $langs = explode(',', $accept);
            foreach ($langs as $l) {
                $code = strtolower(substr(trim($l), 0, 2));
                if (isset(self::AVAILABLE[$code])) {
                    return $code;
                }
            }
        }

        return 'en';
    }
}

// t() function loaded separately in index.php to avoid namespace issues
