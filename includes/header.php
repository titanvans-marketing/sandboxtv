<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../includes/csrf.php';

/*
|--------------------------------------------------------------------------
| Small helpers
|--------------------------------------------------------------------------
*/
if (!function_exists('layout_arrayify')) {
    function layout_arrayify($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }
}

if (!function_exists('layout_merge_unique')) {
    function layout_merge_unique(array ...$arrays): array
    {
        $merged = [];

        foreach ($arrays as $array) {
            foreach ($array as $item) {
                if ($item === null || $item === '') {
                    continue;
                }

                $merged[] = $item;
            }
        }

        return array_values(array_unique($merged));
    }
}

/*
|--------------------------------------------------------------------------
| Page meta bootstrap
|--------------------------------------------------------------------------
| head.php is now the source of truth for SEO/meta rendering.
| This file only prepares pageMeta + layout context.
|--------------------------------------------------------------------------
*/
$pageMeta = $pageMeta ?? [];

if (!is_array($pageMeta)) {
    $pageMeta = [];
}

/*
|--------------------------------------------------------------------------
| Backward compatibility for older pages still passing standalone vars
|--------------------------------------------------------------------------
*/
if (!isset($pageMeta['title']) && isset($pageTitle)) {
    $pageMeta['title'] = $pageTitle;
}

if (!isset($pageMeta['description']) && isset($pageDescription)) {
    $pageMeta['description'] = $pageDescription;
}

if (!isset($pageMeta['canonical']) && isset($pageCanonical)) {
    $pageMeta['canonical'] = $pageCanonical;
}

if (!isset($pageMeta['robots']) && isset($pageRobots)) {
    $pageMeta['robots'] = $pageRobots;
}

if (!isset($pageMeta['extra_head_content']) && isset($extraHeadContent)) {
    $pageMeta['extra_head_content'] = $extraHeadContent;
}

if (!isset($pageMeta['og']) || !is_array($pageMeta['og'])) {
    $pageMeta['og'] = [];
}

if (!isset($pageMeta['og']['title']) && isset($pageOgTitle)) {
    $pageMeta['og']['title'] = $pageOgTitle;
}

if (!isset($pageMeta['og']['description']) && isset($pageOgDescription)) {
    $pageMeta['og']['description'] = $pageOgDescription;
}

if (!isset($pageMeta['og']['url']) && isset($pageOgUrl)) {
    $pageMeta['og']['url'] = $pageOgUrl;
}

if (!isset($pageMeta['og']['image']) && isset($pageOgImage)) {
    $pageMeta['og']['image'] = $pageOgImage;
}

/*
|--------------------------------------------------------------------------
| Layout defaults
|--------------------------------------------------------------------------
*/
$pageMeta['lang'] = trim((string) ($pageMeta['lang'] ?? 'en'));
$pageMeta['body_class'] = trim((string) ($pageMeta['body_class'] ?? ($pageBodyClass ?? '')));
$pageMeta['nav_context'] = trim((string) ($pageMeta['nav_context'] ?? ($navContext ?? 'portal')));
$pageMeta['footer_context'] = trim((string) ($pageMeta['footer_context'] ?? ($footerContext ?? 'portal')));
$pageMeta['nav_type'] = trim((string) ($pageMeta['nav_type'] ?? ($navType ?? 'guest')));
$pageMeta['active_page'] = trim((string) ($pageMeta['active_page'] ?? ($activePage ?? '')));

/*
|--------------------------------------------------------------------------
| Merge styles from both legacy and new config
|--------------------------------------------------------------------------
*/
$legacyExtraStyles = layout_arrayify($extraStyles ?? []);
$pageMetaExtraStyles = layout_arrayify($pageMeta['extra_styles'] ?? []);
$pageMeta['extra_styles'] = layout_merge_unique(
    $pageMetaExtraStyles,
    $legacyExtraStyles,
    [
        '/src/styles/service-t.css'
    ]
);

/*
|--------------------------------------------------------------------------
| Legacy vars still exposed for other include files if needed
|--------------------------------------------------------------------------
*/
$pageTitle = $pageMeta['title'] ?? 'Titan Vans Account Portal';
$pageDescription = $pageMeta['description'] ?? 'Titan Vans Account Portal';
$pageCanonical = $pageMeta['canonical'] ?? '';
$pageRobots = $pageMeta['robots'] ?? 'index, follow';
$pageBodyClass = $pageMeta['body_class'] ?? '';
$extraStyles = $pageMeta['extra_styles'];
$extraHeadContent = $pageMeta['extra_head_content'] ?? '';
$navContext = $pageMeta['nav_context'];
$footerContext = $pageMeta['footer_context'];
$navType = $pageMeta['nav_type'];
$activePage = $pageMeta['active_page'];

/*
|--------------------------------------------------------------------------
| Session / user display data for nav + footer
|--------------------------------------------------------------------------
*/
$isLoggedIn = !empty($_SESSION['user_id']);
$displayFirstName = trim((string) ($_SESSION['user_first_name'] ?? ''));
$displayLastName = trim((string) ($_SESSION['user_last_name'] ?? ''));
$displayEmail = trim((string) ($_SESSION['user_email'] ?? ''));
$displayName = trim($displayFirstName . ' ' . $displayLastName);

?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($pageMeta['lang'], ENT_QUOTES, 'UTF-8'); ?>">

<?php require __DIR__ . '/../includes/head.php'; ?>

<body class="<?php echo htmlspecialchars($pageBodyClass, ENT_QUOTES, 'UTF-8'); ?>">
    <!-- Google Tag Manager (noscript) -->
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-589BKPV" height="0" width="0"
            style="display:none;visibility:hidden"></iframe>
    </noscript>
    <!-- End Google Tag Manager (noscript) -->

    <?php require __DIR__ . '/../includes/nav.php'; ?>