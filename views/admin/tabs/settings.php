<?php if ( ! defined( 'WPINC' ) ) die; ?>

<?php settings_errors(); ?>

<?php if( $account->isLoggedIn() ): ?>
    <div class="postbox premmerce-postbox">
        <form action="options.php" method="post">
            <?php
                settings_fields( 'premmerce_pinterest_settings' );
                do_settings_sections( 'premmerce_pinterest_settings' );
                submit_button( __( 'Save', 'premmerce-pinterest' ) );
            ?>
        </form>
    </div>
<?php endif; ?>