<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class BrevoApiMailer
{
    public function __construct(
        #[Autowire('%env(string:BREVO_API_KEY)%')]
        private readonly string $apiKey,
        #[Autowire('%env(string:MAILER_FROM_EMAIL)%')]
        private readonly string $fromEmail,
        #[Autowire('%env(string:MAILER_FROM_NAME)%')]
        private readonly string $fromName,
    ) {
    }

    public function sendResetCode(string $toEmail, string $toName, string $code, string $verifyCodeUrl, string $resetUrl): void
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('La cle API Brevo est absente.');
        }

        $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeVerifyUrl = htmlspecialchars($verifyCodeUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeResetUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $payload = [
            'sender' => [
                'name' => $this->fromName,
                'email' => $this->fromEmail,
            ],
            'to' => [[
                'email' => $toEmail,
                'name' => $toName,
            ]],
            'subject' => 'Code de reinitialisation de votre mot de passe',
            'textContent' => "Bonjour {$toName},\n\n"
                . "Votre code de verification IDENE PARFUM est : {$code}\n"
                . "Saisissez-le ici : {$verifyCodeUrl}\n"
                . "Lien direct de reinitialisation : {$resetUrl}\n\n"
                . "Ce code expire dans 60 minutes.",
            'htmlContent' => '<html><body style="font-family:Arial,sans-serif;line-height:1.6;color:#1f2937;">'
                . '<h2>Code de reinitialisation</h2>'
                . "<p>Bonjour {$safeName},</p>"
                . '<p>Utilisez ce code pour reinitialiser votre mot de passe IDENE PARFUM :</p>'
                . '<p style="font-size:28px;font-weight:700;letter-spacing:8px;">' . $safeCode . '</p>'
                . '<p><a href="' . $safeVerifyUrl . '" style="display:inline-block;padding:12px 20px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;">Saisir le code</a></p>'
                . '<p><a href="' . $safeResetUrl . '" style="display:inline-block;padding:12px 20px;background:#111827;color:#ffffff;text-decoration:none;border-radius:6px;">Lien direct de reinitialisation</a></p>'
                . '<p>Ce code expire dans 60 minutes.</p>'
                . '</body></html>',
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'api-key: ' . $this->apiKey,
                ]),
                'content' => json_encode($payload, JSON_THROW_ON_ERROR),
                'ignore_errors' => true,
                'timeout' => 20,
            ],
        ]);

        $response = @file_get_contents('https://api.brevo.com/v3/smtp/email', false, $context);
        $headers = $http_response_header ?? [];
        $statusLine = is_array($headers) && $headers !== [] ? (string) $headers[0] : '';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        $statusCode = isset($matches[1]) ? (int) $matches[1] : 0;

        if ($statusCode < 200 || $statusCode >= 300) {
            $details = is_string($response) && $response !== '' ? $response : 'Reponse Brevo vide.';
            throw new \RuntimeException('Envoi Brevo impossible. ' . $details);
        }
    }
}
