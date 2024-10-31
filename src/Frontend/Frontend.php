<?php namespace Premmerce\Pinterest\Frontend;

use Premmerce\SDK\V2\FileManager\FileManager;
use WC_Product;

/**
 * Class Frontend
 *
 * @package Premmerce\Pinterest\Frontend
 */
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

class Frontend
{
    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * Frontend constructor.
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;

        $this->hooks();
    }

    /**
     * Register hooks
     */
    public function hooks()
    {
        add_action('wp_head', function () {
            // Remove Yoast og tags if rich pins are enabled and add own
            if (get_option('premmerce_pinterest_richpins_enable', true) && is_product()) {

                // Check Yoast installed
                if ((is_plugin_active('wordpress-seo/wp-seo.php') || is_plugin_active_for_network('wordpress-seo/wp-seo.php'))) {
                    //remove_action( 'wpseo_head', array( $GLOBALS[ 'wpseo_og' ], 'opengraph' ), 30 );
                    remove_action('wpseo_opengraph', array($GLOBALS['wpseo_og'], 'type'), 5);
                    remove_action('wpseo_opengraph', array($GLOBALS['wpseo_og'], 'og_title'), 10);
                    remove_action('wpseo_opengraph', array($GLOBALS['wpseo_og'], 'description'), 11);
                    remove_action('wpseo_opengraph', array($GLOBALS['wpseo_og'], 'url'), 12);
                    remove_action('wpseo_opengraph', array($GLOBALS['wpseo_og'], 'site_name'), 13);
                }

                $this->addOwnMeta();
            }
        }, 1);
    }

    /**
     * Add pinterest og meta tags
     */
    public function addOwnMeta()
    {
        global $post;

        $product      = wc_get_product($post->ID);
        $product_cost = number_format(floatval($product->get_price()), 2);

        if ($product->is_on_sale() && $product->is_type('simple')) {
            $regular_price = number_format(floatval($product->get_regular_price()), 2);
        }
        $brand  = wp_get_post_terms($product->get_id(), 'product_brand', array("fields" => "names"));

        if ($brand && is_array($brand) && !empty($brand)) {
            $brand = $brand[0];
        } else {
            $brand = false;
        }

        $this->fileManager->includeTemplate('frontend/richpins.php', array(
            'product'      => $product,
            'stock'        => $this->format_stock($product),
            'product_cost' => $product_cost,
            'regular_price' => isset($regular_price) ? $regular_price : false,
            'brand'         => isset($brand) ? $brand : false,
        ));
    }

    /**
     * Format stock status
     *
     * @param WC_Product $product
     * @return mixed|string
     */
    public function format_stock(WC_Product $product)
    {
        $stock_status = get_post_meta($product->get_id(), '_stock_status', true);

        switch ($stock_status) {
            case 'instock':
                $stock_status = 'in stock';
                break;
            case 'outofstock':
                $stock_status = $product->backorders_allowed() ? 'backorder' : 'out of stock';
                break;
            case 'onbackorder':
                $stock_status = 'backorder';
                break;
        }

        return $stock_status;
    }
}
