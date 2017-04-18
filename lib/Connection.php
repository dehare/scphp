<?php

namespace Dehare\SCPHP;

use Dehare\SCPHP\Exception\ConnectionException;

class Connection
{
    private static $hostname = null;
    private static $port     = '9090';
    private static $username = null;
    private static $password = null;

    /**
     * @var resource
     */
    private static $connection;

    public function __construct($hostname = null, $port = '9090', $username = null, $password = null)
    {
        self::$hostname = $hostname;
        self::$port     = $port ?: self::$port;
        self::$username = $username;
        self::$password = $password;


        $this->connect();
    }

    /*public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    public function setPort($port)
    {
        $this->port = $port;
    }
    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }*/

    /**
     * @return resource
     */
    public static function socket()
    {
        if (! self::$connection) {
            self::connect();
        }

        return self::$connection;
    }

    private static function connect()
    {
        if (empty(self::$hostname)) {
            self::findServer();
        }

        if (! self::$hostname) {
            throw new ConnectionException('Connection failed: No host selected', 500);
        }

        self::$connection = fsockopen(self::$hostname, self::$port, $err);
        if (self::$connection === false) {
            throw new ConnectionException('Connection failed: ' . $err);
        }
        socket_set_timeout(self::$connection, 10, 0);

        if (Request::query('secured')) {
            if (empty(self::$username) && empty(self::$password)) {
                throw new ConnectionException('Login required', 401);
            }

            $success = Request::execute('login '.self::$username.' '.self::$password);
            if (!$success) {
                throw new ConnectionException('Login failed', 406);
            }
        }

        return true;
    }

    /**
     * Loop through network searching for valid LMS host
     */
    private function findServer()
    {

    }
}