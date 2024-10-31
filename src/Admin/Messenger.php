<?php namespace Premmerce\Pinterest\Admin;

use Premmerce\SDK\V2\FileManager\FileManager;

class Messenger
{
    /**
     * @var array
     */
    private $messages = array();

    /**
     * @var array
     */
    private $flash_messages = array();

    /**
     * @var FileManager
     */
    private $fileManager;

    const LOCAL_SHOW = 0;

    const GLOBAL_SHOW = 1;

    /**
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Add message
     *
     * @param string $message
     * @param string $type
     * @param int $show
     * @param bool $flash
     */
    public function message($message, $type = 'error', $show = self::LOCAL_SHOW, $flash = false)
    {
        $message = array(
            'type' => $type,
            'text' => $message,
            'show' => $show
        );

        if ($flash) {
            $this->flash_messages[] = $message;
        } else {
            $this->messages[] = $message;
        }
    }

    /**
     * Show messages depending on $show argument
     *
     * @param int $show
     */
    public function showMessages($show = self::LOCAL_SHOW)
    {
        $messages = get_option('premmerce_pinterest_notice', array()) + $this->messages;

        if ($show == self::GLOBAL_SHOW) {
            $messages = array_filter($messages, function ($item) {
                return $item['show'] == self::GLOBAL_SHOW;
            });
        }

        if (! empty($messages)) {
            add_action('admin_notices', function () use ($messages) {
                $this->fileManager->includeTemplate('admin/notice.php', array('messages' => $messages));
            });
        }
    }

    /**
     * Update options
     */
    public function saveMessage()
    {
        if (get_option('premmerce_pinterest_notice') != $this->flash_messages) {
            update_option('premmerce_pinterest_notice', $this->flash_messages);
        }
    }

    /**
     * Remove message
     * @param bool $flash
     */
    public function removeMessages($flash = false)
    {
        if ($flash) {
            $this->flash_messages = array();
        } else {
            $this->messages = array();
        }
    }
}
