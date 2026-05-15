<?php

namespace OPTravelStorefrontCMS\Front;

use OPTravelStorefrontCMS\Documents\DocumentRepository;

final class PreviewResolver
{
    public static function extractPreviewId($query = null)
    {
        if (! is_array($query)) {
            $query = $_GET;
        }

        if (! isset($query['op_travel_storefront_preview'])) {
            return 0;
        }

        return max(0, (int) $query['op_travel_storefront_preview']);
    }

    public static function resolvePreviewDocument($routeKey)
    {
        $previewId = self::extractPreviewId();

        if ($previewId <= 0) {
            return null;
        }

        if (! isset($_GET['op_travel_storefront_preview_nonce'])) {
            return null;
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['op_travel_storefront_preview_nonce']));

        if (! wp_verify_nonce($nonce, 'op_travel_storefront_preview_' . $previewId)) {
            return null;
        }

        if (! current_user_can('edit_post', $previewId)) {
            return null;
        }

        $post = get_post($previewId);

        if (! $post || $post->post_type !== 'storefront_document') {
            return null;
        }

        if (DocumentRepository::getRouteKey($previewId) !== $routeKey) {
            return null;
        }

        return $post;
    }
}
