<?php
/**
 * Handles the support form at the bottom of index.html.
 * No dependencies — plain mail(), matching the rest of the site (no build step, no JS).
 */

declare(strict_types=1);

$to = 'support@kneadbread.app';
$successUrl = '/thanks.html';
$errorUrl = '/index.html#help';

function redirect(string $url): void {
    header('Location: ' . $url, true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect($errorUrl);
}

// Honeypot: a field real visitors never see or fill in (see support-form in
// _includes/help.html). Bots that fill every field trip it; pretend success
// so they don't learn to skip it.
if (trim((string)($_POST['website'] ?? '')) !== '') {
    redirect($successUrl);
}

$email = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect($errorUrl);
}

// filter_var already rejects control characters in a valid address, but strip
// them too as defense in depth since $email is echoed into a mail header below.
$email = str_replace(["\r", "\n"], '', $email);

if (mb_strlen($message) > 5000) {
    $message = mb_substr($message, 0, 5000);
}

$subject = 'Knead support request';
$body = "New message from the Knead support form:\n\n"
    . "From: {$email}\n"
    . "Sent: " . date('Y-m-d H:i:s T') . "\n\n"
    . $message . "\n";

$headers = [
    'From: Knead Support Form <no-reply@kneadbread.app>',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=UTF-8',
];

$sent = mail($to, $subject, $body, implode("\r\n", $headers));

redirect($sent ? $successUrl : $errorUrl);
