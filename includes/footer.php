<?php

$pageMeta = $pageMeta ?? [];

$footerContext = $pageMeta['footer_context'] ?? ($footerContext ?? 'public');
$navType = $pageMeta['nav_type'] ?? ($navType ?? 'guest');
$activePage = $pageMeta['active_page'] ?? ($activePage ?? '');
$isPortalFooter = $footerContext === 'portal';

if (!isset($isLoggedIn)) {
    $isLoggedIn = !empty($_SESSION['user_id']);
}

$currentYear = date('Y');
$extraFooterScripts = $extraFooterScripts ?? '';

?>
</main>

<footer class="footer light-bg <?php echo $isPortalFooter ? 'footer--portal' : ''; ?>">
    <div class="footer-menu">
        <div>
            <h5>Menu</h5>
            <ul>
                <li><a href="https://www.titanvans.com">Home</a></li>
                <li><a href="/about">About</a></li>
                <li><a href="/models">Models</a></li>
                <li><a href="/financing">Financing</a></li>
                <li><a href="/service">Service</a></li>
                <li><a href="/contact.php">Contact</a></li>
            </ul>
        </div>

        <div>
            <h5>Models</h5>
            <ul>
                <li><a href="/sprinter144">Sprinter 144"</a></li>
                <li><a href="/sprinter170">Sprinter 170"</a></li>
                <li><a href="/transit148">Transit 148"</a></li>
                <li><a href="/transit148el">Transit 148" EL</a></li>
            </ul>
        </div>

        <div>
            <h5>Social</h5>
            <ul>
                <li><a target="_blank" rel="noopener noreferrer"
                        href="https://www.titanvans.com/join-newsletter">Newsletter</a></li>
                <li><a target="_blank" rel="noopener noreferrer"
                        href="https://www.instagram.com/titanvans/?hl=en">Instagram</a></li>
                <li><a target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/titanvans/">Facebook</a>
                </li>
                <li><a target="_blank" rel="noopener noreferrer" href="https://www.youtube.com/titanvans">Youtube</a>
                </li>
                <li><a target="_blank" rel="noopener noreferrer"
                        href="https://www.yelp.com/biz/titan-vans-boulder">Yelp</a></li>
            </ul>
        </div>

        <div>
            <h5>Misc</h5>
            <ul>
                <li><a href="/careers">Careers</a></li>
                <li><a href="/warranty">Warranty</a></li>
                <li><a href="/terms-of-service">Terms of Service</a></li>
                <li><a href="/privacy-policy">Privacy Policy</a></li>
                <li><a href="/cookie-policy.php">Cookie Policy</a></li>
                <li> <a class="footer-cta--cookie" href="#" data-cc-open-preferences>
                        Cookie Preferences
                    </a></li>

            </ul>
        </div>

        <div>

            <h5>Portal</h5>
            <ul>
                <?php if ($navType === 'auth' && $isLoggedIn): ?>
                    <li>
                        <a href="/account/dashboard.php" class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="/account/vehicles.php" class="<?php echo $activePage === 'vehicles' ? 'active' : ''; ?>">
                            Vehicles
                        </a>
                    </li>
                    <li>
                        <a href="/account/account.php" class="<?php echo $activePage === 'account' ? 'active' : ''; ?>">
                            Account
                        </a>
                    </li>
                    <li>
                        <a href="/account/change-password.php"
                            class="<?php echo $activePage === 'change-password' ? 'active' : ''; ?>">
                            Change Password
                        </a>
                    </li>
                    <li>
                        <form method="post" action="/account/logout.php" class="footer-logout-form">
                            <?php echo csrf_input(); ?>
                            <button type="submit" class="footer-logout-btn">
                                Log Out
                            </button>
                        </form>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="/account/login.php" class="<?php echo $activePage === 'login' ? 'active' : ''; ?>">
                            Login
                        </a>
                    </li>
                    <li>
                        <a href="/account/register.php" class="<?php echo $activePage === 'register' ? 'active' : ''; ?>">
                            Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

    </div>
    </div>

    <?php if ($isPortalFooter): ?>
        <div class="nl-subscribe nl-subscribe--portal">
            <h5><?php echo ($navType === 'auth' && $isLoggedIn) ? 'Need Account Help?' : 'Customer Portal'; ?></h5>

            <p>
                <?php if ($navType === 'auth' && $isLoggedIn): ?>
                    Manage vehicles, update your account details, review your saved information, or contact Titan Vans support.
                <?php else: ?>
                    Login or register to manage your vehicles, update your account, and access your customer portal tools.
                <?php endif; ?>
            </p>

            <div class="nl-subscribe__actions">
                <?php if ($navType === 'auth' && $isLoggedIn): ?>
                    <a class="footer-cta footer-cta--primary" href="/account/dashboard.php">
                        Dashboard
                    </a>
                <?php else: ?>
                    <a class="footer-cta footer-cta--primary" href="/account/login.php">
                        Login
                    </a>
                <?php endif; ?>

                <a class="footer-cta footer-cta--secondary" href="/contact.php">
                    Contact
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="nl-subscribe">
            <h5>Join Our Newsletter</h5>

            <p>
                Subscribe to get useful camper van tips, interesting stories, new
                product releases and events.
            </p>

            <a class="footer-cta footer-cta--primary" href="/join-newsletter">
                Subscribe
            </a>
        </div>
    <?php endif; ?>

    <div class="footer-nap">
        <ul>
            <li><strong>TITAN VANS ©<?php echo $currentYear; ?></strong></li>
            <li>
                <strong>Address:</strong><br />
                1901 Central Ave Unit 1 <br />Boulder, CO 80301
            </li>
            <li><strong>Phone:</strong> 303-975-6492</li>
            <li><strong>Email:</strong> info@titanvans.com</li>
        </ul>
    </div>
</footer>

<script src="/src/js/nav.js"></script>
<?php echo $extraFooterScripts; ?>
</body>

</html>