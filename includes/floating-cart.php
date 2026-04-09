<?php
$cartHref = $cartHref ?? '/updated-service/my-cart.php';
?>

<a class="cart-link cart-link--floating is-hidden"
    href="<?php echo htmlspecialchars($cartHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="View cart">
    <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
    <span class="cart-link__text">My Cart</span>
    <span class="cart-badge">0</span>
</a>