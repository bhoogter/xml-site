<div class="header-inner section-inner">
    <div class="header-titles-wrapper">
        <div class="header-titles">
            <?php
            twentytwenty_site_logo(); // Site title or logo.
            twentytwenty_site_description(); // Site description.
?>
        </div>

        <button class="toggle nav-toggle mobile-nav-toggle" data-toggle-target=".menu-modal" data-toggle-body-class="showing-menu-modal" aria-expanded="false" data-set-focus=".close-nav-toggle">
            <span class="toggle-inner">
                <span class="toggle-icon">...</span>
                <span class="toggle-text">Menu</span>
            </span>
        </button>        <!-- .nav-toggle -->

    </div>    <!-- .header-titles-wrapper -->

    <div class="header-navigation-wrapper">
        <nav class="primary-menu-wrapper" role="navigation">
            <linklist id='main' style='ul class="primary-menu reset-list-style' />
        </nav>        <!-- .primary-menu-wrapper -->
    </div>    <!-- .header-navigation-wrapper -->

</div><!-- .header-inner -->