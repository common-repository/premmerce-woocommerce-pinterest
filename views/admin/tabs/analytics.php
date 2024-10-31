<?php if ( ! defined( 'WPINC' ) ) die; ?>
<?php use Premmerce\Pinterest\Analytics\AnalyticsSettings; ?>
<?php settings_errors(); ?>

<?php if( $account->isLoggedIn() ): ?>
    <div class="postbox premmerce-postbox">
        <form action="options.php" method="post">
            <?php
                settings_fields(AnalyticsSettings::SETTINGS_PREFIX . 'settings' );
                do_settings_sections(AnalyticsSettings::SETTINGS_PREFIX . 'settings' );
                submit_button( __( 'Save', 'premmerce-pinterest' ) );
            ?>
        </form>
    </div>
<?php endif; ?>