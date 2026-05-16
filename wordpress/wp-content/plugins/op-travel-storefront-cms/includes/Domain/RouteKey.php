<?php

namespace OPTravelStorefrontCMS\Domain;

use InvalidArgumentException;

final class RouteKey
{
    private const ROUTE_TYPES = [
        'home',
        'shop_archive',
        'product_single_default',
        'page',
    ];

    public static function supportedTypes()
    {
        return self::ROUTE_TYPES;
    }

    public static function fromParts($routeType, $targetId = 0)
    {
        $routeType = self::normalizeString($routeType);

        if (! in_array($routeType, self::ROUTE_TYPES, true)) {
            throw new InvalidArgumentException('Unsupported storefront route type: ' . $routeType);
        }

        if ($routeType === 'page') {
            $targetId = (int) $targetId;

            if ($targetId <= 0) {
                throw new InvalidArgumentException('Page storefront routes require a positive page id.');
            }

            return 'page:' . $targetId;
        }

        return $routeType;
    }

    public static function parse($routeKey)
    {
        $routeKey = self::normalizeString($routeKey);

        if ($routeKey === '') {
            return null;
        }

        if (in_array($routeKey, self::ROUTE_TYPES, true) && $routeKey !== 'page') {
            return [
                'route_type' => $routeKey,
                'target_id' => 0,
                'route_key' => $routeKey,
            ];
        }

        if (strpos($routeKey, 'page:') === 0) {
            $targetId = (int) substr($routeKey, 5);

            if ($targetId <= 0) {
                return null;
            }

            return [
                'route_type' => 'page',
                'target_id' => $targetId,
                'route_key' => 'page:' . $targetId,
            ];
        }

        return null;
    }

    public static function isSupported($routeKey)
    {
        return self::parse($routeKey) !== null;
    }

    private static function normalizeString($value)
    {
        return trim((string) $value);
    }
}
