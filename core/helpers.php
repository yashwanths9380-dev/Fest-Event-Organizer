<?php

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Builds "/index.php?route=X" for the router. If $route itself carries a
 * query string (e.g. "participant/events/show?id=4"), it's split so the
 * embedded "?" doesn't collide with the outer one -- otherwise
 * $_GET['route'] would literally contain "?id=4" and never match a
 * registered route (silent 404 on every redirect-with-id flow).
 */
function redirect(string $route): void
{
    if (str_contains($route, '?')) {
        [$path, $query] = explode('?', $route, 2);
        header('Location: /index.php?route=' . $path . '&' . $query);
    } else {
        header('Location: /index.php?route=' . $route);
    }
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function flash_get(string $type): ?string
{
    if (empty($_SESSION['flash'][$type])) {
        return null;
    }
    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);
    return $message;
}

function old(array $data, string $key, string $default = ''): string
{
    return e($data[$key] ?? $default);
}

function slugify(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : bin2hex(random_bytes(4));
}

function format_datetime(?string $datetime): string
{
    if (empty($datetime)) {
        return '-';
    }
    $ts = strtotime($datetime);
    return $ts === false ? '-' : date('d M Y, h:i A', $ts);
}

function format_date(?string $date): string
{
    if (empty($date)) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts === false ? '-' : date('d M Y', $ts);
}

function generate_epass_code(): string
{
    $groups = [];
    for ($i = 0; $i < 2; $i++) {
        $groups[] = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    }
    return 'FEST-' . implode('-', $groups);
}
