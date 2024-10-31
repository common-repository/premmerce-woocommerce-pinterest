<?php

// Create a helper function for easy SDK access.
function premmerce_pwp_fs()
{
    global  $premmerce_pwp_fs ;
    
    if ( !isset( $premmerce_pwp_fs ) ) {
        // Include Freemius SDK.
        require_once dirname( __FILE__ ) . '/freemius/start.php';
        $premmerce_pwp_fs = fs_dynamic_init( array(
            'id'             => '2208',
            'slug'           => 'premmerce-woocommerce-pinterest',
            'type'           => 'plugin',
            'public_key'     => 'pk_0b713a94efad26f22a81cc5300b6c',
            'is_premium'     => false,
            'has_addons'     => false,
            'has_paid_plans' => true,
            'trial'          => array(
            'days'               => 7,
            'is_require_payment' => true,
        ),
            'menu'           => array(
            'slug'    => 'premmerce-pinterest-page',
            'support' => false,
            'parent'  => array(
            'slug' => 'woocommerce',
        ),
        ),
            'is_live'        => true,
        ) );
    }
    
    return $premmerce_pwp_fs;
}

// Init Freemius.
premmerce_pwp_fs();
// Signal that SDK was initiated.
do_action( 'premmerce_pwp_fs_loaded' );