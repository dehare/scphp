<?php

namespace Dehare\SCPHP;

use Dehare\SCPHP\Command\Command;

class API
{
    const FLAG_RAW         = 'raw';
    const FLAG_UNWRAP      = 'unwrap';
    const FLAG_FILL_KEYS   = 'fill_keys';
    const FLAG_COUNT_ONLY  = 'count_only';
    const FLAG_UNWRAP_KEYS = 'unwrap_keys';

    private static $cli = [];

    public static function getConfig($key)
    {
        if (! isset(self::$cli[$key])) {
            $path = __DIR__ . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . $key . '.php';
            if (! is_file($path)) {
                throw new \InvalidArgumentException('No LMS CLI configuration available for "' . $key . '"');
            }
            self::$cli[$key] = (include $path);
        }

        return self::$cli[$key];
    }

    /**
     * Get possible flags
     * @param int|null $query Get flags applicable to this query type
     * @return array [Flag => [application, description]]
     */
    public static function getFlags($query = null)
    {
        $result = (include __DIR__ . DIRECTORY_SEPARATOR . 'flags.php');

        if ($query) {
            $result = array_filter($result, function ($v) use ($query) {
                return in_array($query, $v['query']);
            });
        }

        return $result;
    }

    public static function filterFlags(Command $command, array $flags)
    {
        $query         = $command->getQuery();
        $default_flags = $command->getFlags();

        $possible = array_keys(self::getFlags($query));
        $flags    = array_keys(array_filter(array_replace($default_flags, $flags)));

        return array_diff_assoc($flags, $possible);
    }
}