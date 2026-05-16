<?php

namespace OPTravelStorefrontCMS\Admin;

use InvalidArgumentException;
use OPTravelStorefrontCMS\Documents\DocumentRepository;
use OPTravelStorefrontCMS\Domain\SectionSanitizer;

final class DocumentSave
{
    private const NOTICE_TRANSIENT_PREFIX = 'op_travel_storefront_cms_notice_';

    public static function boot()
    {
        add_action('save_post_storefront_document', [__CLASS__, 'save']);
        add_action('admin_notices', [__CLASS__, 'renderNotices']);
    }

    public static function save($postId)
    {
        if (! self::shouldHandleSave($postId)) {
            return;
        }

        $routeType = isset($_POST['op_travel_storefront_route_type'])
            ? sanitize_text_field(wp_unslash($_POST['op_travel_storefront_route_type']))
            : 'home';
        $routeTargetId = isset($_POST['op_travel_storefront_route_target_id'])
            ? absint(wp_unslash($_POST['op_travel_storefront_route_target_id']))
            : 0;
        $sections = isset($_POST['op_travel_storefront_sections']) && is_array($_POST['op_travel_storefront_sections'])
            ? wp_unslash($_POST['op_travel_storefront_sections'])
            : [];

        try {
            $routeKey = DocumentRepository::save(
                $postId,
                $routeType,
                $routeTargetId,
                SectionSanitizer::sanitizeMany($sections)
            );
        } catch (InvalidArgumentException $exception) {
            self::storeNotice($exception->getMessage());
            return;
        }

        if (get_post_status($postId) !== 'publish') {
            return;
        }

        if (! DocumentRepository::hasPublishedRouteConflict($routeKey, $postId)) {
            return;
        }

        remove_action('save_post_storefront_document', [__CLASS__, 'save']);
        wp_update_post([
            'ID' => $postId,
            'post_status' => 'draft',
        ]);
        add_action('save_post_storefront_document', [__CLASS__, 'save']);

        self::storeNotice(__('Another published storefront document already owns this route. This document was moved back to draft.', 'op-travel-storefront-cms'));
    }

    public static function renderNotices()
    {
        if (! current_user_can('edit_posts')) {
            return;
        }

        $transientKey = self::NOTICE_TRANSIENT_PREFIX . get_current_user_id();
        $message = get_transient($transientKey);

        if (! is_string($message) || $message === '') {
            return;
        }

        delete_transient($transientKey);

        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }

    private static function shouldHandleSave($postId)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        if (! isset($_POST['op_travel_storefront_document_nonce'])) {
            return false;
        }

        if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['op_travel_storefront_document_nonce'])), 'op_travel_storefront_document_save')) {
            return false;
        }

        return current_user_can('edit_post', $postId);
    }

    private static function storeNotice($message)
    {
        if (! current_user_can('edit_posts')) {
            return;
        }

        set_transient(self::NOTICE_TRANSIENT_PREFIX . get_current_user_id(), (string) $message, MINUTE_IN_SECONDS);
    }
}
