<?php if ( ! defined( 'WPINC' ) ) die; ?>

<span class="premmerce-pinterest-tip">
    <?php echo wc_help_tip( __( 'Product Pins add OG markup and make it easier for <strong>Pinners</strong> to see information about things you sell and include pricing, availability and buy location.', 'premmerce-pinterest' ), true )?>
</span>
<div class="inline-block" style="margin-top: 7px">
    <input type="checkbox" <?php checked( get_option( 'premmerce_pinterest_richpins_enable' ), 1 )?> name="premmerce_pinterest_richpins_enable" id="premmerce_pinterest_richpins_enable" value="1">
    <p class="description">
        <?php
            printf( __( 'After you turn on rich pins you must %s product page', 'premmerce-pinterest' ), vsprintf( '<a href="%s" target="_blank">%s</a>', [
               'https://developers.pinterest.com/tools/url-debugger/',
               __( 'validate', 'premmerce-pinterest' ),
           ] ) );
       ?>
    </p>
</div>