<?php namespace Premmerce\Pinterest;

use Premmerce\SDK\V2\FileManager\FileManager;
use Premmerce\Pinterest\Admin\Messenger;

class TaskManager
{
    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var Messenger
     */
    private $messenger;

    /**
     * @var PinterestModel
     */
    private $model;

    /**
     * @var Pinterest
     */
    private $account;

    /**
     * TaskManager constructor.
     *
     * @param FileManager $fileManager
     * @param PinterestModel $model
     */
    public function __construct(FileManager $fileManager, PinterestModel $model)
    {
        $this->fileManager = $fileManager;
        $this->model       = $model;
        $this->messenger   = new Messenger($fileManager);
        $this->account     = new Pinterest($this->fileManager->getPluginDirectory());
    }

    /**
     * Main method. Get one task and transfers management to particular function
     */
    public function execute()
    {
        if (! $this->account->checkRequirements()) {
            $this->messenger->message($this->account->getError(), 'error', Messenger::GLOBAL_SHOW, true);
        }

        $task = $this->model->getWhere(null, array(
            "done"         => 0,
            "pin_username" => $this->account->getUserName(),
        ), 'action_order', 'ASC', 1);

        if ($task && is_array($task)) {
            $task = $task[0];
        }

        if (! is_object($task)) {
            return;
        }

        $action = $task->action;

        if (method_exists($this, $action)) {
            call_user_func(array( $this, $action ), $task);
        }
    }

    /**
     * Send request to pinterest for create new pin
     *
     * @param object $task
     */
    public function create($task)
    {
        $product = wc_get_product($task->post_id);

        $description = $this->account->getPinDescription($product, $task->attachment_id);
        $link        = get_permalink($product->get_id());
        $board       = get_option('premmerce_pinterest_default_board');
        $image       = wp_get_attachment_image_src($task->attachment_id, 'full')[0];

        try {
            if ($pinInfo = $this->account->createPin($image, $board, $description, $link)) {
                $this->model->updateToDone($task->id, $pinInfo['id']);
            } else {
                $data['error_message'] = $this->account->getError();
                $data['can_retry']     = true;
                $this->model->updateToFail($task->id, $data);
            }
        } catch (\Exception $e) {
            $this->model->updateToFail($task->id, $data);
            wc_get_logger()->error($e->getMessage(), array( 'source' => 'premmerce-pinterest' ));
        }
    }

    /**
     * Send request to pinterest for update new pin
     *
     * @param object $task
     */
    public function update($task)
    {
        if (! $task->pin_id) {
            $data['error_message'] = __('Pin has not pin_id', 'premmerce-pinterest');
            $this->model->updateToFail($task->id, $data);
        } else {
            $product     = wc_get_product($task->post_id);
            $description = $this->account->getPinDescription($product, $task->attachment_id);
            $link        = get_permalink($product->get_id());
            $board       = get_option('premmerce_pinterest_default_board');

            try {
                if ($pinInfo = $this->account->editPin($task->pin_id, $description, $link, $board)) {
                    $this->model->updateToDone($task->id);
                } else {
                    $data['error_message'] = $this->account->getError();
                    $data['can_retry']     = true;
                    $this->model->updateToFail($task->id, $data);
                }
            } catch (\Exception $e) {
                $this->model->updateToFail($task->id, $data);
                wc_get_logger()->error($e->getMessage(), array( 'source' => 'premmerce-pinterest' ));
            }
        }
    }

    /**
     * Send request to pinterest for update new pin
     *
     * @param object $task
     */
    public function remove($task)
    {
        if (! $task->pin_id) {
            $data['error_message'] = __('Pin has not pin_id', 'premmerce-pinterest');
            $this->model->updateToFail($task->id, $data);
        } else {
            try {
                if ($this->account->deletePin($task->pin_id)) {
                    $this->model->deleteWhere(array( 'pin_id' => $task->pin_id ));
                } else {
                    $data['error_message'] = $this->account->getError();
                    $data['can_retry']     = true;
                    $this->model->updateToFail($task->id, $data);
                }
            } catch (\Exception $e) {
                $this->model->updateToFail($task->id, $data);
                wc_get_logger()->error($e->getMessage(), array( 'source' => 'premmerce-pinterest' ));
            }
        }
    }
}
