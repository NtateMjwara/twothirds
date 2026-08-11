<?php

// Escapes output for safe display - use this around every variable printed in a view
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

// Prints a hidden CSRF field, generating the token on first call
function csrf_field(): string
{
    $_SESSION['_csrf'] = $_SESSION['_csrf'] ?? bin2hex(random_bytes(32));
    return '<input type="hidden" name="_csrf" value="' . $_SESSION['_csrf'] . '">';
}
