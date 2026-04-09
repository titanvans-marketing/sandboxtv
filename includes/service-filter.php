<?php
$filterAriaLabel = $filterAriaLabel ?? 'Filter services by model';
$filterTitle = $filterTitle ?? 'Browse Services';
$filterDescription = $filterDescription ?? 'Filter by van model and sort the list.';
$radioName = $radioName ?? 'vanModel-services';
$sortName = $sortName ?? 'serviceSort-services';
$showFloatingCartClass = $showFloatingCartClass ?? true;
$filterWrapperClass = $filterWrapperClass ?? 'service-model-filter';
$browserId = $browserId ?? 'services-main';

$sprinterIcon = $sprinterIcon ?? '/assets/images/filters/sprinter.png';
$transitIcon = $transitIcon ?? '/assets/images/filters/transit.png';
$promasterIcon = $promasterIcon ?? '/assets/images/filters/promaster.png';

$modelOptions = $modelOptions ?? [
    [
        'value' => 'Sprinter',
        'label' => 'Sprinter',
        'icon' => $sprinterIcon,
    ],
    [
        'value' => 'Transit',
        'label' => 'Transit',
        'icon' => $transitIcon,
    ],
    [
        'value' => 'ProMaster',
        'label' => 'ProMaster',
        'icon' => $promasterIcon,
    ],
];
?>

<div class="<?php echo htmlspecialchars($filterWrapperClass, ENT_QUOTES, 'UTF-8'); ?>"
    aria-label="<?php echo htmlspecialchars($filterAriaLabel, ENT_QUOTES, 'UTF-8'); ?>" data-service-filter-root
    data-service-browser-id="<?php echo htmlspecialchars($browserId, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="service-model-filter__inner">
        <div class="service-model-filter__form">

            <div class="service-model-filter__toolbar">
                <div class="service-model-filter__toolbar-panel">

                    <div class="service-model-filter__models-shell">
                        <fieldset class="service-model-filter__group service-model-filter__group--models">
                            <legend class="sr-only">Choose your van model</legend>

                            <div class="service-model-filter__pill-tray" data-model-pill-tray>
                                <?php foreach ($modelOptions as $model): ?>
                                    <label class="model-pill">
                                        <input type="radio"
                                            name="<?php echo htmlspecialchars($radioName, ENT_QUOTES, 'UTF-8'); ?>"
                                            value="<?php echo htmlspecialchars($model['value'], ENT_QUOTES, 'UTF-8'); ?>"
                                            class="model-pill__input" />

                                        <span class="model-pill__label">
                                            <span class="model-pill__media" aria-hidden="true">
                                                <img class="model-pill__icon"
                                                    src="<?php echo htmlspecialchars($model['icon'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    alt="" loading="lazy" />
                                            </span>

                                            <span class="model-pill__text">
                                                <?php echo htmlspecialchars($model['label'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    </div>

                    <div class="service-model-filter__toolbar-secondary">
                        <fieldset class="service-model-filter__group service-model-filter__group--sort">
                            <legend class="sr-only">Sort services</legend>

                            <div class="service-model-filter__sort-tray">
                                <label class="sort-pill">
                                    <input type="radio"
                                        name="<?php echo htmlspecialchars($sortName, ENT_QUOTES, 'UTF-8'); ?>"
                                        value="recent" class="sort-pill__input" checked />
                                    <span class="sort-pill__label">
                                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                                        <span>Recent</span>
                                    </span>
                                </label>

                                <label class="sort-pill">
                                    <input type="radio"
                                        name="<?php echo htmlspecialchars($sortName, ENT_QUOTES, 'UTF-8'); ?>"
                                        value="name" class="sort-pill__input" />
                                    <span class="sort-pill__label">
                                        <i class="fa-solid fa-arrow-down-a-z" aria-hidden="true"></i>
                                        <span>Name</span>
                                    </span>
                                </label>

                                <label class="sort-pill">
                                    <input type="radio"
                                        name="<?php echo htmlspecialchars($sortName, ENT_QUOTES, 'UTF-8'); ?>"
                                        value="popular" class="sort-pill__input" />
                                    <span class="sort-pill__label">
                                        <i class="fa-solid fa-fire" aria-hidden="true"></i>
                                        <span>Popular</span>
                                    </span>
                                </label>
                            </div>
                        </fieldset>
                    </div>

                </div>
            </div>

            <input type="hidden" class="vehicle-model" name="vehicleModel" value="" />
            <input type="hidden" class="service-sort" name="serviceSort" value="recent" />

        </div>
    </div>
</div>

<?php if ($showFloatingCartClass): ?>
    <?php require __DIR__ . '/../includes/floating-cart.php'; ?>
<?php endif; ?>