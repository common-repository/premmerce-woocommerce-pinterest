<div class="updated notice is-dismissible">
    <p><strong>Premmerce Pinterest </strong>
        <?php if(!$is_logged_in): ?>
            <?php printf(__('is almost ready to get started. Please %s to your Pinterest account', 'premmerce-pinterest'),'<a href="' . $sign_in_link . '">' . __('sign in', 'premmerce-pinterest') . '</a>' ); ?>
        <?php else:?>
            <?php _e('ready to get started', 'premmerce-pinterest'); ?>.
        <?php endif;?>
    </p>
</div>