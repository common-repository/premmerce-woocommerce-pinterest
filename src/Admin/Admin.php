<?php

namespace Premmerce\Pinterest\Admin;

use  Premmerce\SDK\V2\FileManager\FileManager ;
use  Premmerce\Pinterest\Pinterest ;
use  Premmerce\Pinterest\PinterestModel ;
use  Premmerce\Pinterest\Admin\Pins\PinsService ;
use  Premmerce\Pinterest\Admin\Queue\QueueService ;
use  Premmerce\Pinterest\Admin\Pins\PinnedTable ;
use  Premmerce\Pinterest\Admin\Queue\QueueTable ;
/**
 * Class Admin
 *
 * @package Premmerce\Pinterest\Admin
 */
class Admin
{
    /**
     * @var FileManager
     */
    private  $fileManager ;
    /**
     * @var Pinterest
     */
    private  $account ;
    /**
     * @var Messenger
     */
    private  $messenger ;
    /**
     * @var PinterestModel
     */
    private  $model ;
    /**
     * @var array
     */
    private  $services = array() ;
    /**
     * Admin constructor.
     *
     * Register menu items and handlers
     *
     * @param FileManager $fileManager
     * @param PinterestModel $model
     */
    public function __construct( FileManager $fileManager, PinterestModel $model )
    {
        $this->fileManager = $fileManager;
        $this->model = $model;
        $this->messenger = new Messenger( $fileManager );
        $uploadDir = wp_upload_dir();
        $dir = $uploadDir['basedir'] . '/premmerce';
        
        if ( $this->makeDir( $dir ) ) {
            $this->account = new Pinterest( $dir );
            $this->registerServices();
            $this->bootServices();
        }
        
        $this->hooks();
    }
    
    /**
     * Register service
     */
    protected function registerServices()
    {
        $this->services[] = new PinsService();
        $this->services[] = new QueueService();
    }
    
    /**
     * Create path to store cookie
     *
     * @param string $dir
     *
     * @return bool
     */
    protected function makeDir( $dir )
    {
        $file = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        $message = sprintf( __( 'Enable to write cookie file, please check permissions directory %s', 'premmerce-pinterest' ), $dir );
        
        if ( !is_file( $file ) ) {
            if ( !is_dir( $dir ) ) {
                
                if ( !wp_is_writable( dirname( $dir ) ) || !mkdir( $dir ) ) {
                    $this->messenger->message( $message, 'error', Messenger::LOCAL_SHOW );
                    return false;
                }
            
            }
            
            if ( !file_put_contents( $file, 'deny from all' ) ) {
                $this->messenger->message( $message, 'error', Messenger::LOCAL_SHOW );
                return false;
            }
        
        }
        
        return true;
    }
    
    /**
     * Load service
     */
    protected function bootServices()
    {
        foreach ( $this->services as $service ) {
            $service->boot(
                $this->fileManager,
                $this->model,
                $this->account,
                $this->messenger
            );
        }
    }
    
    /**
     * Check if user cannot interact with pinterest
     *
     * @return bool
     */
    public function checkRequirements()
    {
        $error = false;
        
        if ( $this->account && $this->account->isLoggedIn() ) {
            $defaultBoard = get_option( 'premmerce_pinterest_default_board' );
            
            if ( !$defaultBoard ) {
                $this->messenger->message( __( 'You must select your default board to pin', 'premmerce-pinterest' ), 'error', Messenger::LOCAL_SHOW );
                $error = true;
            }
        
        }
        
        return !$error;
    }
    
    /**
     * Register admin side hooks
     */
    public function hooks()
    {
        add_action( 'admin_menu', array( $this, 'registerMenu' ), 999 );
        add_action( 'admin_init', array( $this, 'registerSettings' ) );
        add_action( 'admin_notices', array( $this, 'showMessages' ) );
        add_action( 'shutdown', array( $this, 'manageMessage' ) );
        add_action( 'add_meta_boxes', array( $this, 'addMetabox' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ), 100 );
    }
    
    /**
     * Enqueue plugin assets
     * @param string $screen
     */
    public function enqueueAssets( $screen )
    {
        
        if ( $screen == 'post.php' || $screen == 'woocommerce_page_premmerce-pinterest-page' || $screen == 'post-new.php' ) {
            // WooCommerce assets
            wp_enqueue_style( 'woocommerce_admin_styles' );
            wp_enqueue_script( 'woocommerce_admin' );
            wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $this->getAdminLocalizeScript() );
            $this->checkRequirements();
            $this->messenger->showMessages( Messenger::LOCAL_SHOW );
            $is_free = true;
            if ( $is_free ) {
                wp_enqueue_script( 'premmerce-pinterest-admin-script', $this->fileManager->locateAsset( 'admin/js/main.js' ) );
            }
            wp_enqueue_style( 'premmerce-pinterest-admin-style', $this->fileManager->locateAsset( 'admin/css/main.css' ) );
        }
    
    }
    
    /**
     * Get params for correctly working WooCommerce admin scripts
     *
     * @return array
     */
    private function getAdminLocalizeScript()
    {
        $locale = localeconv();
        $decimal = ( isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.' );
        $params = array(
            'i18n_decimal_error'                => sprintf( __( 'Please enter in decimal (%s) format without thousand separators.', 'woocommerce' ), $decimal ),
            'i18n_mon_decimal_error'            => sprintf( __( 'Please enter in monetary decimal (%s) format without thousand separators and currency symbols.', 'woocommerce' ), wc_get_price_decimal_separator() ),
            'i18n_country_iso_error'            => __( 'Please enter in country code with two capital letters.', 'woocommerce' ),
            'i18n_sale_less_than_regular_error' => __( 'Please enter in a value less than the regular price.', 'woocommerce' ),
            'i18n_delete_product_notice'        => __( 'This product has produced sales and may be linked to existing orders. Are you sure you want to delete it?', 'woocommerce' ),
            'i18n_remove_personal_data_notice'  => __( 'This action cannot be reversed. Are you sure you wish to erase personal data from the selected orders?', 'woocommerce' ),
            'decimal_point'                     => $decimal,
            'mon_decimal_point'                 => wc_get_price_decimal_separator(),
            'ajax_url'                          => admin_url( 'admin-ajax.php' ),
            'strings'                           => array(
            'import_products' => __( 'Import', 'woocommerce' ),
            'export_products' => __( 'Export', 'woocommerce' ),
        ),
            'nonces'                            => array(
            'gateway_toggle' => wp_create_nonce( 'woocommerce-toggle-payment-gateway-enabled' ),
        ),
            'urls'                              => array(
            'import_products' => ( current_user_can( 'import' ) ? esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ) : null ),
            'export_products' => ( current_user_can( 'export' ) ? esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ) : null ),
        ),
        );
        return $params;
    }
    
    /**
     * Add meta box to product create\update page
     */
    public function addMetaBox()
    {
        if ( $this->account->isLoggedIn() ) {
            add_meta_box(
                'premmerce-pinterest-metabox',
                __( 'Premmerce Pinterest', 'premmerce-pinterest' ),
                array( $this, 'renderMetabox' ),
                'product',
                'normal',
                'high'
            );
        }
    }
    
    /**
     * Show admin notices
     */
    public function showMessages()
    {
        $this->showActivationMessage();
        $this->messenger->showMessages( Messenger::GLOBAL_SHOW );
    }
    
    public function showActivationMessage()
    {
        
        if ( get_transient( 'premmerce_pinterest_activation_message' ) ) {
            $sign_in_link = ( $this->account->isLoggedIn() ? false : admin_url( 'admin.php?page=premmerce-pinterest-page' ) );
            $this->fileManager->includeTemplate( 'admin/activation-message.php', array(
                'is_logged_in' => $this->account->isLoggedIn(),
                'sign_in_link' => $sign_in_link,
            ) );
            delete_transient( 'premmerce_pinterest_activation_message' );
        }
    
    }
    
    /**
     * Check if user cannot interact with pinterest and save message about it
     */
    public function manageMessage()
    {
        $this->messenger->saveMessage();
    }
    
    /**
     * Register settings
     */
    public function registerSettings()
    {
        /**
         * Pinterest Settings Section
         */
        register_setting( 'premmerce_pinterest_settings', 'premmerce_pinterest_default_board' );
        register_setting( 'premmerce_pinterest_settings', 'premmerce_pinterest_default_description' );
        register_setting( 'premmerce_pinterest_settings', 'premmerce_pinterest_richpins_enable' );
        register_setting( 'premmerce_pinterest_settings', 'premmerce_pinterest_default_frequently', array(
            'sanitize_callback' => function ( $frequently ) {
            // Update interval
            wp_unschedule_event( wp_next_scheduled( 'premmerce_pinterest_task' ), 'premmerce_pinterest_task' );
            wp_schedule_event( time(), 'premmerce_pinterest_interval', 'premmerce_pinterest_task' );
            return abs( $frequently );
        },
        ) );
        add_settings_section(
            'premmerce_pinterest_settings',
            __( 'Pinterest settings', 'premmerce-pinterest' ),
            null,
            'premmerce_pinterest_settings'
        );
        add_settings_field(
            'premmerce_pinterest_default_board',
            __( 'Board', 'premmerce-pinterest' ),
            array( $this, 'renderDefaultBoardField' ),
            'premmerce_pinterest_settings',
            'premmerce_pinterest_settings',
            array(
            'label_for' => 'premmerce_pinterest_default_board',
        )
        );
        add_settings_field(
            'premmerce_pinterest_default_description',
            __( 'Pin description', 'premmerce-pinterest' ),
            array( $this, 'renderDefaultDescField' ),
            'premmerce_pinterest_settings',
            'premmerce_pinterest_settings',
            array(
            'label_for' => 'premmerce_pinterest_default_description',
        )
        );
        add_settings_field(
            'premmerce_pinterest_default_frequently',
            __( 'Task frequently (min)', 'premmerce-pinterest' ),
            array( $this, 'renderDefaultFrequentlyField' ),
            'premmerce_pinterest_settings',
            'premmerce_pinterest_settings',
            array(
            'label_for' => 'premmerce_pinterest_default_frequently',
        )
        );
        add_settings_field(
            'premmerce_pinterest_richpins_enable',
            __( 'Rich product pins', 'premmerce-pinterest' ),
            array( $this, 'renderRichPinsEnableField' ),
            'premmerce_pinterest_settings',
            'premmerce_pinterest_settings',
            array(
            'label_for' => 'premmerce_pinterest_richpins_enable',
        )
        );
    }
    
    /**
     * Render default description field
     */
    public function renderDefaultDescField()
    {
        $this->fileManager->includeTemplate( 'admin/fields/default_description_field.php' );
    }
    
    /**
     * Render login field
     */
    public function renderLoginField()
    {
        $this->fileManager->includeTemplate( 'admin/fields/login_field.php' );
    }
    
    /**
     * Render password field
     */
    public function renderPasswordField()
    {
        $this->fileManager->includeTemplate( 'admin/fields/password_field.php' );
    }
    
    /**
     * Render default frequently field
     */
    public function renderDefaultFrequentlyField()
    {
        $this->fileManager->includeTemplate( 'admin/fields/default_frequently_field.php' );
    }
    
    /**
     * Render rich pins enable\disable field
     */
    public function renderRichPinsEnableField()
    {
        $this->fileManager->includeTemplate( 'admin/fields/rich_pins_enable_field.php' );
    }
    
    /**
     * Render meta box at product edit\create page
     */
    public function renderMetaBox()
    {
        global  $post ;
        $username = $this->account->getUserName();
        $dbPins = $this->model->getWhere( 'attachment_id', array(
            'post_id'      => $post->ID,
            'action'       => array( 'IN', array( 'create', 'update' ) ),
            'pin_username' => $username,
        ) );
        $product = wc_get_product( $post->ID );
        $images = array();
        $featured = (int) get_post_thumbnail_id( $product->get_id() );
        // Add featured images
        if ( $featured ) {
            $images[] = $featured;
        }
        $images = array_unique( array_merge( $images, $dbPins ) );
        $this->fileManager->includeTemplate( 'admin/metabox.php', array(
            'images' => $images,
            'dbPins' => $dbPins,
        ) );
    }
    
    /**
     * Render default board field
     */
    public function renderDefaultBoardField()
    {
        $boards = $this->account->getUserBoards();
        $default_board = get_option( 'premmerce_pinterest_default_board' );
        $this->fileManager->includeTemplate( 'admin/fields/default_board_field.php', array(
            'boards'        => $boards,
            'default_board' => $default_board,
        ) );
    }
    
    /**
     * Register admin menu
     */
    public function registerMenu()
    {
        add_submenu_page(
            'woocommerce',
            'Woocommerce pinterest integration',
            'Pinterest',
            'read',
            'premmerce-pinterest-page',
            array( $this, 'renderPinterestPage' )
        );
    }
    
    /**
     * Render data at premmerce-pinterest-page
     */
    public function renderPinterestPage()
    {
        $current = ( isset( $_GET['tab'] ) ? $_GET['tab'] : null );
        $defaultTab = ( $this->account->isLoggedIn() ? 'pinned' : 'account' );
        $queueTable = new QueueTable( $this->fileManager, $this->model );
        $pinnedTable = new PinnedTable( $this->fileManager, $this->model );
        $tabs['account'] = __( 'Account', 'premmerce-pinterest' );
        
        if ( $this->account && $this->account->isLoggedIn() ) {
            $tabs['pinned'] = __( 'Pinned', 'premmerce-pinterest' );
            $tabs['queue'] = __( 'Queue', 'premmerce-pinterest' );
            $tabs['settings'] = __( 'Settings', 'premmerce-pinterest' );
        }
        
        $this->fileManager->includeTemplate( 'admin/main.php', array(
            'fileManager' => $this->fileManager,
            'current'     => ( $current ? $current : $defaultTab ),
            'tabs'        => $tabs,
            'account'     => $this->account,
            'queueTable'  => $queueTable,
            'pinnedTable' => $pinnedTable,
        ) );
    }

}