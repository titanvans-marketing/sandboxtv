<?php
/*
|--------------------------------------------------------------------------
| Head / SEO / Meta bootstrap
|--------------------------------------------------------------------------
| Supports both:
| 1) New array-based usage with $pageMeta = [...]
| 2) Legacy individual variables already used across the site
|--------------------------------------------------------------------------
*/

$pageMeta = $pageMeta ?? [];

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/
if (!function_exists('head_esc')) {
    function head_esc($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('head_is_assoc')) {
    function head_is_assoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}

if (!function_exists('head_arrayify')) {
    function head_arrayify($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }
}

if (!function_exists('head_merge_unique')) {
    function head_merge_unique(array ...$arrays): array
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

if (!function_exists('head_meta_value')) {
    function head_meta_value(array $pageMeta, string $path, $fallback = '')
    {
        $segments = explode('.', $path);
        $value = $pageMeta;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $fallback;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

/*
|--------------------------------------------------------------------------
| Legacy variable compatibility
|--------------------------------------------------------------------------
*/
$pageTitle = $pageTitle ?? 'Titan Vans';
$pageDescription = $pageDescription ?? 'Titan Vans';
$pageCanonical = $pageCanonical ?? '';
$pageOgTitle = $pageOgTitle ?? $pageTitle;
$pageOgDescription = $pageOgDescription ?? $pageDescription;
$pageOgUrl = $pageOgUrl ?? $pageCanonical;
$pageOgImage = $pageOgImage ?? '';
$pageRobots = $pageRobots ?? 'index, follow';
$pageBodyClass = $pageBodyClass ?? '';
$extraStyles = head_arrayify($extraStyles ?? []);
$extraHeadContent = $extraHeadContent ?? '';

/*
|--------------------------------------------------------------------------
| Defaults + normalized meta config
|--------------------------------------------------------------------------
*/
$metaDefaults = [
    'title' => $pageTitle,
    'description' => $pageDescription,
    'canonical' => $pageCanonical,
    'robots' => $pageRobots,
    'author' => 'Titan Vans',
    'keywords' => '',
    'theme_color' => '',
    'site_name' => 'Titan Vans',
    'locale' => 'en_US',
    'type' => 'website',
    'image' => '',
    'image_alt' => '',
    'twitter_card' => 'summary_large_image',
    'extra_styles' => $extraStyles,
    'extra_head_content' => $extraHeadContent,
    'head_scripts' => [],
    'preconnect' => [],
    'preload_styles' => [],
    'custom_meta' => [],
    'custom_links' => [],
    'json_ld' => [],
    'og' => [
        'title' => $pageOgTitle,
        'description' => $pageOgDescription,
        'url' => $pageOgUrl,
        'image' => $pageOgImage,
        'type' => 'website',
        'locale' => 'en_US',
        'site_name' => 'Titan Vans',
        'image_alt' => '',
    ],
    'twitter' => [
        'card' => 'summary_large_image',
        'title' => $pageOgTitle,
        'description' => $pageOgDescription,
        'image' => $pageOgImage,
    ],
];

$pageMeta = array_replace_recursive($metaDefaults, is_array($pageMeta) ? $pageMeta : []);

/*
|--------------------------------------------------------------------------
| Final normalized values
|--------------------------------------------------------------------------
*/
$metaTitle = trim((string) ($pageMeta['title'] ?? 'Titan Vans'));
$metaDescription = trim((string) ($pageMeta['description'] ?? 'Titan Vans'));
$metaCanonical = trim((string) ($pageMeta['canonical'] ?? ''));
$metaRobots = trim((string) ($pageMeta['robots'] ?? 'index, follow'));
$metaAuthor = trim((string) ($pageMeta['author'] ?? 'Titan Vans'));
$metaKeywords = trim((string) ($pageMeta['keywords'] ?? ''));
$metaThemeColor = trim((string) ($pageMeta['theme_color'] ?? ''));
$metaSiteName = trim((string) ($pageMeta['site_name'] ?? 'Titan Vans'));
$metaLocale = trim((string) ($pageMeta['locale'] ?? 'en_US'));
$metaType = trim((string) ($pageMeta['type'] ?? 'website'));
$ogTitle = trim((string) head_meta_value($pageMeta, 'og.title', $metaTitle));
$ogDescription = trim((string) head_meta_value($pageMeta, 'og.description', $metaDescription));
$ogUrl = trim((string) head_meta_value($pageMeta, 'og.url', $metaCanonical));
$ogImage = trim((string) head_meta_value($pageMeta, 'og.image', $pageMeta['image'] ?? ''));
$ogType = trim((string) head_meta_value($pageMeta, 'og.type', $metaType));
$ogLocale = trim((string) head_meta_value($pageMeta, 'og.locale', $metaLocale));
$ogSiteName = trim((string) head_meta_value($pageMeta, 'og.site_name', $metaSiteName));
$ogImageAlt = trim((string) head_meta_value($pageMeta, 'og.image_alt', $pageMeta['image_alt'] ?? ''));
$twitterCard = trim((string) head_meta_value($pageMeta, 'twitter.card', $pageMeta['twitter_card'] ?? 'summary_large_image'));
$twitterTitle = trim((string) head_meta_value($pageMeta, 'twitter.title', $ogTitle));
$twitterDescription = trim((string) head_meta_value($pageMeta, 'twitter.description', $ogDescription));
$twitterImage = trim((string) head_meta_value($pageMeta, 'twitter.image', $ogImage));
$metaExtraStyles = head_merge_unique(
    head_arrayify($pageMeta['extra_styles'] ?? []),
    $extraStyles
);
$metaExtraHeadContent = (string) ($pageMeta['extra_head_content'] ?? $extraHeadContent);
$metaHeadScripts = head_arrayify($pageMeta['head_scripts'] ?? []);
$metaPreconnect = head_merge_unique(head_arrayify($pageMeta['preconnect'] ?? []));
$metaPreloadStyles = head_merge_unique(head_arrayify($pageMeta['preload_styles'] ?? []));
$metaCustomMeta = head_arrayify($pageMeta['custom_meta'] ?? []);
$metaCustomLinks = head_arrayify($pageMeta['custom_links'] ?? []);
$metaJsonLd = head_arrayify($pageMeta['json_ld'] ?? []);
$shouldIndex = strtolower($metaRobots) !== 'noindex, nofollow';
?>

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title><?php echo head_esc($metaTitle); ?></title>

    <meta name="description" content="<?php echo head_esc($metaDescription); ?>" />
    <meta name="author" content="<?php echo head_esc($metaAuthor); ?>" />
    <meta name="robots" content="<?php echo head_esc($metaRobots); ?>" />

    <?php if ($metaKeywords !== ''): ?>
    <meta name="keywords" content="<?php echo head_esc($metaKeywords); ?>" />
    <?php endif; ?>

    <?php if ($metaThemeColor !== ''): ?>
    <meta name="theme-color" content="<?php echo head_esc($metaThemeColor); ?>" />
    <?php endif; ?>

    <?php if ($shouldIndex): ?>
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
    <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
    <?php endif; ?>

    <?php if ($metaCanonical !== ''): ?>
    <link rel="canonical" href="<?php echo head_esc($metaCanonical); ?>" />
    <?php endif; ?>

    <link rel="shortcut icon" type="image/svg+xml" href="/assets/images/titan-vans-logo-no-text.svg" />
    <link rel="icon" href="https://www.titanvans.com/titan-vans.ico" />

    <?php foreach ($metaPreconnect as $href): ?>
    <link rel="preconnect" href="<?php echo head_esc($href); ?>" crossorigin />
    <?php endforeach; ?>

    <?php foreach ($metaPreloadStyles as $href): ?>
    <link rel="preload" href="<?php echo head_esc($href); ?>" as="style" />
    <?php endforeach; ?>

    <!-- Start Cookies -->
    <link rel="stylesheet" href="/src/styles/cookie-consent.css" />

    <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }

    (function() {
        const STORAGE_KEY = "site_cookie_consent_v1";
        const CONSENT_VERSION = "2026-03-11";
        const defaultConsent = {
            ad_storage: "denied",
            ad_user_data: "denied",
            ad_personalization: "denied",
            analytics_storage: "denied",
            functionality_storage: "denied",
            personalization_storage: "denied",
            security_storage: "granted",
            wait_for_update: 500
        };

        let effectiveConsent = {
            ...defaultConsent
        };

        try {
            const raw = localStorage.getItem(STORAGE_KEY);

            if (raw) {
                const saved = JSON.parse(raw);

                if (
                    saved &&
                    typeof saved === "object" &&
                    saved.consentVersion === CONSENT_VERSION
                ) {
                    effectiveConsent = {
                        ad_storage: saved.marketing ? "granted" : "denied",
                        ad_user_data: saved.marketing ? "granted" : "denied",
                        ad_personalization: saved.marketing ? "granted" : "denied",
                        analytics_storage: saved.analytics ? "granted" : "denied",
                        functionality_storage: saved.preferences ? "granted" : "denied",
                        personalization_storage: saved.preferences ? "granted" : "denied",
                        security_storage: "granted",
                        wait_for_update: 500
                    };
                }
            }
        } catch (e) {
            // Keep default denied consent
        }

        gtag("consent", "default", effectiveConsent);
    })();
    </script>

    <script defer src="/src/js/cookie-consent.js"></script>
    <!-- End Cookies -->

    <!-- Google Tag Manager -->
    <script>
    (function(w, d, s, l, i) {
        w[l] = w[l] || [];
        w[l].push({
            "gtm.start": new Date().getTime(),
            event: "gtm.js",
        });
        var f = d.getElementsByTagName(s)[0],
            j = d.createElement(s),
            dl = l != "dataLayer" ? "&l=" + l : "";
        j.async = true;
        j.src = "https://www.googletagmanager.com/gtm.js?id=" + i + dl;
        f.parentNode.insertBefore(j, f);
    })(window, document, "script", "dataLayer", "GTM-589BKPV");
    </script>
    <!-- End Google Tag Manager -->

    <?php if ($shouldIndex): ?>
    <meta property="og:locale" content="<?php echo head_esc($ogLocale); ?>" />
    <meta property="og:type" content="<?php echo head_esc($ogType); ?>" />
    <meta property="og:site_name" content="<?php echo head_esc($ogSiteName); ?>" />
    <meta property="og:title" content="<?php echo head_esc($ogTitle); ?>" />
    <meta property="og:description" content="<?php echo head_esc($ogDescription); ?>" />

    <?php if ($ogUrl !== ''): ?>
    <meta property="og:url" content="<?php echo head_esc($ogUrl); ?>" />
    <?php endif; ?>

    <?php if ($ogImage !== ''): ?>
    <meta property="og:image" content="<?php echo head_esc($ogImage); ?>" />
    <?php endif; ?>

    <?php if ($ogImageAlt !== ''): ?>
    <meta property="og:image:alt" content="<?php echo head_esc($ogImageAlt); ?>" />
    <?php endif; ?>

    <meta name="twitter:card" content="<?php echo head_esc($twitterCard); ?>" />
    <meta name="twitter:title" content="<?php echo head_esc($twitterTitle); ?>" />
    <meta name="twitter:description" content="<?php echo head_esc($twitterDescription); ?>" />

    <?php if ($twitterImage !== ''): ?>
    <meta name="twitter:image" content="<?php echo head_esc($twitterImage); ?>" />
    <?php endif; ?>
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@100;200;300;400&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

    <link rel="stylesheet" href="/src/styles/theme-dark.css">
    <link rel="stylesheet" href="/src/styles/nav.css">
    <link rel="stylesheet" href="/src/styles/footer.css">
    <link rel="stylesheet" href="/src/styles/portal.css">
    <link rel="stylesheet" href="/src/styles/m072224.css" />

    <?php foreach ($metaExtraStyles as $href): ?>
    <link rel="stylesheet" href="<?php echo head_esc($href); ?>" />
    <?php endforeach; ?>

    <?php foreach ($metaCustomLinks as $link): ?>
    <?php if (is_array($link) && !empty($link['rel']) && !empty($link['href'])): ?>
    <link rel="<?php echo head_esc($link['rel']); ?>" href="<?php echo head_esc($link['href']); ?>"
        <?php echo !empty($link['type']) ? 'type="' . head_esc($link['type']) . '"' : ''; ?>
        <?php echo !empty($link['sizes']) ? 'sizes="' . head_esc($link['sizes']) . '"' : ''; ?>
        <?php echo !empty($link['media']) ? 'media="' . head_esc($link['media']) . '"' : ''; ?>
        <?php echo !empty($link['crossorigin']) ? 'crossorigin="' . head_esc($link['crossorigin']) . '"' : ''; ?> />
    <?php endif; ?>
    <?php endforeach; ?>

    <?php foreach ($metaCustomMeta as $meta): ?>
    <?php if (is_array($meta)): ?>
    <?php
            $attrParts = [];
            foreach ($meta as $attr => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $attrParts[] = head_esc($attr) . '="' . head_esc($value) . '"';
            }
            ?>
    <?php if (!empty($attrParts)): ?>
    <meta <?php echo implode(' ', $attrParts); ?> />
    <?php endif; ?>
    <?php endif; ?>
    <?php endforeach; ?>

    <?php foreach ($metaHeadScripts as $script): ?>
    <?php if (is_string($script) && $script !== ''): ?>
    <script src="<?php echo head_esc($script); ?>" defer></script>
    <?php elseif (is_array($script) && !empty($script['src'])): ?>
    <script src="<?php echo head_esc($script['src']); ?>" <?php echo !empty($script['defer']) ? 'defer' : ''; ?>
        <?php echo !empty($script['async']) ? 'async' : ''; ?>
        <?php echo !empty($script['type']) ? 'type="' . head_esc($script['type']) . '"' : ''; ?>
        <?php echo !empty($script['crossorigin']) ? 'crossorigin="' . head_esc($script['crossorigin']) . '"' : ''; ?>>
    </script>
    <?php endif; ?>
    <?php endforeach; ?>

    <?php foreach ($metaJsonLd as $schema): ?>
    <?php if (is_array($schema) && !empty($schema)): ?>
    <script type="application/ld+json">
    <?php
        echo json_encode(
            $schema,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );
    ?>
    </script>
    <?php endif; ?>
    <?php endforeach; ?>

    <?php echo $metaExtraHeadContent; ?>
</head>