<?php if ( ! defined( 'WPINC' ) ) die; ?>

<?php $tip = __( 'Your tag id created at Pinterest', 'premmerce-pinterest' ); ?>
<?php use Premmerce\Pinterest\Analytics\AnalyticsSettings; ?>

<p class="form-field">
    <?php echo  wc_help_tip( __( 'Your tag id created at Pinterest', 'premmerce-pinterest' ) ); ?>
    <input style="margin-left: 5px" title="<?php _e( 'Instruction to create app at Pinterest', 'premmerce-pinterest' ) ?>" type="text" id="<?php echo AnalyticsSettings::SETTINGS_PREFIX; ?>tag_id" name="<?php echo AnalyticsSettings::SETTINGS_PREFIX; ?>tag_id" value="<?php echo get_option( AnalyticsSettings::SETTINGS_PREFIX . 'tag_id', null ); ?>">
    <p class="description" style="margin-left: 30px;"><?php printf(__('See %s', 'premmerce-pinterest'), '<a target="_blank" href="https://help.pinterest.com/en/business/article/track-conversions-with-pinterest-tag">'.__('documentation', 'premmerce-pinterest').'</a>') ?></p>
</p>
