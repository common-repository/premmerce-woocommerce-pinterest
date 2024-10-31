<?php namespace Premmerce\Pinterest\Admin\Queue;

use Premmerce\SDK\V2\FileManager\FileManager;
use Premmerce\Pinterest\PinterestModel;
use WP_List_Table;

if (! class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class QueueTable extends WP_List_Table
{
    /**
     * @var FileManager $fileManager
     */
    private $fileManager;

    /**
     * @var PinterestModel $model
     */
    private $model;

    /**
     * @var array $items_count
     */
    private $items_count = array();

    /**
     * PriceTypesTable constructor.
     *
     * @param FileManager                         $fileManager
     * @param \Premmerce\Pinterest\PinterestModel $model
     */
    public function __construct(FileManager $fileManager, PinterestModel $model)
    {
        parent::__construct(array(
            'singular' => 'queue',
            'plural'   => 'queue',
            'ajax'     => false,
        ));

        $this->fileManager = $fileManager;
        $this->model       = $model;

        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );

        $args = array(
            'pin_username' => get_option('premmerce_pinterest_username', ''),
            'done'         => array( '<', 1 ),
        );

        $this->items_count =  $this->model->getRowCount($args);
    }

    /**
     * Render data for cell checkbox
     *
     * @param object $item
     * @return string
     */
    protected function column_cb($item)
    {
        return '<input type="checkbox" name="pinterest_tasks[]" id="cb-select-' . $item->id . '" value="' . $item->id . '">';
    }

    /**
     * Render data for cell checkbox
     *
     * @param object $item
     * @return string
     */
    protected function column_action($item)
    {
        $status = '';
        $action = '';
        switch ($item->action) {
            case 'remove':
                $status = 'pin-to-remove';
                $action = __('Remove', 'premmerce-pinterest');
                break;
            case 'create':
                $status = 'pin-to-create';
                $action = __('Create', 'premmerce-pinterest');
                break;
            case 'update':
                $status = 'pin-to-update';
                $action = __('Update', 'premmerce-pinterest');
                break;
        }
        return '<span class="pin-status ' . $status . '">' . $action . '</span>';
    }

    /**
     * Render data for product name
     *
     * @param object $item
     * @return string
     */
    protected function column_product($item)
    {
        $product = wc_get_product($item->post_id);
        if ($product) {
            return '<a href="' . get_edit_post_link($item->post_id) . '">' . $product->get_name() . '</a>';
        }
        return '';
    }

    /**
     * Render image column
     *
     * @param object $item
     * @return string
     */
    protected function column_image($item)
    {
        if (! empty(wp_get_attachment_image_src($item->attachment_id)[0])) {
            $url =  wp_get_attachment_image_src($item->attachment_id)[0];
            return '<img src="' . $url . '" width="50px;"/>';
        }
        return '';
    }

    /**
     * Render column status
     *
     * @param object $item
     * @return string
     */
    protected function column_status($item)
    {
        $status  = 'pin-to-create';
        $message = __('Processing', 'premmerce-pinterest');
        $error_message = '';

        if ($item->done == -1) {
            $status = 'pin-to-remove';
            $message = __('fail', 'premmerce-pinterest');
            $error_message = $item->data->error_message;
        }

        $msg = '<span class="pin-status ' . $status . '">' . $message . '</span>';
        if ($error_message) {
            $msg .=  wc_help_tip($error_message);
        }
        return $msg;
    }

    /**
     * Render username field
     *
     * @param object $item
     * @return string
     */
    protected function column_username($item)
    {
        return '<span class="pinterest_username">' . $item->pin_username . '</span>';
    }

    /**
     * Return array with columns titles
     *
     * @return array
     */
    public function get_columns()
    {
        return  array(
            'cb'        => '<input type="checkbox">',
            'image'     => '<span class="dashicons dashicons-format-image"></span>',
            'product'   => __('Name', 'premmerce-pinterest'),
            'action'    => __('Action', 'premmerce-pinterest'),
            'username'  => '<span class="dashicons dashicons-admin-users"></span>',
            'status'    => __('Status', 'premmerce-pinterest'),
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
    protected function handle_row_actions($item, $column_name, $primary)
    {
        if ('product' !== $column_name) {
            return '';
        }

        $actions['delete'] = vsprintf(
          '<a class="submitdelete" href="%s&action=%s&task_id=%d" data-action--delete>%s</a>',
          array(
              wp_nonce_url(admin_url('admin-post.php'), $item->id),
              'premmerce_pinterest_delete_task',
              $item->id,
              __('Delete from queue', 'premmerce-pinterest'),
          )
      );

        if (isset($item->data->can_retry) &&  $item->data->can_retry) {
            $actions['retry'] = vsprintf(
              '<a href="%s&action=%s&task_id=%d" data-action--delete>%s</a>',
              array(
                  wp_nonce_url(admin_url('admin-post.php'), $item->id),
                  'premmerce_pinterest_retry_task',
                  $item->id,
                  '<span class="dashicons dashicons-update"></span> ' . __('Retry', 'premmerce-pinterest'),
              )
          );
        }

        return $this->row_actions($actions);
    }

    /**
     * Set actions list for bulk
     *
     * @return array
     */
    protected function get_bulk_actions()
    {
        $actions = array(
            'premmerce_pinterest_delete_task' => __('Delete from queue', 'premmerce-pinterest'),
        );

        if (! empty($_GET['done']) &&  $_GET['done'] == -1) {
            $actions[ 'premmerce_pinterest_retry_task' ] = __('Retry', 'premmerce-pinterest');
        }

        return $actions;
    }

    /**
     * Set items data in table
     */
    public function prepare_items()
    {
        $args = array(
            'pin_username' => get_option('premmerce_pinterest_username', ''),
            'done'         => array( '<', 1 ),
        );

        $total = $this->items_count[ 'in_queue' ];

        if (isset($_GET['done'])) {
            $args[ 'done' ] = $_GET[ 'done' ];
            $total = $this->items_count[ $_GET[ 'done' ] ];
        }

        $perPage        = 10;
        $currentPage    = $this->get_pagenum();
        $totalItems     = $total;

        $this->set_pagination_args(array(
            'total_items' => $totalItems,
            'per_page'    => $perPage,
        ));

        $offset = (($currentPage - 1) * $perPage);

        $data = $this->model->getWhere(null, $args, 'action_order', 'ASC', $perPage, $offset);
        $this->items = $data;
    }

    /**
     * Render if no items
     */
    public function no_items()
    {
        _e('No tasks in queue', 'premmerce-pinterest');
    }

    /**
     *
     * @return array
     */
    protected function get_views()
    {
        $status_links = array(
            "all" => vsprintf("<a href='%s' " . $this->curr('all') . ">%s</a>", array(
                    admin_url('admin.php?page=premmerce-pinterest-page&tab=queue'),
                    sprintf(__('All (%d)', 'premmerce-pinterest'), $this->items_count[ 'in_queue' ])
                )),

            "in_processing" => vsprintf("<a href='%s' " . $this->curr('0') . " >%s</a>", array(
                admin_url('admin.php?page=premmerce-pinterest-page&tab=queue&done=0'),
                sprintf(__('Processing', 'premmerce-pinterest') . ' (%d)', $this->items_count[ '0' ])
            )),

            "failed" => vsprintf("<a href='%s' " . $this->curr('-1') . ">%s</a>", array(
                admin_url('admin.php?page=premmerce-pinterest-page&tab=queue&done=-1'),
                sprintf(__('Failed (%d)', 'premmerce-pinterest'), $this->items_count[ '-1' ])
            )),
        );

        return $status_links;
    }

    /**
     * Helper function
     *
     * @param string $current
     * @return string
     */
    private function curr($current)
    {
        if (isset($_GET['done'])) {
            if ($_GET['done'] == $current) {
                return 'class="current"';
            }
        } elseif ($current == 'all') {
            return 'class="current"';
        }
        return '';
    }
}
