<?php if ( ! defined( 'WPINC' ) ) die; ?>

<?php
    $tip = __( '{price} - product price', 'premmerce-pinterest' ) . '<br />';
    $tip .= __( '{title} - product title', 'premmerce-pinterest' ) . '<br />';
    $tip .= __( '{link} - link to product', 'premmerce-pinterest' ) . '<br />';
    $tip .= __( '{description} - product description', 'premmerce-pinterest' ) . '<br />';
    $tip .= __( '{excerpt} - product excerpt', 'premmerce-pinterest' ) . '<br />';
    $tip .= __( '{site_title} - site title', 'premmerce-pinterest' );
?>

<span class="premmerce-pinterest-tip">
 <?php echo wc_help_tip( $tip ); ?>
</span>
<div class="inline-block" style="width: 90%">
    <textarea maxlength="500" name="premmerce_pinterest_default_description" id="premmerce_pinterest_default_description" cols="100" rows="4"><?php esc_attr_e( get_option( 'premmerce_pinterest_default_description' ) ); ?></textarea>
    <p class="description" id="premmerce_pinterest_default_description">
        <?php _e( 'Available shortcodes', 'premmerce-pinterest' ) ?>: {price} {title} {link} {description} {excerpt} {site_title}.
        <?php _e( 'Max text length 500 characters including real content embedded by shortcode', 'premmerce-pinterest' ) ?>
    </p>
</div>
