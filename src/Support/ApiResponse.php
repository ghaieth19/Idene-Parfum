<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponse
{
    public static function ok(array $data = [], int $status = 200): JsonResponse
    {
        return new JsonResponse($data === [] ? ['ok' => true] : $data, $status);
    }

    public static function error(string $message, int $status, array $extra = []): JsonResponse
    {
        return new JsonResponse(['error' => $message] + $extra, $status);
    }

    public static function validation(array $errors): JsonResponse
    {
        $errors = array_values(array_filter($errors));

        return self::error($errors[0] ?? 'Validation invalide.', 422, ['errors' => $errors]);
    }
}
