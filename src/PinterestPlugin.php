<?php

namespace Premmerce\Pinterest;

use  Premmerce\SDK\V2\FileManager\FileManager ;
use  Premmerce\Pinterest\Analytics\Analytics ;
use  Premmerce\SDK\V2\Notifications\AdminNotifier ;
use  Premmerce\Pinterest\Admin\Admin ;
use  Premmerce\Pinterest\Frontend\Frontend ;
use  Premmerce\Pinterest\PinterestModel as Model ;
/**
 * Class PinterestPlugin
 *
 * @package Premmerce\Pinterest
 */
class PinterestPlugin
{
    /**
     * @var FileManager
     */
    private  $fileManager ;
    /**
     * @var Model
     */
    private  $model ;
    /**
     * @var AdminNotifier
     */
    private  $notifier ;
    /**
     * @var Analytics
     */
    private  $analytics ;
    /**
     * PinterestPlugin constructor.
     *
     * @param string $mainFile
     */
    public function __construct( $mainFile )
    {
        $this->fileManager = new FileManager( $mainFile );
        $this->model = new Model();
        $this->notifier = new AdminNotifier();
        add_action( 'plugins_loaded', array( $this, 'loadTextDomain' ) );
        add_action( 'admin_init', array( $this, 'checkRequirePlugins' ) );
        add_filter( 'cron_schedules', array( $this, 'addCronInterval' ) );
        add_action( 'premmerce_pinterest_task', array( $this, 'doTask' ) );
    }
    
    /**
     * Run plugin part
     */
    public function run()
    {
        $valid = count( $this->validateRequiredPlugins() ) === 0;
        if ( $valid ) {
            
            if ( is_admin() ) {
                new Admin( $this->fileManager, $this->model );
            } else {
                new Frontend( $this->fileManager );
            }
        
        }
    }
    
    /**
     * Load plugin translations
     */
    public function loadTextDomain()
    {
        $name = $this->fileManager->getPluginName();
        load_plugin_textdomain( 'premmerce-pinterest', false, $name . '/languages/' );
    }
    
    /**
     * Add custom cron interval for each 3 minutes
     *
     * @param array $intervals
     *
     * @return array $intervals
     */
    public function addCronInterval( $intervals )
    {
        $frequently = get_option( 'premmerce_pinterest_default_frequently', 1 );
        $intervals['premmerce_pinterest_interval'] = array(
            'interval' => (int) ($frequently * 60),
            'display'  => 'Custom interval',
        );
        return $intervals;
    }
    
    /**
     * Run one task. Trigger by cron
     */
    public function doTask()
    {
        $taskManager = new TaskManager( $this->fileManager, $this->model );
        $taskManager->execute();
    }
    
    /*
     * Fired when the plugin is activated
     */
    public function activate()
    {
        set_transient( 'premmerce_pinterest_activation_message', true, 5 );
        add_option( 'premmerce_pinterest_default_description', false );
        add_option( 'premmerce_pinterest_default_board', false );
        add_option( 'premmerce_pinterest_is_banned', false );
        add_option( 'premmerce_pinterest_username', false );
        add_option( 'premmerce_pinterest_default_frequently', 1 );
        $this->model->createTable();
        // Add cron task if not already added
        if ( !wp_next_scheduled( 'premmerce_pinterest_task' ) ) {
            wp_schedule_event( time(), 'premmerce_pinterest_interval', 'premmerce_pinterest_task' );
        }
    }
    
    /**
     * Fired when the plugin is deactivated
     */
    public function deactivate()
    {
        $timestamp = wp_next_scheduled( 'premmerce_pinterest_task' );
        wp_unschedule_event( $timestamp, 'premmerce_pinterest_task' );
    }
    
    /**
     * Clear data
     */
    public static function uninstall()
    {
        global  $wpdb ;
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . "premmerce_pinterest" );
        $uploadDir = wp_upload_dir();
        $dir = $uploadDir['basedir'] . '/premmerce/';
        self::deleteDirectory( $dir );
        delete_option( 'premmerce_pinterest_login' );
        delete_option( 'premmerce_pinterest_password' );
        delete_option( 'premmerce_pinterest_username' );
        delete_option( 'premmerce_pinterest_default_board' );
        delete_option( 'premmerce_pinterest_default_description' );
        delete_option( 'premmerce_pinterest_is_banned' );
    }
    
    /**
     * Validate required plugins
     *
     * @return array
     */
    private function validateRequiredPlugins()
    {
        $plugins = array();
        if ( !function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        /**
         * Check if WooCommerce is active
         **/
        if ( !(is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' )) ) {
            $plugins[] = '<a target="_blank" href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a>';
        }
        return $plugins;
    }
    
    /**
     * Check required plugins and push notifications
     */
    public function checkRequirePlugins()
    {
        $message = __( 'The %s plugin requires %s plugin to be active!', 'premmerce-pinterest' );
        $plugins = $this->validateRequiredPlugins();
        if ( count( $plugins ) ) {
            foreach ( $plugins as $plugin ) {
                $error = sprintf( $message, 'Premmerce Pinterest', $plugin );
                $this->notifier->push( $error, AdminNotifier::ERROR, false );
            }
        }
    }
    
    /**
     * @param string $dir
     *
     * @return bool
     */
    protected static function deleteDirectory( $dir )
    {
        if ( !file_exists( $dir ) ) {
            return true;
        }
        if ( !is_dir( $dir ) ) {
            return unlink( $dir );
        }
        foreach ( scandir( $dir ) as $item ) {
            if ( $item == '.' || $item == '..' ) {
                continue;
            }
            if ( !self::deleteDirectory( $dir . DIRECTORY_SEPARATOR . $item ) ) {
                return false;
            }
        }
        return rmdir( $dir );
    }

}