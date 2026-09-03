<?php

declare(strict_types=1);

namespace Saec\Core;

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "{$label} est requis";
        }
        return $this;
    }

    public function email(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} n'est pas un email valide";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): self
    {
        $label = $label ?: $field;
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = "{$label} doit contenir au moins {$min} caractères";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): self
    {
        $label = $label ?: $field;
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = "{$label} ne doit pas dépasser {$max} caractères";
        }
        return $this;
    }

    public function maxSize(string $field, int $maxBytes, string $label = ''): self
    {
        $label = $label ?: $field;
        if (!empty($this->data[$field]) && $this->data[$field]['size'] > $maxBytes) {
            $maxMb = round($maxBytes / 1048576, 1);
            $this->errors[$field] = "{$label} ne doit pas dépasser {$maxMb} MB";
        }
        return $this;
    }

    public function allowedMime(string $field, array $allowed, string $label = ''): self
    {
        $label = $label ?: $field;
        if (!empty($this->data[$field]['tmp_name'])) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($this->data[$field]['tmp_name']);
            if (!in_array($mime, $allowed, true)) {
                $this->errors[$field] = "{$label} n'est pas un type autorisé";
            }
        }
        return $this;
    }

    public function matches(string $field, string $other, string $label = ''): self
    {
        $label = $label ?: $field;
        if (($this->data[$field] ?? '') !== ($this->data[$other] ?? '')) {
            $this->errors[$field] = "{$label} ne correspond pas";
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
