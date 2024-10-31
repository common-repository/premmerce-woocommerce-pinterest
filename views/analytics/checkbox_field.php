<?php if ( ! defined( 'WPINC' ) ) die; ?>

<p class="form-field">
    <?php echo  wc_help_tip( $description ); ?>
    <input title="<?php echo $label; ?>" type="checkbox" value="yes" style="margin-left: 5px" <?php checked('yes', $checked, true) ?> name="<?php echo $id; ?>" id="<?php echo $id ?>">
    <label for="<?php echo $id; ?>"><?php _e('Enable for', 'premmerce-pinterest'); ?> <b><?php echo $label; ?></b></label>
</p>
