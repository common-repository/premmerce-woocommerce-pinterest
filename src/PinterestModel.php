<?php namespace Premmerce\Pinterest;

class PinterestModel
{
    /**
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * @var string $table
     */
    protected $table;

    /**
     * PinterestModel constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->wpdb  = $wpdb;
        $this->table = $this->wpdb->prefix . "premmerce_pinterest";
    }

    /**
     * Create table for plugin. Call at activation hook
     *
     * @return array|bool
     */
    public function createTable()
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        if ($this->wpdb->get_var("show tables like '$this->table'") != $this->table) {
            $sql = "CREATE TABLE " . $this->table . " (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  pin_username varchar(255) NOT NULL,
			  post_id mediumint(9) NOT NULL,
			  attachment_id mediumint(9) NOT NULL,
			  action varchar(255) NOT NULL,
			  done int(1) NOT NULL,
			  date_created datetime NOT NULL,
			  date_updated datetime,
			  data TEXT(10000),
			  pin_id varchar(255),
			  action_order mediumint(9) NOT NULL,
			  UNIQUE KEY id (id)
			) $charset_collate ;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            return dbDelta($sql);
        }

        return false;
    }

    /**
     * Main method to get data from plugin table
     *
     * @param string $column use to get particular column form table
     * @param array $args array of conditionals for query. Available: [ id => 1, [ id => ['<', 1] ], [ id => ['IN', [1,2,3,4] ] ] ]
     * @param string $orderBy order by column
     * @param string $order ASC\DESC
     * @param int|bool $limit limit of record
     * @param bool $offset
     *
     * @return array|null|object
     */
    public function getWhere($column, array $args, $orderBy = 'action_order', $order = 'ASC', $limit = false, $offset = false)
    {
        $condition = $this->parseCondition($args);

        $limit  = $limit ? "LIMIT $limit" : '';
        $offset = $offset ? "OFFSET $offset" : '';

        $sql = "SELECT * FROM {$this->table} {$condition} ORDER BY {$orderBy} {$order} {$limit} {$offset}";

        $result = $this->wpdb->get_results($sql);

        $result = array_map(function ($row) use ($column) {
            $row->data = json_decode($row->data);

            return $row;
        }, $result);

        if ($column) {
            $result = array_map(function ($row) use ($column) {
                return $row->$column;
            }, $result);
        }

        return $result;
    }

    /**
     * Delete row by condition
     *
     * @param array $args array of conditionals for query. Available: [ id => 1, [ id => ['<', 1] ], [ id => ['IN', [1,2,3,4] ] ] ]
     *
     * @return bool
     */
    public function deleteWhere(array $args)
    {
        $condition = $this->parseCondition($args);

        if (! $condition) {
            return false;
        }

        $sql = "DELETE FROM {$this->table} {$condition}";

        return $this->wpdb->query($sql);
    }

    /**
     * cancel back row by condition
     *
     * @param array $args array of conditionals for query. Available: [ id => 1, [ id => ['<', 1] ], [ id => ['IN', [1,2,3,4] ] ] ]
     *
     * @return bool
     */
    public function setBackWhere(array $args)
    {
        $condition = $this->parseCondition($args);

        $sql = "UPDATE {$this->table} SET `done` = 1, `action` = 'create' $condition";

        return $this->wpdb->query($sql);
    }

    /**
     * Add task create pin
     *
     * @param int $post_id
     * @param array $forPin
     * @param string $username
     *
     * @return false|int
     */
    public function pin($post_id, $forPin, $username)
    {
        $order = $this->getNewOrder();

        $sql    = "INSERT INTO {$this->table} ( `post_id`, `pin_username`, `attachment_id`, `action`, `done`, `date_created`, `data`, `action_order` ) VALUES";
        $values = array();

        foreach ($forPin as $pin) {
            $values[] = $this->wpdb->prepare(
                " ( %d, '%s', %d, '%s', %d, '%s', '%s', %d )",
                $post_id,
                $username,
                $pin,
                'create',
                0,
                date("Y-m-d H:i:s"),
                '',
                $order
            );
            $order    += 1;
        }

        $sql .= implode(",", $values);

        return $this->wpdb->query($sql);
    }

    /**
     * Add task remove pin
     *
     * @param int $post_id
     * @param array $forRemove
     * @param string $username
     *
     * @return false|int
     */
    public function setRemove($post_id, $forRemove, $username)
    {
        $forRemove = implode(", ", $forRemove);
        $order     = $this->getNewOrder();

        $sql = "DELETE FROM {$this->table} WHERE `post_id` = %d AND `done` = 0 AND `pin_username` = %s AND `action` = 'create' AND `attachment_id` IN ( $forRemove )";
        $sql = $this->wpdb->prepare($sql, $post_id, $username);
        $this->wpdb->query($sql);

        $sql = "UPDATE {$this->table} SET `action` = 'remove', `action_order` = %d, `done` = 0 WHERE `done` = 1 AND `post_id` = %d AND `pin_username` = %s AND `attachment_id` IN ( $forRemove )";
        $sql = $this->wpdb->prepare($sql, $order, $post_id, $username);
        return $this->wpdb->query($sql);
    }

    /**
     * Add task remove pin by id
     *
     * @param array $forRemoveIds
     *
     * @return false|int
     */
    public function setRemoveById($forRemoveIds)
    {
        $forRemove = implode(", ", $forRemoveIds);
        $order     = $this->getNewOrder();

        $sql = "DELETE FROM {$this->table} WHERE `action` = 'create' AND `done` = 0 AND `id` IN ( $forRemove )";

        $this->wpdb->query($sql);

        $sql = "UPDATE {$this->table} SET `action` = 'remove', `action_order` = $order, `done` = 0 WHERE  `done` = 1 AND `id` IN ( $forRemove )";

        return $this->wpdb->query($sql);
    }

    /**
     * Add task update
     *
     * @param int $post_id
     * @param string $username
     *
     * @return false|int
     */
    public function setUpdate($post_id, $username)
    {
        $order = $this->getNewOrder();
        $sql   = "UPDATE {$this->table} SET `action` = 'update', `done` = 0, `action_order` = $order WHERE  `done` = 1 AND `post_id` = $post_id AND `pin_username` = '$username' ";

        return $this->wpdb->query($sql);
    }

    /**
     * Add task update by args
     *
     * @param array $args
     *
     * @return false|int
     */
    public function setUpdateWhere(array $args)
    {
        $order     = $this->getNewOrder();
        $condition = $this->parseCondition($args);

        $sql = "UPDATE {$this->table} SET `action` = 'update', `done` = 0, `action_order` = {$order} {$condition}";

        return $this->wpdb->query($sql);
    }

    /**
     * Get new order
     *
     * @return int
     */
    private function getNewOrder()
    {
        $order = "SELECT MAX(action_order) as last_order FROM {$this->table}";
        $order = $this->wpdb->get_row($order);
        $order = is_null($order->last_order) ? 1 : (int)$order->last_order + 1;

        return $order;
    }

    /**
     * Update pin status to done
     *
     * @param int $id
     * @param int $pin_id
     *
     * @return false|int
     */
    public function updateToDone($id, $pin_id = null)
    {
        $date = date("Y-m-d H:i:s");

        if (is_null($pin_id)) {
            $sql = "UPDATE {$this->table} SET `done` = 1, `date_updated` = %s  WHERE `id` = %d";
            $sql = $this->wpdb->prepare($sql, $date, $id);
        } else {
            $sql = "UPDATE {$this->table} SET `done` = 1, `pin_id` = %d, `date_updated` = %s WHERE `id` = %d";
            $sql = $this->wpdb->prepare($sql, $pin_id, $date, $id);
        }

        return $this->wpdb->query($sql);
    }

    /**
     * Update pin status to fail
     *
     * @param int $id
     * @param array $data
     *
     * @return false|int
     */
    public function updateToFail($id, $data)
    {
        $order = $this->getNewOrder();

        $data = json_encode($data);
        $sql  = "UPDATE {$this->table} SET `done` = -1, `data` = '%s', `action_order` = %d WHERE `id` = %d";
        $sql  = $this->wpdb->prepare($sql, $data, $order, $id);

        return $this->wpdb->query($sql);
    }

    /**
     * Recreate task and take it in the and of queue
     *
     * @param array $args
     *
     * @return false|int
     */
    public function retryWhere($args)
    {
        $order     = $this->getNewOrder();
        $condition = $this->parseCondition($args);

        $sql = "UPDATE {$this->table} SET `done` = %d, `data` = %s, `action_order` = %d {$condition}";
        $sql = $this->wpdb->prepare($sql, 0, '', $order);

        return $this->wpdb->query($sql);
    }

    /**
     * Parse array to string sql condition
     *
     * @param array $args
     *
     * @return string
     */
    public function parseCondition($args)
    {
        if (empty($args)) {
            $condition = '';
        } else {
            $condition = '';
            if (! empty($args)) {
                $conditions = array();
                foreach ($args as $key => $value) {
                    if (is_array($value)) {
                        if (is_array($value[1])) {
                            $conditions[] = $this->parseIfString($key, $value);
                        } else {
                            $conditions[] = "$key $value[0] $value[1]";
                        }
                    } else {
                        if (is_string($value)) {
                            $value = "'" . $value . "'";
                        }
                        $conditions[] = "$key = $value";
                    }
                }
                $condition = "WHERE " . implode(' AND ', $conditions);
            }
        }
        return $condition;
    }

    /**
     * Get count of pin statuses
     *
     * @param null|array $args
     * @param bool $simple
     *
     * @return array
     */
    public function getRowCount($args = null, $simple = false)
    {
        $condition = $this->parseCondition($args);

        if ($simple) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} {$condition}";

            return $this->wpdb->get_row($sql);
        }

        $items_count = array();
        $sql         = "SELECT `done` as status, COUNT(*) as count FROM {$this->table} {$condition} GROUP BY `done`";

        $counts = $this->wpdb->get_results($sql);

        $items_count['all']      = 0;
        $items_count['in_queue'] = 0;
        $items_count['-1']       = 0;
        $items_count['1']        = 0;
        $items_count['0']        = 0;

        if (! empty($counts)) {
            foreach ($counts as $count) {
                $items_count["$count->status"] = (int)$count->count;
            }

            $items_count['in_queue'] = $items_count['-1'] + $items_count['0'];
            $items_count['all']      = $items_count['in_queue'] + $items_count['1'];
        }

        return $items_count;
    }

    /**
     * Return sql condition part
     *
     * @param string $key
     * @param array $value
     * @return string
     */
    public function parseIfString($key, $value)
    {
        if (is_string($value[1][0])) {
            return "$key $value[0] ('" . implode('\', \'', $value[1]) . "')";
        } else {
            return "$key $value[0] (" . implode(', ', $value[1]) . ")";
        }
    }
}
