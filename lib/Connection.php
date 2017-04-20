<?php

namespace Dehare\SCPHP;

use Dehare\SCPHP\Exception\ConnectionException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class Connection
{
    const CLI_PORT = 9090;

    private static $hostname = null;
    private static $port     = self::CLI_PORT;
    private static $username = null;
    private static $password = null;

    /**
     * @var FilesystemAdapter
     */
    private static $cache;

    /**
     * @var resource
     */
    private static $connection;

    public static function setHostname($hostname)
    {
        self::$hostname = $hostname;
    }

    public static function setPort($port)
    {
        self::$port = $port;
    }

    public static function setUsername($username)
    {
        self::$username = $username;
    }

    public static function setPassword($password)
    {
        self::$password = $password;
    }

    /**
     * Get CLI connection
     * @return resource
     */
    public static function socket()
    {
        if (! self::$connection) {
            self::connect();
        }

        return self::$connection;
    }

    /**
     * Connect to CLI
     *
     * @return bool
     * @throws ConnectionException
     */
    public static function connect()
    {
        self::$cache = new FilesystemAdapter('', 604800, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'cache');

        if (empty(self::$hostname)) {
            self::findServer();
        }

        if (! self::$hostname) {
            throw new ConnectionException('Connection failed: No host selected', 500);
        }

        self::$connection = @fsockopen(self::$hostname, self::$port, $errno, $err);
        if (self::$connection === false) {
            throw new ConnectionException($err . ' on ' . self::$hostname . ':' . self::$port);
        }
        socket_set_timeout(self::$connection, 10, 0);

        if (Request::query('secured')) {
            $cache = self::$cache->getItem('credentials.' . str_replace('.', '-', self::$hostname));
            if ($cache->isHit()) {
                $credentials    = $cache->get();
                self::$username = self::$username ?: $credentials['username'];
                self::$password = self::$password ?: $credentials['password'];
            }

            if (empty(self::$username) && empty(self::$password)) {
                throw new ConnectionException('Login required', 401);
            }

            $success = Request::query('login', ['username' => self::$username, 'password' => self::$password]);
            if (! $success) {
                throw new ConnectionException('Login failed', 406);
            }

            self::saveCredentials(self::$username, self::$password);
        }

        return true;
    }

    /**
     * Loop through network searching for valid LMS host
     *
     * Saved host and port to cache file for future connections
     */
    private static function findServer()
    {
        $cache = self::$cache->getItem('hostname');
        if ($cache->isHit()) {
            $known_host     = $cache->get();
            self::$hostname = $known_host['hostname'];
            self::$port     = $known_host['port'];

            return;
        }

        $ip_addr  = getHostByName(gethostname());
        $ip_range = explode('.', $ip_addr);
        unset($ip_range[3]);

        $ports = [self::$port];
        if (self::$port != self::CLI_PORT) {
            $ports[] = self::CLI_PORT;
        }

        $success = false;
        $port    = self::knockIP($ip_addr, $ports);
        if ($port) {
            die("$ip_addr:$port");
        }

        $ip_range = implode('.', $ip_range);
        $machine  = 0;
        while ($success == false) {
            $machine++;
            if ($machine > 999) {
                throw new ConnectionException('Exhausted IP range. Please supply ip address and port.');
            }

            $ip_addr = $ip_range . '.' . $machine;
            $success = self::knockIP($ip_addr, $ports);
        }

        if ($success) {
            self::$hostname = $ip_addr;
            self::$port     = $success;

            $cache->set([
                'hostname' => $ip_addr,
                'port'     => $success,
            ]);
            self::$cache->save($cache);
        }
    }

    /**
     * Test a host for applicable CLI ports
     *
     * @param       $ip_addr
     * @param array $ports
     *
     * @return bool|mixed
     */
    private function knockIP($ip_addr, array $ports)
    {
        foreach ($ports as $port) {
            $success = @fsockopen($ip_addr, $port, $errno, $err, 1);
            if ($success) {
                return $port;
            }
        }

        return false;
    }

    /**
     * Save login credentials to cache
     *
     * Intended to be called from client application
     *
     * @param string $username
     * @param string $password
     */
    public static function saveCredentials($username, $password)
    {
        if (! self::$cache) {
            self::$cache = new FilesystemAdapter('', 604800, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'cache');
        }

        if (self::$hostname) {
            $cache = self::$cache->getItem('credentials.' . str_replace('.', '-', self::$hostname));
            $cache->set(compact('username', 'password'));

            self::$cache->save($cache);
        }
    }
}