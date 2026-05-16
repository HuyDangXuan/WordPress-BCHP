<?php

namespace OPTravelStorefrontCMS\Documents;

use OPTravelStorefrontCMS\Domain\RouteKey;
use OPTravelStorefrontCMS\Domain\SectionSanitizer;

final class DocumentRepository
{
    public const ROUTE_KEY_META = '_op_travel_storefront_route_key';
    public const ROUTE_TYPE_META = '_op_travel_storefront_route_type';
    public const ROUTE_TARGET_META = '_op_travel_storefront_route_target_id';
    public const SECTIONS_META = '_op_travel_storefront_sections';

    public static function save($postId, $routeType, $routeTargetId, $sections)
    {
        $routeKey = RouteKey::fromParts($routeType, $routeTargetId);

        update_post_meta($postId, self::ROUTE_KEY_META, $routeKey);
        update_post_meta($postId, self::ROUTE_TYPE_META, $routeType);
        update_post_meta($postId, self::ROUTE_TARGET_META, (int) $routeTargetId);
        update_post_meta($postId, self::SECTIONS_META, SectionSanitizer::sanitizeMany($sections));

        return $routeKey;
    }

    public static function getRouteKey($postId)
    {
        return (string) get_post_meta($postId, self::ROUTE_KEY_META, true);
    }

    public static function getRouteType($postId)
    {
        $stored = (string) get_post_meta($postId, self::ROUTE_TYPE_META, true);

        if ($stored !== '') {
            return $stored;
        }

        $parsed = RouteKey::parse(self::getRouteKey($postId));

        return $parsed['route_type'] ?? 'home';
    }

    public static function getRouteTargetId($postId)
    {
        $stored = (int) get_post_meta($postId, self::ROUTE_TARGET_META, true);

        if ($stored > 0) {
            return $stored;
        }

        $parsed = RouteKey::parse(self::getRouteKey($postId));

        return (int) ($parsed['target_id'] ?? 0);
    }

    public static function getSections($postId)
    {
        $sections = get_post_meta($postId, self::SECTIONS_META, true);

        return SectionSanitizer::sanitizeMany(is_array($sections) ? $sections : []);
    }

    public static function getPublishedDocumentIdByRouteKey($routeKey, $excludePostId = 0)
    {
        $posts = get_posts([
            'post_type' => 'storefront_document',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'post__not_in' => $excludePostId > 0 ? [(int) $excludePostId] : [],
            'meta_key' => self::ROUTE_KEY_META,
            'meta_value' => $routeKey,
        ]);

        return isset($posts[0]) ? (int) $posts[0] : 0;
    }

    public static function getPublishedDocumentByRouteKey($routeKey)
    {
        $postId = self::getPublishedDocumentIdByRouteKey($routeKey);

        if ($postId <= 0) {
            return null;
        }

        $post = get_post($postId);

        return $post ?: null;
    }

    public static function hasPublishedRouteConflict($routeKey, $excludePostId = 0)
    {
        if (! RouteKey::isSupported($routeKey)) {
            return false;
        }

        return self::getPublishedDocumentIdByRouteKey($routeKey, $excludePostId) > 0;
    }

    public static function hasPublishedRouteConflictInRecords($routeKey, $currentPostId, $records)
    {
        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $recordId = (int) ($record['ID'] ?? 0);
            $recordStatus = (string) ($record['post_status'] ?? '');
            $recordRouteKey = (string) ($record['route_key'] ?? '');

            if ($recordId === (int) $currentPostId) {
                continue;
            }

            if ($recordStatus !== 'publish') {
                continue;
            }

            if ($recordRouteKey === $routeKey) {
                return true;
            }
        }

        return false;
    }
}
