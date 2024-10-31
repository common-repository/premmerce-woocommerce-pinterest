<?php if ( ! defined( 'WPINC' ) ) die; ?>

<h3><?php _e( 'Pinterest account login', 'premmerce-pinterest' ); ?></h3>

<form action="admin-post.php" method='POST'>
        <p>
            <label for="pinterest_login"><?php _e( 'Login', 'premmerce-pinterest' ); ?><br>
                <input type="text" name="pinterest_login" id="pinterest_login"  class="input" value="" size="20"></label>
        </p>

        <p>
            <label for="pinterest_password"><?php _e( 'Password', 'premmerce-pinterest' ); ?><br>
                <input type="password" name="pinterest_password" id="pinterest_password" class="input" value="" size="20"></label>
        </p>

        <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php _e( 'Login', 'premmerce-pinterest' )?>">
            <input type="hidden" name="action" value="premmerce_pinterest_login">
            <?php wp_nonce_field( 'premmerce_pinterest_login' ); ?>
        </p>
</form>