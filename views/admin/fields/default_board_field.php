<?php if ( ! defined( 'WPINC' ) ) die; ?>

<?php  $tip = __( 'The board for which will be published pins', 'premmerce-pinterest' ); ?>

<span class="premmerce-pinterest-tip">
    <?php echo wc_help_tip( $tip ); ?>
</span>
<div class="inline-block">
    <select name="premmerce_pinterest_default_board" id="premmerce_pinterest_default_board">
        <option value=""><?php _e('None', 'woocommerce' ); ?></option>
        <?php foreach ( $boards as $board ): ?>
            <option value="<?php echo $board[ 'id' ] ?>" <?php echo selected( $board[ 'id' ], $default_board ) ?> ><?php echo $board[ 'name' ] ?></option>
        <?php endforeach; ?>
    </select>

    <?php if ( empty( $boards ) ): ?>
        <?php update_option( 'premmerce_pinterest_default_board', false ); ?>

        <span> <?php _e( 'You don\'t have any boards on your Pinterest account', 'premmerce-pinterest' ); ?>
            <a href="https://www.pinterest.com/" target="_blank"><?php _e( 'Create', 'premmerce-pinterest' ); ?></a>
	</span>
    <?php endif; ?>
</div>
