<?php namespace Premmerce\Pinterest\Admin\Queue;

use Premmerce\SDK\V2\FileManager\FileManager;
use Premmerce\Pinterest\PinterestModel;
use Premmerce\Pinterest\Admin\Messenger;
use Premmerce\Pinterest\Pinterest;

class QueueService
{
    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var Pinterest
     */
    private $account;

    /**
     * @var Messenger
     */
    private $messenger;

    /**
     * @var PinterestModel
     */
    private $model;

    /**
     * Boot service
     *
     * @param FileManager $fileManager
     * @param PinterestModel $model
     * @param Pinterest $account
     * @param Messenger $messenger
     */
    public function boot($fileManager, $model, $account, $messenger)
    {
        $this->fileManager = $fileManager;
        $this->model       = $model;
        $this->account     = $account;
        $this->messenger   = $messenger;

        $this->hooks();
    }

    /**
     * Register hooks
     */
    public function hooks()
    {
        // Handle other action
        add_action('admin_post_-1', array($this, 'handleAction'), 9999);

        // Queue action handlers
        add_action('admin_post_premmerce_pinterest_delete_task', array($this, 'handleDeleteTask'));
        add_action('admin_post_premmerce_pinterest_retry_task', array($this, 'handleRetryTask'));
    }

    public function handleAction()
    {
        $action2 = $_REQUEST['action2'];

        if (isset($action2) && $_REQUEST['page'] == 'premmerce-pinterest-page') {
            if ($action2 == 'premmerce_pinterest_delete_task') {
                $this->handleDeleteTask();
            }
            if ($action2 == 'premmerce_pinterest_retry_task') {
                $this->handleRetryTask();
            } elseif ($_REQUEST['action2'] == '-1' && $_REQUEST['action'] == '-1') {
                // todo: find better way
                $parts = parse_url(wp_get_referer());
                parse_str($parts['query'], $query);

                $_REQUEST['paged'] = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : 1;

                if (! key_exists('paged', $query)) {
                    $url = wp_get_referer() . "&paged=" . $_REQUEST['paged'];
                } else {
                    $url = str_replace('paged=' . $query['paged'], 'paged=' . $_REQUEST['paged'], wp_get_referer());
                }
                wp_redirect($url);
                exit;
            }
        }
    }

    /**
     * Call from bulk and row action. Handle delete task from queue list
     */
    public function handleDeleteTask()
    {
        $data = $_REQUEST;

        if (isset($data['task_id'])) {
            // Row action handle
            if (! wp_verify_nonce($data['_wpnonce'], $data['task_id'])) {
                return;
            }

            $id = (int)$data['task_id'];

            if (! $id && empty($id)) {
                return;
            }

            $result = $this->model->setBackWhere(array(
                    'id'     => $id,
                    'action' => array('IN', array('update', 'remove')),
                    'pin_id' => array("IS NOT", "NULL"),
                )) || $this->model->deleteWhere(array(
                    'id' => $id,
                ));

            $result && $this->messenger->message(__('Task has been deleted', 'premmerce-pinterest'), 'updated', Messenger::LOCAL_SHOW, true);
        } elseif (is_array($data['pinterest_tasks'])) {

            // Bulk action handle
            if (! wp_verify_nonce($data['_wpnonce'], 'bulk-queue')) {
                return;
            }

            $tasks_id = array_map('intval', $data['pinterest_tasks']);

            $setBackResult = $this->model->setBackWhere(array(
                'id'     => array('IN', $tasks_id),
                'action' => array('IN', array('update', 'remove')),
                'pin_id' => array("IS NOT", "NULL"),
                'done'   => array('!=', -1)
            ));

            $deleteFailedResult = $this->model->deleteWhere(array(
                'id'    => array('IN', $tasks_id),
                'done'  => array('=', -1)
            ));

            $deleteTryPinResult = $this->model->deleteWhere(array(
                'id'    => array('IN', $tasks_id),
                'pin_id' => array("IS", "NULL"),
            ));

            $result = $deleteTryPinResult || $deleteFailedResult || $setBackResult;

            $result && $this->messenger->message(__('Tasks has been deleted', 'premmerce-pinterest'), 'updated', Messenger::LOCAL_SHOW, true);
        }

        wp_redirect(wp_get_referer());
        exit;
    }

    /**
     * Call from row action at queue list. Recreate task and take it in the and of queue
     */
    public function handleRetryTask()
    {
        $data = $_REQUEST;

        if (isset($data['task_id'])) {

            // Row action handle
            if (! wp_verify_nonce($data['_wpnonce'], $data['task_id'])) {
                return;
            }

            $id = (int)$data['task_id'];

            if (! $id && empty($id)) {
                return;
            }

            $result = $this->model->retryWhere(array(
                'id' => $id,
            ));

            $result && $this->messenger->message(__('Task has been added to end of queue', 'premmerce-pinterest'), 'updated', Messenger::LOCAL_SHOW, true);
        } elseif (is_array($data['pinterest_tasks'])) {
            // Bulk action handle
            if (! wp_verify_nonce($data['_wpnonce'], 'bulk-queue')) {
                return;
            }

            $tasks_id = array_map(function ($id) {
                return (int)$id;
            }, $data['pinterest_tasks']);

            $result = $this->model->retryWhere(array(
                'id' => array('IN', $tasks_id),
            ));

            $result && $this->messenger->message(__('Tasks has been added to end of queue', 'premmerce-pinterest'), 'updated', Messenger::LOCAL_SHOW, true);
        }

        wp_redirect(wp_get_referer());
        exit;
    }
}
