<?php

namespace Premmerce\Pinterest\Admin\Pins;

use  Premmerce\SDK\V2\FileManager\FileManager ;
use  Premmerce\Pinterest\PinterestModel ;
use  Premmerce\Pinterest\Admin\Messenger ;
use  Premmerce\Pinterest\Pinterest ;
class PinsService
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
     * Boot service
     *
     * @param $fileManager
     * @param $model
     * @param $account
     * @param $messenger
     */
    public function boot(
        $fileManager,
        $model,
        $account,
        $messenger
    )
    {
        $this->fileManager = $fileManager;
        $this->model = $model;
        $this->account = $account;
        $this->messenger = $messenger;
        $this->hooks();
    }
    
    /**
     * Register hooks
     */
    public function hooks()
    {
        // Handle other action
        add_action( 'admin_post_-1', array( $this, 'handleAction' ), 99999 );
        // Pinterest Auth handlers
        add_action( 'admin_post_premmerce_pinterest_login', array( $this, 'handleLogin' ) );
        add_action( 'admin_post_premmerce_pinterest_logout', array( $this, 'handleLogout' ) );
        // product list bulk edit
        add_filter( 'bulk_actions-edit-product', array( $this, 'register_bulk_pin_action' ) );
        add_filter(
            'handle_bulk_actions-edit-product',
            array( $this, 'bulk_pin_action_handler' ),
            10,
            3
        );
        add_action(
            'save_post',
            array( $this, 'pinImage' ),
            10,
            3
        );
    }
    
    public function handleAction()
    {
        $action2 = $_REQUEST['action2'];
        
        if ( isset( $action2 ) && $_REQUEST['page'] == 'premmerce-pinterest-page' ) {
            if ( $action2 == 'premmerce_pinterest_remove_pin' ) {
                $this->handleRemovePin__premium_only();
            }
            
            if ( $action2 == 'premmerce_pinterest_update_pin' ) {
                $this->handleUpdatePin__premium_only();
            } elseif ( $_REQUEST['action2'] == '-1' && $_REQUEST['action'] == '-1' ) {
                // todo: find better way
                $parts = parse_url( wp_get_referer() );
                parse_str( $parts['query'], $query );
                $paged = ( isset( $_REQUEST['paged'] ) ? $_REQUEST['paged'] : 1 );
                
                if ( !key_exists( 'paged', $query ) ) {
                    $url = wp_get_referer() . "&paged=" . $paged;
                } else {
                    $url = str_replace( 'paged=' . $query['paged'], 'paged=' . $paged, wp_get_referer() );
                }
                
                wp_redirect( $url );
                exit;
            }
        
        }
    
    }
    
    /**
     * Add own action to product bulk action list
     *
     * @param array $bulk_actions
     *
     * @return mixed
     */
    public function register_bulk_pin_action( $bulk_actions )
    {
        if ( $this->account->isLoggedIn() ) {
            $bulk_actions['premmerce_pinterest_bulk_pin'] = __( 'Pin feature image', 'premmerce-pinterest' );
        }
        return $bulk_actions;
    }
    
    /**
     * Handle pin bulk action
     *
     * @param string $redirect_to
     * @param string $action
     * @param array $post_ids
     *
     * @return mixed
     */
    public function bulk_pin_action_handler( $redirect_to, $action, $post_ids )
    {
        if ( $action !== 'premmerce_pinterest_bulk_pin' ) {
            return $redirect_to;
        }
        $count = 0;
        foreach ( $post_ids as $product_id ) {
            if ( $this->pinFeatured( $product_id ) ) {
                $count++;
            }
        }
        $this->messenger->message(
            $count . ' ' . __( 'products add to queue ', 'premmerce-pinterest' ),
            'updated',
            Messenger::GLOBAL_SHOW,
            true
        );
        return $redirect_to;
    }
    
    /**
     * Pin product featured image
     *
     * @param int $product_id
     *
     * @return bool
     */
    private function pinFeatured( $product_id )
    {
        $attachment_id = (int) get_post_thumbnail_id( $product_id );
        $username = $this->account->getUserName();
        if ( !$attachment_id ) {
            return false;
        }
        $item = $this->model->getWhere( null, array(
            'post_id'       => $product_id,
            'pin_username'  => $username,
            'attachment_id' => $attachment_id,
            'action'        => array( 'IN', array( 'create', 'update' ) ),
        ) );
        if ( empty($item) ) {
            if ( $this->model->pin( $product_id, array( $attachment_id ), $username ) ) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Call on 'save_post' hook. Sync pinned post with DB. Create task for create and remove pins
     *
     * @param int      $post_id
     * @param \WP_Post $post
     */
    public function pinImage( $post_id, $post )
    {
        if ( !$this->account->isLoggedIn() || $post->post_type != 'product' || $post->post_status != 'publish' ) {
            return;
        }
        $postPins = ( !empty($_POST['premmerce_pinterest_images']) && !empty($_POST['is_pinned_post']) ? (array) $_POST['premmerce_pinterest_images'] : array() );
        $username = $this->account->getUserName();
        if ( !$username ) {
            return;
        }
        $dbPins = $this->model->getWhere( 'attachment_id', array(
            'post_id'      => $post_id,
            'action'       => array( 'IN', array( 'create', 'update' ) ),
            'pin_username' => $username,
        ) );
        $forPin = array_diff( $postPins, $dbPins );
        $forRemove = array_diff( $dbPins, $postPins );
        if ( !empty($forPin) ) {
            $this->model->pin( $post_id, $forPin, $username );
        }
    }
    
    /**
     * Handle pinterest login from account tab
     */
    public function handleLogin()
    {
        if ( !wp_verify_nonce( $_POST['_wpnonce'], 'premmerce_pinterest_login' ) ) {
            return;
        }
        $login = $_POST['pinterest_login'];
        $password = $_POST['pinterest_password'];
        if ( $login && $password ) {
            
            if ( !$this->account->login( $login, $password ) ) {
                $this->messenger->message(
                    __( 'Invalid username or password', 'premmerce-pinterest' ),
                    'error',
                    Messenger::LOCAL_SHOW,
                    true
                );
            } else {
                $this->messenger->message(
                    __( 'Connected to Pinterest', 'premmerce-pinterest' ) . '!',
                    'updated',
                    Messenger::LOCAL_SHOW,
                    true
                );
                $this->setDefaultBoard();
                wp_redirect( admin_url( 'admin.php?page=premmerce-pinterest-page&tab=settings' ) );
                exit;
            }
        
        }
        wp_redirect( wp_get_referer() );
        exit;
    }
    
    /**
     * Handle pinterest logout from account tab
     */
    public function handleLogOut()
    {
        if ( !wp_verify_nonce( $_POST['_wpnonce'], 'premmerce_pinterest_logout' ) ) {
            return;
        }
        $this->account->logout();
        $redirect_url = get_admin_url( null, 'admin.php?page=premmerce-pinterest-page' );
        $this->messenger->message(
            __( 'You have been logged out', 'premmerce-pinterest' ),
            'updated',
            Messenger::LOCAL_SHOW,
            true
        );
        wp_redirect( $redirect_url );
        exit;
    }
    
    /**
     * Handle when user login at pinterest from account page.
     * If not already has default board set first from user boards list and notify about it.
     * Or if notify about user don`t have any boards at pinterest
     */
    public function setDefaultBoard()
    {
        $boards = $this->account->getUserBoards();
        
        if ( empty($boards) ) {
            $this->messenger->message(
                __( 'You don\'t have any Pinterest boards, please create', 'premmerce-pinterest' ),
                'notice-warning',
                Messenger::LOCAL_SHOW,
                true
            );
            update_option( 'premmerce_pinterest_default_board', false );
        } else {
            
            if ( !$this->account->boardExist() ) {
                $id = $boards[0]['id'];
                $name = '<span style="color:green">' . $boards[0]['name'] . '</span>';
                $message = sprintf( __( 'We choose %s as default board. You can change it at settings tab', 'premmerce-pinterest' ), $name );
                $this->messenger->message( $message, 'notice-warning' );
                update_option( 'premmerce_pinterest_default_board', $id );
            }
        
        }
    
    }

}