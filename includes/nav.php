<?php
$navContext = $navContext ?? 'public';
$navType = $navType ?? 'guest';
$activePage = $activePage ?? '';
$isPortalNav = $navContext === 'portal';
$isLoggedIn = !empty($isLoggedIn);
$displayName = trim((string) ($displayName ?? ''));
$displayEmail = trim((string) ($displayEmail ?? ''));
$portalPages = ['dashboard', 'vehicles', 'account', 'change-password', 'login', 'register'];
$portalMenuIsActive = in_array($activePage, $portalPages, true);
$homeUrl = 'https://www.titanvans.com';
$portalTriggerLabel = ($navType === 'auth' && $isLoggedIn) ? 'My Account' : 'Login';
?>
<nav class="navbar <?php echo $isPortalNav ? 'navbar--portal' : ''; ?>">
    <div class="nav-brand-cluster">
        <a href="<?php echo $homeUrl; ?>" class="nav-logo" aria-label="Titan Vans Home">
            <img src="/assets/images/titan-vans-ws-logo.svg" alt="Official Titan Vans Logo" />
        </a>

        <div class="nav-brand-home-spacer" aria-hidden="true"></div>
    </div>

    <div class="nav-panel" id="site-nav-menu">
        <ul class="nav-menu nav-menu--center">
            <li class="nav-item dropdown-parent">
                <a class="nav-link nav-link--trigger" aria-expanded="false">Models
                    <span class="dropdown-arrow"><i class="fas fa-chevron-down"></i></span>
                </a>

                <ul class="dropdown dropdown--mega dropdown--models">
                    <li>
                        <a href="/sprinter144">
                            <span class="dropdown-link__media">
                                <img src="/assets/images/filters/sprinter.png" alt="Mercedes Sprinter 144 model" />
                            </span>

                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Mercedes-Benz</span>

                                <span class="dropdown-link__title">Sprinter 144"</span>

                                <span class="dropdown-link__desc">
                                    A shorter wheelbase camper van option that balances comfort,
                                    drivability, and everyday usability.
                                </span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a href="/sprinter170">
                            <span class="dropdown-link__media">
                                <img src="/assets/images/filters/sprinter-170-white.png"
                                    alt="Mercedes Sprinter 170 model" />
                            </span>

                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Mercedes-Benz</span>

                                <span class="dropdown-link__title">Sprinter 170"</span>

                                <span class="dropdown-link__desc">
                                    A longer platform with more interior room for extended travel,
                                    storage, and larger layouts.
                                </span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a href="/transit148">
                            <span class="dropdown-link__media">
                                <img src="/assets/images/filters/transit.png" alt="Ford Transit 148 model" />
                            </span>

                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Ford</span>

                                <span class="dropdown-link__title">Transit 148"</span>

                                <span class="dropdown-link__desc">
                                    A versatile mid-length camper van base with a comfortable
                                    blend of space and maneuverability.
                                </span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a href="/transit148el">
                            <span class="dropdown-link__media">
                                <img src="/assets/images/filters/transit.png"
                                    alt="Ford Transit 148 extended length model" />
                            </span>

                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Ford</span>

                                <span class="dropdown-link__title">Transit 148" EL</span>

                                <span class="dropdown-link__desc">
                                    An extended-length Transit platform designed for more storage,
                                    gear, and livable interior space.
                                </span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a href="/models">
                            <span class="dropdown-link__media">
                                <img src="/assets/images/models-image7.png" alt="All Titan camper van models" />
                            </span>

                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Explore</span>

                                <span class="dropdown-link__title">All Van Models</span>

                                <span class="dropdown-link__desc">
                                    Compare Titan Vans platforms, layouts, and configurations
                                    across the full model lineup.
                                </span>
                            </span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item dropdown-parent">
                <a class="nav-link nav-link--trigger" aria-expanded="false">Buy Now
                    <span class="dropdown-arrow"><i class="fas fa-chevron-down"></i></span>
                </a>

                <ul class="dropdown dropdown--mega dropdown--menu-grid">

                    <li>
                        <a href="/build-price/build-form">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
                            </span>

                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Custom Order</span>

                                <span class="dropdown-link__title">Build to Order Camper Vans</span>

                                <span class="dropdown-link__desc">Start a custom build tailored to your layout, travel
                                    style, and platform.</span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a href="/vans-for-sale">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-van-shuttle" aria-hidden="true"></i>
                            </span>

                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Inventory</span>

                                <span class="dropdown-link__title">New Pre-Built Camper Vans</span>

                                <span class="dropdown-link__desc">Browse available new vans that are ready now or nearly
                                    complete.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/used-vans-for-sale">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-road" aria-hidden="true"></i>
                            </span>

                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Pre-Owned</span>

                                <span class="dropdown-link__title">Used Pre-Owned Camper Vans</span>

                                <span class="dropdown-link__desc">Explore previously owned inventory with proven layouts
                                    and faster delivery.</span>
                            </span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item dropdown-parent">
                <a class="nav-link nav-link--trigger" aria-expanded="false">Upgrades &amp; Service
                    <span class="dropdown-arrow"><i class="fas fa-chevron-down"></i></span>
                </a>
                <ul class="dropdown dropdown--mega dropdown--menu-grid">
                    <li>
                        <a href="/sprinter-campervan-service">
                            <span class="dropdown-link__media">
                                <img src="/assets/images/mercedes-sprinter-service.jpg" alt="" />
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Mercedes</span>

                                <span class="dropdown-link__title">Sprinter</span>

                                <span class="dropdown-link__desc">Maintenance, diagnostics, repairs, and upgrades for
                                    Sprinter platforms.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/transit-campervan-service">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <!-- <i class="fa-solid fa-toolbox" aria-hidden="true"></i> -->
                                <img src="/assets/images/transit-service.jpg" alt="" />
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Ford</span>

                                <span class="dropdown-link__title">Transit</span>

                                <span class="dropdown-link__desc">Service and upgrade options designed specifically for
                                    Transit camper vans.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/promaster-campervan-service">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <!-- <i class="fa-solid fa-gears" aria-hidden="true"></i> -->
                                <img src="/assets/images/promaster-service.jpg" alt="" />
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Ram</span>

                                <span class="dropdown-link__title">ProMaster</span>

                                <span class="dropdown-link__desc">Explore service work and improvement options for
                                    ProMaster owners.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/storyteller-overland-service">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <!-- <i class="fa-solid fa-gears" aria-hidden="true"></i> -->
                                <img src="/assets/images/storyteller-camper-van.jpg" alt="" />
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Storyteller</span>

                                <span class="dropdown-link__title">Overland</span>

                                <span class="dropdown-link__desc">Support, upgrades, and service tailored to Storyteller
                                    builds.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/winnebago-revel-service">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <!-- <i class="fa-solid fa-gears" aria-hidden="true"></i> -->
                                <img src="/assets/images/revel-service.jpg" alt="" />
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Winnebago</span>

                                <span class="dropdown-link__title">Revel</span>

                                <span class="dropdown-link__desc">Get service support and premium upgrades for your
                                    Revel.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/winnebago-ekko-service">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <!-- <i class="fa-solid fa-gears" aria-hidden="true"></i> -->
                                <img src="/assets/images/winnebago-ekko.jpg" alt="" />
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Winnebago</span>

                                <span class="dropdown-link__title">Ekko</span>

                                <span class="dropdown-link__desc">Browse service options and platform-specific help for
                                    Ekko owners.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/service">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <!-- <i class="fa-solid fa-gears" aria-hidden="true"></i> -->
                                <img src="/assets/images/sprinter-service-options.jpg" alt="" />
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Browse</span>

                                <span class="dropdown-link__title">All Services</span>

                                <span class="dropdown-link__desc">See the full list of available service and upgrade
                                    categories.</span>
                            </span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item dropdown-parent">
                <a class="nav-link nav-link--trigger" aria-expanded="false">Support
                    <span class="dropdown-arrow"><i class="fas fa-chevron-down"></i></span>
                </a>
                <ul class="dropdown dropdown--mega dropdown--menu-grid">
                    <li>
                        <a href="/video-tutorials">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-circle-play" aria-hidden="true"></i>
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Watch</span>

                                <span class="dropdown-link__title">Video Tutorials</span>

                                <span class="dropdown-link__desc">Step-by-step walkthroughs for systems, features, and
                                    common usage tasks.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/owners-manual">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Guide</span>

                                <span class="dropdown-link__title">Owner's Manual</span>

                                <span class="dropdown-link__desc">Find core operating information, care instructions,
                                    and product references.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/faq">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Answers</span>

                                <span class="dropdown-link__title">Frequently Asked Questions</span>

                                <span class="dropdown-link__desc">Quick answers to the most common questions from owners
                                    and shoppers.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/financing">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Payments</span>

                                <span class="dropdown-link__title">Financing</span>

                                <span class="dropdown-link__desc">Explore financing options and next steps for your
                                    camper van purchase.</span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a href="/updated-service/contact.php">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-headset" aria-hidden="true"></i>
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Service Team</span>

                                <span class="dropdown-link__title">Contact</span>

                                <span class="dropdown-link__desc">Connect directly with the service department for help
                                    or scheduling.</span>
                            </span>
                        </a>
                    </li>
                    <!-- <li>
                        <a href="/updated-service/index.php">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-calendar-alt" aria-hidden="true"></i>
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Quick Services</span>
                                <span class="dropdown-link__title">Schedule Appointment</span>
                                <span class="dropdown-link__desc">Need a quick service? Make an appointment online with
                                    our new scheduling services.</span>
                            </span>
                        </a>
                    </li> -->
                </ul>
            </li>

            <li class="nav-item dropdown-parent">
                <a class="nav-link nav-link--trigger" aria-expanded="false">Company
                    <span class="dropdown-arrow"><i class="fas fa-chevron-down"></i></span>
                </a>
                <ul class="dropdown dropdown--mega dropdown--menu-grid">
                    <li>
                        <a href="/about">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-building" aria-hidden="true"></i>
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Company</span>
                                <span class="dropdown-link__title">About Us</span>
                                <span class="dropdown-link__desc">Learn about Titan Vans, our process, and what drives
                                    the
                                    brand.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/blog">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Stories</span>
                                <span class="dropdown-link__title">Blog</span>
                                <span class="dropdown-link__desc">Read stories, updates, guides, and helpful camper van
                                    content.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/rvia">
                            <span class="dropdown-link__media dropdown-link__media--icon dropdown-link__img">
                                <!-- <i class="fa-solid fa-award" aria-hidden="true"></i> -->
                                <img src="/assets/images/rvia.svg" alt="" />
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Standards</span>
                                <span class="dropdown-link__title">RVIA</span>
                                <span class="dropdown-link__desc">See standards and certification information related to
                                    Titan
                                    Vans.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/expert-upfitter">
                            <span class="dropdown-link__media dropdown-link__media--icon dropdown-link__img">
                                <!-- <i class="fa-solid fa-award" aria-hidden="true"></i> -->
                                <img src="/assets/images/expert-upfitter.svg" alt="" />
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Expertise</span>
                                <span class="dropdown-link__title">Expert Upfitter</span>
                                <span class="dropdown-link__desc">Explore our expertise in upfitting and premium van
                                    conversions.</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="/careers">
                            <span class="dropdown-link__media dropdown-link__media--icon ">
                                <i class="fa-solid fa-briefcase" aria-hidden="true"></i>
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Join Us</span>
                                <span class="dropdown-link__title">Careers</span>
                                <span class="dropdown-link__desc">Join the team and view current opportunities at Titan
                                    Vans.</span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a href="/contact">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-phone" aria-hidden="true"></i>
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Reach Out</span>
                                <span class="dropdown-link__title">Contact</span>
                                <span class="dropdown-link__desc">Get in touch with the company for questions,
                                    partnerships,
                                    or
                                    help.</span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a href="/events.php">
                            <span class="dropdown-link__media dropdown-link__media--icon">
                                <i class="fa-solid fa-ticket" aria-hidden="true"></i>
                            </span>
                            <span class="dropdown-link__content">
                                <span class="dropdown-link__eyebrow">Expo Schedule</span>
                                <span class="dropdown-link__title">Events</span>
                                <span class="dropdown-link__desc">Where to find Titan Vans in 2026</span>
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>

        <ul class="nav-actions">
            <li class="nav-item">

                <a href="https://www.timbervankits.com" target="_blank" rel="noopener noreferrer" class="nav-link tdk">
                    DIY Van Kits
                </a>

                <!-- <div class="nav-brand-cluster tooltip">
                    <a href="https://www.timbervankits.com" class="nav-logo-timber" aria-label="Timber Van Kits Home">
                        <img src="/assets/icons/timber-square.png" alt="Official Timber Van Kits logo" />
                    </a>

                    <span class="tooltiptext">DIY Van Kits</span>

                </div> -->

            </li>



            <!-- <li class="nav-item">
                <a href="/updated-service/my-cart.php"
                    class="nav-link nav-link--login <?php echo $activePage === 'my-cart' ? 'active' : ''; ?>">
                    My Cart
                    <span class="nav-link--login__icon" aria-hidden="true">
                        <i class="fa-solid fa-shopping-cart"></i>
                    </span>
                </a>
            </li> -->

            <!-- <?php if ($isPortalNav): ?>
                <li class="nav-item dropdown-parent nav-item--portal">
                    <a class="nav-link nav-link--portal nav-link--trigger <?php echo $portalMenuIsActive ? 'active' : ''; ?>"
                        aria-expanded="false">


                        <span
                            class="nav-link--portal_label"><?php echo htmlspecialchars($portalTriggerLabel, ENT_QUOTES, 'UTF-8'); ?></span>

                        <span class="nav-link--portal__icon" aria-hidden="true">
                            <i class="fa-solid fa-circle-user"></i>
                        </span>
                    </a>

                    <ul class="dropdown dropdown--portal dd-last">
                        <?php if ($navType === 'auth' && $isLoggedIn): ?>
                            <li class="dropdown-account-summary">
                                <div class="dropdown-account-summary__icon" aria-hidden="true">
                                    <i class="fa-solid fa-circle-user"></i>
                                </div>

                                <div class="dropdown-account-summary__content">
                                    <div class="dropdown-account-summary__eyebrow">Logged in as</div>

                                    <div class="dropdown-account-summary__name">
                                        <?php echo htmlspecialchars($displayName !== '' ? $displayName : 'Account User', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>

                                    <?php if ($displayEmail !== ''): ?>
                                        <div class="dropdown-account-summary__email">
                                            <?php echo htmlspecialchars($displayEmail, ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>

                            <li>
                                <a href="/updated-service/my-cart.php"
                                    class="<?php echo $activePage === 'cart' ? 'active' : ''; ?>">
                                    <span class="dropdown-item__icon" aria-hidden="true"><i
                                            class="fa-solid fa-shopping-cart"></i></span>
                                    <span>My Cart</span>
                                </a>
                            </li>

                            <li>
                                <a href="/account/dashboard.php"
                                    class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                                    <span class="dropdown-item__icon" aria-hidden="true"><i
                                            class="fa-solid fa-gauge-high"></i></span>
                                    <span>Dashboard</span>
                                </a>
                            </li>

                            <li>
                                <a href="/account/account.php" class="<?php echo $activePage === 'account' ? 'active' : ''; ?>">
                                    <span class="dropdown-item__icon" aria-hidden="true"><i
                                            class="fa-solid fa-user-gear"></i></span>
                                    <span>Settings</span>
                                </a>
                            </li>

                            <li class="dropdown-form-item">
                                <form method="post" action="/account/logout.php" class="dropdown-logout-form">
                                    <?php echo csrf_input(); ?>
                                    <button type="submit" class="dropdown-logout-btn">
                                        <span class="dropdown-item__icon" aria-hidden="true"><i
                                                class="fa-solid fa-right-from-bracket"></i></span>
                                        <span>Log Out</span>
                                    </button>
                                </form>
                            </li>
                        <?php else: ?>

                            <li class="dropdown-account-summary dropdown-account-summary--guest">
                                <div class="dropdown-account-summary__icon" aria-hidden="true">
                                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                </div>

                                <div class="dropdown-account-summary__content">
                                    <div class="dropdown-account-summary__eyebrow">Customer Portal</div>
                                    <div class="dropdown-account-summary__name">Guest Access</div>
                                    <div class="dropdown-account-summary__email">Login or register to manage your account</div>
                                </div>
                            </li>

                            <li>
                                <a href="/account/login.php" class="<?php echo $activePage === 'login' ? 'active' : ''; ?>">
                                    <span class="dropdown-item__icon" aria-hidden="true"><i
                                            class="fa-solid fa-right-to-bracket"></i></span>
                                    <span>Login</span>
                                </a>
                            </li>

                            <li>
                                <a href="/account/register.php"
                                    class="<?php echo $activePage === 'register' ? 'active' : ''; ?>">
                                    <span class="dropdown-item__icon" aria-hidden="true"><i
                                            class="fa-solid fa-user-plus"></i></span>
                                    <span>Register</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if (!$isPortalNav): ?>
                <li class="nav-item nav-item--login">
                    <a href="/account/login.php"
                        class="nav-link nav-link--login <?php echo $activePage === 'login' ? 'active' : ''; ?>">
                        <span>Login</span>
                        <span class="nav-link--login__icon" aria-hidden="true">
                            <i class="fa-solid fa-circle-user"></i>
                        </span>
                    </a>
                </li>


            <?php endif; ?> -->

        </ul>
    </div>

    <div class="hamburger" aria-label="Toggle navigation" aria-controls="site-nav-menu" aria-expanded="false"
        role="button" tabindex="0">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </div>
</nav>