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

// Merges $overrides into the current query params $params (a null value removes
// the key), drops anything empty, and returns a leading-? query string - or ''
// when nothing is left. Used to build filter/sort/pagination links that keep
// the rest of the current query string intact.
function query_with(array $params, array $overrides): string
{
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    $params = array_filter($params, fn ($v) => $v !== null && $v !== '');

    return $params ? '?' . http_build_query($params) : '';
}
