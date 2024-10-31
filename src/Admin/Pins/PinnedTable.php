<?php

namespace Premmerce\Pinterest\Admin\Pins;

use  Premmerce\SDK\V2\FileManager\FileManager ;
use  Premmerce\Pinterest\PinterestModel ;
use  WP_List_Table ;
if ( !class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class PinnedTable extends WP_List_Table
{
    /**
     * @var FileManager $fileManager
     */
    private  $fileManager ;
    /**
     * @var PinterestModel $model
     */
    private  $model ;
    /**
     * PriceTypesTable constructor.
     *
     * @param FileManager $fileManager
     * @param PinterestModel $model
     */
    public function __construct( FileManager $fileManager, PinterestModel $model )
    {
        parent::__construct( array(
            'singular' => 'pinned',
            'plural'   => 'pinned',
            'ajax'     => false,
        ) );
        $this->fileManager = $fileManager;
        $this->model = $model;
        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
    }
    
    /**
     * Render data for cell checkbox
     *
     * @param object $item
     * @return string
     */
    protected function column_cb( $item )
    {
        return '<input type="checkbox" name="pinterest_pinned[]" id="cb-select-' . $item->id . '" value="' . $item->id . '">';
    }
    
    /**
     * Render data for product name
     *
     * @param object $item
     * @return string
     */
    protected function column_product( $item )
    {
        $product = wc_get_product( $item->post_id );
        if ( $product ) {
            return '<a href="' . get_edit_post_link( $item->post_id ) . '">' . $product->get_name() . '</a>';
        }
    }
    
    /**
     * Render image column
     *
     * @param object $item
     * @return string
     */
    protected function column_image( $item )
    {
        
        if ( wp_get_attachment_image_src( $item->attachment_id ) ) {
            $url = wp_get_attachment_image_src( $item->attachment_id )[0];
            return '<img src="' . $url . '" width="50px;"/>';
        }
    
    }
    
    /**
     * Render username field
     *
     * @param object $item
     * @return string
     */
    protected function column_username( $item )
    {
        return '<span class="pinterest_username">' . $item->pin_username . '</span>';
    }
    
    /**
     * Render date_created field
     *
     * @param object $item
     * @return string
     */
    protected function column_date_created( $item )
    {
        return date_i18n( get_option( 'date_format' ), strtotime( $item->date_created ) );
    }
    
    /**
     * Return array with columns titles
     *
     * @return array
     */
    public function get_columns()
    {
        return array(
            'cb'           => '<input type="checkbox">',
            'image'        => '<span class="dashicons dashicons-format-image"></span>',
            'product'      => __( 'Name', 'premmerce-pinterest' ),
            'date_created' => __( 'Created', 'premmerce-pinterest' ),
            'username'     => '<span class="dashicons dashicons-admin-users"></span>',
        );
    }
    
    /**
     * Generate row actions
     *
     * @param object $item
     * @param string $column_name
     * @param string $primary
     * @return string
     */
    protected function handle_row_actions( $item, $column_name, $primary )
    {
        if ( 'product' !== $column_name ) {
            return '';
        }
        $actions = array();
        return $this->row_actions( $actions );
    }
    
    /**
     * Set actions list for bulk
     *
     * @return array
     */
    protected function get_bulk_actions()
    {
        $actions = array();
        return $actions;
    }
    
    /**
     * Set items data in table
     */
    public function prepare_items()
    {
        $args = array(
            'pin_username' => get_option( 'premmerce_pinterest_username', '' ),
            'done'         => 1,
        );
        $total = $this->model->getRowCount( $args, true )->count;
        $perPage = 10;
        $currentPage = $this->get_pagenum();
        $totalItems = $total;
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage,
        ) );
        $offset = ($currentPage - 1) * $perPage;
        $data = $this->model->getWhere(
            null,
            $args,
            'action_order',
            'DESC',
            $perPage,
            $offset
        );
        $this->items = $data;
    }
    
    /**
     * Render if no items
     */
    public function no_items()
    {
        _e( 'No pinned pins', 'premmerce-pinterest' );
    }

}