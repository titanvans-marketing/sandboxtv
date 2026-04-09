<?php
$filterAriaLabel = $filterAriaLabel ?? 'Filter upgrades by model';
$filterTitle = $filterTitle ?? 'Browse Upgrades';
$filterDescription = $filterDescription ?? 'Filter by vehicle model, sort the list, or search for a specific upgrade.';
$radioName = $radioName ?? 'vanModel-upgrades';
$sortName = $sortName ?? 'serviceSort-upgrades';
$showFloatingCartClass = $showFloatingCartClass ?? true;
$filterWrapperClass = $filterWrapperClass ?? 'service-model-filter';
$browserId = $browserId ?? 'upgrades-main';

$sprinterIcon = $sprinterIcon ?? '/assets/images/filters/sprinter.png';
$transitIcon = $transitIcon ?? '/assets/images/filters/transit.png';
$promasterIcon = $promasterIcon ?? '/assets/images/filters/promaster.png';

$overlandIcon = $overlandIcon ?? '/assets/images/filters/sprinter.png';
$ekkoIcon = $ekkoIcon ?? '/assets/images/filters/transit.png';
$revelIcon = $revelIcon ?? '/assets/images/filters/promaster.png';
$storytellerIcon = $storytellerIcon ?? '/assets/images/filters/sprinter.png';

$models = $models ?? [
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
  [
    'value' => 'Overland',
    'label' => 'Overland',
    'icon' => $overlandIcon,
  ],
  [
    'value' => 'Ekko',
    'label' => 'Ekko',
    'icon' => $ekkoIcon,
  ],
  [
    'value' => 'Revel',
    'label' => 'Revel',
    'icon' => $revelIcon,
  ],
  [
    'value' => 'Storyteller',
    'label' => 'Storyteller',
    'icon' => $storytellerIcon,
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
                            <legend class="sr-only">
                                <?php echo htmlspecialchars($filterTitle, ENT_QUOTES, 'UTF-8'); ?>
                            </legend>

                            <div class="service-model-filter__pill-tray" data-model-pill-tray>
                                <?php foreach ($models as $model): ?>
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
                            <legend class="sr-only">Sort upgrades</legend>

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

            <div class="service-model-filter__search-panel is-hidden" data-service-search-panel hidden>
                <div class="service-model-filter__search-box">
                    <i class="fa-solid fa-magnifying-glass service-model-filter__search-icon" aria-hidden="true"></i>

                    <input type="search" class="service-model-filter__search-input" placeholder="Search upgrades..."
                        aria-label="Search upgrades" data-service-search-input />

                    <button type="button" class="service-model-filter__search-close" aria-label="Close search"
                        data-service-search-close>
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
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