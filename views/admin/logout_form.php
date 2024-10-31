<?php if ( ! defined( 'WPINC' ) ) die; ?>

<h1><?php printf( __( 'Hello, %s', 'premmerce-pinterest' ), $account->getUserName() ) ?></h1>

<h2> <?php echo __( 'Connected to Pinterest', 'premmerce-pinterest' ); ?>. </h2>

<form action="admin-post.php" method='POST'>
    <p class="submit">
        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php _e( 'Logout', 'premmerce-pinterest' )?>">
        <input type="hidden" name="action" value="premmerce_pinterest_logout">
        <?php wp_nonce_field( 'premmerce_pinterest_logout' ); ?>
    </p>
</form>