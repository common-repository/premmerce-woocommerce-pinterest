<?php if ( ! defined( 'WPINC' ) ) die; ?>

<!--start premmerce product pin seo-->
<meta property="og:type" content="product" />
<meta property="og:title" content="<?php echo $product->get_title(); ?>" />
<meta property="og:description" content="<?php echo strip_tags($product->get_description()); ?>" />
<meta property="og:url" content="<?php echo get_permalink( $product->get_id() );?>"/>
<meta property="og:site_name" content="<?php echo get_bloginfo( 'site_title' )?>" />
<meta property="og:price:amount" content="<?php echo esc_attr( $product_cost ); ?>" />
<?php if($regular_price): ?>
<meta property="og:price:standard_amount" content="<?php echo esc_attr( $regular_price ); ?>" />
<?php endif; ?>
<?php if($brand): ?>
<meta property="og:brand" content="<?php echo esc_attr( $brand ); ?>" />
<?php endif; ?>
<meta property="og:price:currency" content="<?php echo esc_attr( get_woocommerce_currency() ); ?>" />
<meta property="og:availability" content="<?php echo esc_attr( $stock ); ?>" />
<!--end premmerce product pin seo-->
