<?php

namespace OPTravelCore\Support;

final class Env
{
    public static function get($key, $default = '')
    {
        $value = getenv($key);

        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}
