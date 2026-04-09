<?php
if (!isset($customerReviews) || !is_array($customerReviews)) {
    $customerReviews = [];
}

$reviewTrackId = $reviewTrackId ?? 'servicesReviewsTrack';

function review_source_class(string $source): string
{
    return match ($source) {
        'google' => 'review-card__source--google',
        'yelp' => 'review-card__source--yelp',
        'facebook' => 'review-card__source--facebook',
        default => '',
    };
}

function review_source_icon(string $source): string
{
    return match ($source) {
        'google' => 'fa-google',
        'yelp' => 'fa-yelp',
        'facebook' => 'fa-facebook',
        default => 'fa-star',
    };
}
?>

<div class="services-reviews__marquee">
    <div class="services-reviews__track" id="<?php echo htmlspecialchars($reviewTrackId, ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ($customerReviews as $review): ?>
            <?php
            $source = (string) ($review['source'] ?? 'google');
            $sourceLabel = (string) ($review['source_label'] ?? ucfirst($source));
            $name = (string) ($review['name'] ?? '');
            $meta = trim((string) ($review['meta'] ?? ''));
            $date = trim((string) ($review['date'] ?? ''));
            $rating = (string) ($review['rating'] ?? '5.0');
            $stars = (string) ($review['stars'] ?? '★★★★★');
            $image = (string) ($review['image'] ?? '/assets/images/reviews/default_user_avatar_yelp.png');
            $imageAlt = (string) ($review['image_alt'] ?? ('Portrait of ' . $name));
            $text = (string) ($review['text'] ?? '');
            $sourceClass = review_source_class($source);
            $sourceIcon = review_source_icon($source);
            ?>
            <article class="review-card" data-review-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
                data-review-source="<?php echo htmlspecialchars($sourceLabel, ENT_QUOTES, 'UTF-8'); ?>"
                data-review-date="<?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>"
                data-review-full="<?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?>"
                data-review-image="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>"
                data-review-image-alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="review-card__source <?php echo htmlspecialchars($sourceClass, ENT_QUOTES, 'UTF-8'); ?>"
                    aria-label="Review from <?php echo htmlspecialchars($sourceLabel, ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fa-brands <?php echo htmlspecialchars($sourceIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>
                    <span><?php echo htmlspecialchars($sourceLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <div class="review-card__header">
                    <div class="review-card__person">
                        <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8'); ?>"
                            class="review-card__image" />

                        <div class="review-card__meta">
                            <h3><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h3>

                            <?php if ($date !== ''): ?>
                                <p class="review-card__date"><?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>

                            <div class="review-card__rating-row"
                                aria-label="<?php echo htmlspecialchars($rating, ENT_QUOTES, 'UTF-8'); ?> out of 5 stars">
                                <span class="review-card__rating">
                                    <?php echo htmlspecialchars($rating, ENT_QUOTES, 'UTF-8'); ?>
                                </span>

                                <span class="review-card__stars" aria-hidden="true">
                                    <?php echo htmlspecialchars($stars, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="review-card__body">
                    <span class="review-card__quote review-card__quote--open" aria-hidden="true">“</span>

                    <p class="review-card__text review-card__text-clamp">
                        <?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?>
                    </p>

                    <span class="review-card__quote review-card__quote--close" aria-hidden="true">”</span>
                </div>

                <div class="review-card__actions">
                    <button type="button" class="review-card__more-btn">Show More</button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>