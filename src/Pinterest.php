<?php namespace Premmerce\Pinterest;

use seregazhuk\PinterestBot\Factories\PinterestBot;
use WC_Product;

class Pinterest
{
    /**
     * @var PinterestBot
     */
    private $account;

    /**
     * @var array
     */
    private $placeholders;

    /**
     * Pinterest constructor.
     * @var string $cookieDirectory;
     *
     */
    public function __construct($cookieDirectory = null)
    {
        $this->account = PinterestBot::create();

        if (! is_null($cookieDirectory)) {
            $this->setCookieDirectory($cookieDirectory);
        }

        $this->cookieLogin();
    }

    /**
     * @return PinterestBot
     */
    public function getBotInstance()
    {
        return $this->account;
    }

    /**
     * Login using cookie
     *
     * @return bool
     */
    public function cookieLogin()
    {
        $login    = get_option('premmerce_pinterest_login');
        $password = get_option('premmerce_pinterest_password');

        if ($login && $password) {
            if ($this->account->auth->login($login, $password, true)) {
                return true;
            } else {
                $this->setOptionsLogOut();
            }
        }
        return false;
    }

    /**
     * Login using credential
     *
     * @param string $login
     * @param static $password
     * @param bool $auto
     * @return bool
     */
    public function login($login, $password, $auto = true)
    {
        if ($this->account->auth->login($login, $password, $auto)) {
            update_option('premmerce_pinterest_login', $login);
            update_option('premmerce_pinterest_password', $password);
            update_option('premmerce_pinterest_username', $this->getUserName());

            return true;
        } else {
            $this->setOptionsLogOut();
        }

        return false;
    }

    /**
     * Logout. $force - true if remove cookie also
     *
     * @param bool $force
     */
    public function logout($force = true)
    {
        $this->account->auth->logout();

        $this->setOptionsLogOut();

        if ($force) {
            $this->account->getHttpClient()->removeCookies();
        }
    }

    /**
     * Create new pin
     *
     * @param string $image
     * @param int $board
     * @param string $description
     * @param string $link
     * @return array
     */
    public function createPin($image, $board, $description, $link)
    {
        return $this->account->pins->create($image, $board, $description, $link);
    }

    /**
     * Edit exist pin
     *
     * @param int $pinId
     * @param string $description
     * @param string $link
     * @param int $board
     * @return bool
     */
    public function editPin($pinId, $description, $link, $board)
    {
        return $this->account->pins->edit($pinId, $description, $link, $board);
    }

    /**
     * Delete pin
     *
     * @param int $pinId
     * @return bool
     */
    public function deletePin($pinId)
    {
        return $this->account->pins->delete($pinId);
    }

    /**
     * Return user boards list
     *
     * @return array
     */
    public function getUserBoards()
    {
        return $this->account->boards->forMe();
    }

    /**
     * Check if is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->account->auth->isLoggedIn();
    }

    /**
     * Check if is banned
     *
     * @return bool
     */
    public function isBanned()
    {
        return $this->account->user->isBanned();
    }

    /**
     * @param string $cookieDirectory
     * @return \seregazhuk\PinterestBot\Api\Contracts\HttpClient
     */
    public function setCookieDirectory($cookieDirectory)
    {
        return $this->account->getHttpClient()->setCookiesPath($cookieDirectory);
    }

    /**
     * Return last error
     *
     * @return null|string
     */
    public function getError()
    {
        return $this->account->getLastError();
    }

    /**
     * Return current username
     *
     * @return bool|string
     */
    public function getUserName()
    {
        $username = get_option('premmerce_pinterest_username', '');
        if ($username) {
            return $username;
        }
        if ($this->isLoggedIn()) {
            return $this->account->user->username();
        }
        return false;
    }

    /**
     * Check if default board exist
     *
     * @return bool
     */
    public function boardExist()
    {
        $boards = $this->getUserBoards();
        $defaultBoard = get_option('premmerce_pinterest_default_board');

        if (! $defaultBoard  || ! in_array($defaultBoard, array_column($boards, 'id'))) {
            return false;
        }

        return true;
    }

    /**
     * Check basic requirements for work
     *
     * @return bool
     */
    public function checkRequirements()
    {
        return $this->isLoggedIn() && ! $this->isBanned() && $this->boardExist();
    }

    /**
     * Set placeholders for pin description
     *
     * @param WC_Product $product
     * @param int        $attachment_id
     */
    protected function setPlaceholders(WC_Product $product, $attachment_id)
    {
        $product_details = $product->get_data();
        $this->placeholders['{price}'] = get_woocommerce_currency_symbol() . $product->get_price();
        $this->placeholders['{title}'] = $product->get_title();

        if ($product instanceof \WC_Product_Variable) {
            $this->placeholders['{price}'] = strip_tags($product->get_price_html());

            foreach ($product->get_available_variations() as $variation) {
                if ($variation['image_id'] == $attachment_id) {
                    if ($variation) {
                        $this->placeholders['{price}'] = strip_tags(wc_price($variation['display_price']));
                    }
                    $this->placeholders['{title}'] = wc_get_product($variation['variation_id'])->get_name();
                }
            }
        }

        $this->placeholders['{site_title}']   = get_bloginfo('name');
        $this->placeholders['{link}']         = get_permalink($product->get_id());
        $this->placeholders['{description}']  = $product_details[ 'description' ];
        $this->placeholders['{excerpt}']      = $product_details[ 'short_description' ];
    }

    /**
     * Return formatted pin description
     *
     * @param            $product
     * @param null|int $attachment_id
     *
     * @return string
     */
    public function getPinDescription($product, $attachment_id)
    {
        $this->setPlaceholders($product, $attachment_id);

        $template = get_option('premmerce_pinterest_default_description');

        if (! $template) {
            return '';
        }

        return strtr($template, $this->placeholders);
    }

    /**
     * Reset all options after user log out
     *
     * @return void
     */
    public function setOptionsLogOut()
    {
        update_option('premmerce_pinterest_login', false);
        update_option('premmerce_pinterest_password', false);
        update_option('premmerce_pinterest_username', false);
    }
}
