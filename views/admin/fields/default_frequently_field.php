<?php if ( ! defined( 'WPINC' ) ) die; ?>

<?php $tip = __( 'time delay between tasks', 'premmerce-pinterest' ); ?>

<span class="premmerce-pinterest-tip">
    <?php echo wc_help_tip( $tip ); ?>
</span>
<div class="inline-block" style="width: 90%">
    <input type="number" id="premmerce_pinterest_default_frequently" name="premmerce_pinterest_default_frequently" value="<?php echo get_option( 'premmerce_pinterest_default_frequently', 1 ); ?>" step="any" min="0.05">

    <p class="description" id="premmerce_pinterest_default_frequently"><?php _e( 'Be careful! We recommended choose time more than 1 minutes. If less - Pinterest may take you for bot and ban your account', 'premmerce-pinterest' ) ?></p>
</div>
