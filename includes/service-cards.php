<?php
if (!isset($serviceCards) || !is_array($serviceCards)) {
    $serviceCards = [];
}

$cardsWrapperClass = $cardsWrapperClass ?? 'service-packages-grid';

/*
|--------------------------------------------------------------------------
| Layout modifier
|--------------------------------------------------------------------------
| Examples:
|   services page  -> service-packages-grid--services
|   upgrades page  -> service-packages-grid--upgrades
|   checkout page  -> service-packages-grid--checkout
*/
$cardsLayoutClass = $cardsLayoutClass ?? 'service-packages-grid--services';

$defaultButtonText = $defaultButtonText ?? 'Add to Cart';
$showEmptyState = $showEmptyState ?? true;
$emptyStateText = $emptyStateText ?? 'No services matched your filters.';
$browserId = $browserId ?? 'services-main';

$serviceCardCount = count($serviceCards);
$serviceCardCountClass = 'service-grid-count-' . min(max($serviceCardCount, 1), 8);

$cardsGridClass = trim(
    $cardsWrapperClass . ' ' . $cardsLayoutClass . ' ' . $serviceCardCountClass
);

/*
|--------------------------------------------------------------------------
| Shared defaults for this include instance
|--------------------------------------------------------------------------
| Example:
|   Services page  -> $cardTypeDefault = 'service'; $cardLabelDefault = 'Service';
|   Upgrades page  -> $cardTypeDefault = 'upgrade'; $cardLabelDefault = 'Upgrade';
*/
$cardTypeDefault = $cardTypeDefault ?? 'service';
$cardLabelDefault = $cardLabelDefault ?? ucfirst($cardTypeDefault);

/*
|--------------------------------------------------------------------------
| Reuse toggles
|--------------------------------------------------------------------------
| These allow this include to be reused in checkout without forcing
| add-to-cart buttons or the dynamic model badge behavior from catalog pages.
*/
$showCardButtons = $showCardButtons ?? true;
$showModelBadge = $showModelBadge ?? true;
$useDynamicModelBadge = $useDynamicModelBadge ?? true;

if (!function_exists('service_cards_stringify_models')) {
    function service_cards_stringify_models($models): string
    {
        if (is_array($models)) {
            $models = array_map(static fn($value) => trim((string) $value), $models);
            $models = array_filter($models, static fn($value) => $value !== '');
            return implode(',', $models);
        }

        return trim((string) $models);
    }
}
?>

<div class="service-results-meta" data-service-results-meta
    data-service-browser-id="<?php echo htmlspecialchars($browserId, ENT_QUOTES, 'UTF-8'); ?>">
    <p class="service-results-title" data-service-results-title
        data-service-browser-id="<?php echo htmlspecialchars($browserId, ENT_QUOTES, 'UTF-8'); ?>">
        Showing all results
    </p>
</div>

<div class="<?php echo htmlspecialchars($cardsGridClass, ENT_QUOTES, 'UTF-8'); ?>" data-service-grid
    data-service-count="<?php echo (int) $serviceCardCount; ?>"
    data-service-browser-id="<?php echo htmlspecialchars($browserId, ENT_QUOTES, 'UTF-8'); ?>">
    <?php foreach ($serviceCards as $card): ?>
    <?php
        $cardId = (string) ($card['id'] ?? '');
        $cardName = (string) ($card['name'] ?? $card['title'] ?? '');
        $cardImage = (string) ($card['image'] ?? $card['card_image'] ?? $card['cardImage'] ?? '');
        $cardAlt = (string) ($card['alt'] ?? $cardName);
        $cardDescription = (string) ($card['description'] ?? $card['excerpt'] ?? '');
        $cardTime = (string) ($card['duration'] ?? $card['time'] ?? $card['timeNeeded'] ?? $card['estimated_time'] ?? '');
        $cardPrice = (string) ($card['price'] ?? '');
        $cardModels = $card['filter_models'] ?? ($card['models'] ?? []);
        $cardModelsString = service_cards_stringify_models($cardModels);
        $cardButtonClass = (string) ($card['button_class'] ?? 'service-add');
        $cardButtonText = (string) ($card['button_text'] ?? $defaultButtonText);
        $cardBadge = (string) ($card['badge'] ?? '');
        $cardSubtitle = (string) ($card['subtitle'] ?? '');
        $cardRecent = (string) ($card['recent'] ?? '');
        $cardPopularity = $card['popularity'] ?? 0;
        $cardSearchTerms = (string) ($card['search_terms'] ?? '');
        $cardCardClass = (string) ($card['card_class'] ?? '');
        $cardShowButton = isset($card['show_button']) ? (bool) $card['show_button'] : (bool) $showCardButtons;
        $cardShowModelBadge = isset($card['show_model_badge']) ? (bool) $card['show_model_badge'] : (bool) $showModelBadge;
        $cardUseDynamicModelBadge = isset($card['use_dynamic_model_badge']) ? (bool) $card['use_dynamic_model_badge'] : (bool) $useDynamicModelBadge;
        $cardSelectedModel = trim((string) ($card['selected_model'] ?? ''));
        $cardModelBadgeValue = $cardSelectedModel !== '' ? $cardSelectedModel : 'None';

        $cardType = (string) (
            $card['type']
            ?? $card['service_type']
            ?? $cardTypeDefault
            ?? 'service'
        );

        $cardLabel = (string) (
            $card['label']
            ?? $card['service_label']
            ?? $cardLabelDefault
            ?? ucfirst($cardType)
        );

        $cardTypeNormalized = strtolower(trim((string) $cardType));
        $cardShowSecondaryCta = $cardTypeNormalized === 'service';

        $cardRequiresDate = isset($card['requires_date']) ? (bool) $card['requires_date'] : true;

        $cardSearchBlob = trim(implode(' ', array_filter([
            $cardName,
            $cardSubtitle,
            $cardDescription,
            is_array($cardModels) ? implode(' ', $cardModels) : $cardModelsString,
            $cardSearchTerms,
            $cardType,
            $cardLabel,
            $cardTime,
        ])));
    ?>
    <?php
$cardBadgeSlug = strtolower(trim((string) $cardBadge));
$cardBadgeSlug = preg_replace('/[^a-z0-9]+/', '-', $cardBadgeSlug);
$cardBadgeSlug = trim((string) $cardBadgeSlug, '-');

$cardBadgeClass = '';

if ($cardBadgeSlug !== '') {
    $cardBadgeClass = 'service-card-badge--' . $cardBadgeSlug;
} elseif ($cardType !== '') {
    $cardBadgeClass = 'service-card-badge--' . strtolower($cardType);
}
?>
    <article
        class="service-card service-options pars <?php echo htmlspecialchars($cardCardClass, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-card data-service-id="<?php echo htmlspecialchars($cardId, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-name="<?php echo htmlspecialchars($cardName, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-price="<?php echo htmlspecialchars($cardPrice, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-models="<?php echo htmlspecialchars($cardModelsString, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-recent="<?php echo htmlspecialchars($cardRecent, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-popularity="<?php echo htmlspecialchars((string) $cardPopularity, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-search="<?php echo htmlspecialchars($cardSearchBlob, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-duration="<?php echo htmlspecialchars($cardTime, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-type="<?php echo htmlspecialchars($cardType, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-label="<?php echo htmlspecialchars($cardLabel, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-description="<?php echo htmlspecialchars($cardDescription, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-image="<?php echo htmlspecialchars($cardImage, ENT_QUOTES, 'UTF-8'); ?>"
        data-service-requires-date="<?php echo $cardRequiresDate ? 'true' : 'false'; ?>">
        <?php if (!empty($cardBadge)): ?>
        <div class="service-card-badge <?php echo htmlspecialchars($cardBadgeClass, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($cardBadge, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php endif; ?>

        <div class="service-card-media">
            <div class="service-options-img-ctr">
                <img src="<?php echo htmlspecialchars($cardImage, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars($cardAlt, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" />
            </div>
        </div>

        <div class="service-card-body">
            <header class="service-card-header">
                <h3 class="service-card-title">
                    <?php echo htmlspecialchars($cardName, ENT_QUOTES, 'UTF-8'); ?>
                </h3>

                <?php if (!empty($cardSubtitle)): ?>
                <p class="service-card-subtitle">
                    <?php echo htmlspecialchars($cardSubtitle, ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <?php endif; ?>
            </header>

            <div class="service-desc service-card-description">
                <?php echo htmlspecialchars($cardDescription, ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <div class="service-card-meta">
                <?php if (!empty($cardTime)): ?>
                <div class="service-time service-card-pill">
                    <span class="service-card-pill__label">Estimated Time</span>
                    <span class="service-card-pill__value">
                        <?php echo htmlspecialchars($cardTime, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php if ($cardShowModelBadge): ?>
                <div class="service-model-badge service-card-pill">
                    <span class="service-card-pill__label">Selected Model</span>
                    <span class="service-model-badge__value service-card-pill__value"
                        <?php echo $cardUseDynamicModelBadge ? 'data-model-badge' : ''; ?>>
                        <?php echo htmlspecialchars($cardModelBadgeValue, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <div class="service-card-footer">
                <div class="pars-price service-card-price">
                    <?php echo htmlspecialchars($cardPrice, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <?php if ($cardShowButton): ?>
                <div class="service-card-actions">
                    <button type="button"
                        class="learn-more service-card-button <?php echo htmlspecialchars($cardButtonClass, ENT_QUOTES, 'UTF-8'); ?>"
                        data-button-state="default"
                        data-service-id="<?php echo htmlspecialchars($cardId, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-name="<?php echo htmlspecialchars($cardName, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-price="<?php echo htmlspecialchars($cardPrice, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-duration="<?php echo htmlspecialchars($cardTime, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-type="<?php echo htmlspecialchars($cardType, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-label="<?php echo htmlspecialchars($cardLabel, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-description="<?php echo htmlspecialchars($cardDescription, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-image="<?php echo htmlspecialchars($cardImage, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-requires-date="<?php echo $cardRequiresDate ? 'true' : 'false'; ?>">
                        <span class="service-card-button__label">
                            <?php echo htmlspecialchars($cardButtonText, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="service-card-button__icon" aria-hidden="true">
                            <i class="fa-solid fa-cart-plus fa-sm"></i>
                        </span>
                    </button>

                    <?php if ($cardShowSecondaryCta): ?>
                    <button type="button" class="service-card-button service-card-button--secondary service-buy-now"
                        data-service-id="<?php echo htmlspecialchars($cardId, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-name="<?php echo htmlspecialchars($cardName, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-price="<?php echo htmlspecialchars($cardPrice, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-duration="<?php echo htmlspecialchars($cardTime, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-type="<?php echo htmlspecialchars($cardType, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-label="<?php echo htmlspecialchars($cardLabel, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-description="<?php echo htmlspecialchars($cardDescription, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-image="<?php echo htmlspecialchars($cardImage, ENT_QUOTES, 'UTF-8'); ?>"
                        data-service-requires-date="<?php echo $cardRequiresDate ? 'true' : 'false'; ?>">
                        <span class="service-card-button__label">Schedule Now</span>
                        <span class="service-card-button__icon" aria-hidden="true">
                            <i class="fa-solid fa-calendar-plus fa-sm"></i>
                        </span>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php endforeach; ?>
</div>

<?php if ($showEmptyState): ?>
<p class="services-empty-state is-hidden" data-service-empty
    data-service-browser-id="<?php echo htmlspecialchars($browserId, ENT_QUOTES, 'UTF-8'); ?>" hidden>
    <?php echo htmlspecialchars($emptyStateText, ENT_QUOTES, 'UTF-8'); ?>
</p>
<?php endif; ?>