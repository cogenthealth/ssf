<?php

namespace CogentHealth\Ssf\Services;

use App\Utils\Options;

class AuthService
{
    protected static $password;
    protected static $username;

    /**
     *
     * @var Singleton
     */
    private static $instance;

    public function __construct()
    {
        self::$username =  Options::get('ssf_settings')['ssf_username'] ?? '';
        self::$password =  Options::get('ssf_settings')['ssf_password'] ?? '';
    }

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getUsername()
    {
        self::init();
        return self::$username;
    }

    public static function getPassword()
    {
        self::init();
        return self::$password;
    }

}
