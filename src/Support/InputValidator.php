<?php

namespace App\Support;

final class InputValidator
{
    public function required(string $value, string $label, int $max = 255): ?string
    {
        if ($value === '') {
            return $label . ' obligatoire.';
        }
        if (mb_strlen($value) > $max) {
            return $label . ' trop long.';
        }

        return null;
    }

    public function name(string $value, string $label): ?string
    {
        if ($value === '') {
            return $label . ' obligatoire.';
        }
        if (mb_strlen($value) < 2) {
            return $label . ' trop court.';
        }
        if (mb_strlen($value) > 100) {
            return $label . ' trop long.';
        }

        return null;
    }

    public function phone(string $value, bool $required = true): ?string
    {
        if ($value === '') {
            return $required ? 'Telephone obligatoire.' : null;
        }
        if (!preg_match('/^\+?[0-9 ]{8,20}$/', $value)) {
            return 'Telephone invalide.';
        }

        return null;
    }

    public function email(string $value, bool $required = true): ?string
    {
        if ($value === '') {
            return $required ? 'Email obligatoire.' : null;
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'Email invalide.';
        }

        return null;
    }

    public function password(string $value, int $min = 8): ?string
    {
        if ($value === '') {
            return 'Mot de passe obligatoire.';
        }
        if (mb_strlen($value) < $min) {
            return 'Le mot de passe doit contenir au moins ' . $min . ' caracteres.';
        }

        return null;
    }

    public function date(string $value, string $label): ?string
    {
        if ($value === '') {
            return $label . ' obligatoire.';
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return $label . ' invalide.';
        }

        return null;
    }

    public function allowedValue(string $value, array $allowed, string $message): ?string
    {
        if (!in_array($value, $allowed, true)) {
            return $message;
        }

        return null;
    }
}
