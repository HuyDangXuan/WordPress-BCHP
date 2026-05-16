<?php

namespace OPTravelStorefrontCMS\Admin;

use OPTravelStorefrontCMS\Documents\DocumentRepository;
use OPTravelStorefrontCMS\Sections\SectionRegistry;

final class DocumentMetaBoxes
{
    public static function boot()
    {
        add_action('add_meta_boxes_storefront_document', [__CLASS__, 'register']);
        add_filter('manage_storefront_document_posts_columns', [__CLASS__, 'filterColumns']);
        add_action('manage_storefront_document_posts_custom_column', [__CLASS__, 'renderColumn'], 10, 2);
        add_filter('preview_post_link', [__CLASS__, 'filterPreviewLink'], 10, 2);
    }

    public static function register()
    {
        add_meta_box(
            'op-travel-storefront-route',
            __('Storefront Route Binding', 'op-travel-storefront-cms'),
            [__CLASS__, 'renderRouteBox'],
            'storefront_document',
            'normal',
            'high'
        );

        add_meta_box(
            'op-travel-storefront-sections',
            __('Storefront Sections', 'op-travel-storefront-cms'),
            [__CLASS__, 'renderSectionsBox'],
            'storefront_document',
            'normal',
            'default'
        );
    }

    public static function filterColumns($columns)
    {
        $columns['op_travel_route_key'] = __('Route Key', 'op-travel-storefront-cms');

        return $columns;
    }

    public static function renderColumn($column, $postId)
    {
        if ($column !== 'op_travel_route_key') {
            return;
        }

        $routeKey = DocumentRepository::getRouteKey($postId);
        echo esc_html($routeKey !== '' ? $routeKey : 'draft-only');
    }

    public static function renderRouteBox($post)
    {
        wp_nonce_field('op_travel_storefront_document_save', 'op_travel_storefront_document_nonce');

        $routeType = DocumentRepository::getRouteType($post->ID);
        $routeTargetId = DocumentRepository::getRouteTargetId($post->ID);
        $pages = get_pages([
            'sort_column' => 'post_title',
        ]);
        ?>
        <div class="op-storefront-field-grid">
            <p>
                <label for="op-travel-storefront-route-type"><strong><?php esc_html_e('Route Type', 'op-travel-storefront-cms'); ?></strong></label><br>
                <select id="op-travel-storefront-route-type" name="op_travel_storefront_route_type">
                    <?php foreach (\OPTravelStorefrontCMS\Domain\RouteKey::supportedTypes() as $type) : ?>
                        <option value="<?php echo esc_attr($type); ?>" <?php selected($routeType, $type); ?>>
                            <?php echo esc_html($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p class="op-storefront-page-target" <?php echo $routeType === 'page' ? '' : 'style="display:none;"'; ?>>
                <label for="op-travel-storefront-route-target"><strong><?php esc_html_e('WordPress Page', 'op-travel-storefront-cms'); ?></strong></label><br>
                <select id="op-travel-storefront-route-target" name="op_travel_storefront_route_target_id">
                    <option value="0"><?php esc_html_e('Select a page', 'op-travel-storefront-cms'); ?></option>
                    <?php foreach ($pages as $page) : ?>
                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($routeTargetId, (int) $page->ID); ?>>
                            <?php echo esc_html($page->post_title . ' (#' . $page->ID . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
        </div>
        <p class="description">
            <?php esc_html_e('One published storefront document may own a route at a time. Draft duplicates are allowed until publish.', 'op-travel-storefront-cms'); ?>
        </p>
        <?php
    }

    public static function renderSectionsBox($post)
    {
        $sections = DocumentRepository::getSections($post->ID);
        $sectionTypes = SectionRegistry::all();
        ?>
        <div class="op-storefront-sections" data-storefront-sections>
            <div class="op-storefront-sections__toolbar">
                <button type="button" class="button button-primary" data-add-section><?php esc_html_e('Add Section', 'op-travel-storefront-cms'); ?></button>
            </div>

            <div class="op-storefront-sections__list" data-sections-list>
                <?php foreach ($sections as $index => $section) : ?>
                    <?php self::renderSectionCard($section, (string) $index, $sectionTypes); ?>
                <?php endforeach; ?>
            </div>

            <template data-section-template>
                <?php
                $templateSection = SectionRegistry::defaultSection();
                $templateSection['id'] = 'section-__index__';
                self::renderSectionCard($templateSection, '__index__', $sectionTypes);
                ?>
            </template>
        </div>
        <?php
    }

    public static function filterPreviewLink($previewLink, $post)
    {
        if (! $post || $post->post_type !== 'storefront_document') {
            return $previewLink;
        }

        $routeKey = DocumentRepository::getRouteKey($post->ID);

        if ($routeKey === '') {
            return $previewLink;
        }

        $targetUrl = self::resolvePreviewTargetUrl($routeKey);

        if ($targetUrl === '') {
            return $previewLink;
        }

        return add_query_arg([
            'op_travel_storefront_preview' => (int) $post->ID,
            'op_travel_storefront_preview_nonce' => wp_create_nonce('op_travel_storefront_preview_' . $post->ID),
        ], $targetUrl);
    }

    private static function renderSectionCard($section, $index, $sectionTypes)
    {
        $settings = is_array($section['settings'] ?? null) ? $section['settings'] : [];
        $content = is_array($section['content'] ?? null) ? $section['content'] : [];
        $bindings = is_array($section['bindings'] ?? null) ? $section['bindings'] : [];
        ?>
        <section class="op-storefront-section-card" data-section-card>
            <div class="op-storefront-section-card__header">
                <strong><?php echo esc_html($section['label'] ?: __('New section', 'op-travel-storefront-cms')); ?></strong>
                <div class="op-storefront-section-card__actions">
                    <button type="button" class="button-link" data-move-up><?php esc_html_e('Up', 'op-travel-storefront-cms'); ?></button>
                    <button type="button" class="button-link" data-move-down><?php esc_html_e('Down', 'op-travel-storefront-cms'); ?></button>
                    <button type="button" class="button-link-delete" data-remove-section><?php esc_html_e('Remove', 'op-travel-storefront-cms'); ?></button>
                </div>
            </div>

            <input type="hidden" name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][id]" value="<?php echo esc_attr($section['id'] ?? ''); ?>">

            <div class="op-storefront-field-grid">
                <p>
                    <label><strong><?php esc_html_e('Type', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <select name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][type]">
                        <?php foreach ($sectionTypes as $type => $definition) : ?>
                            <option value="<?php echo esc_attr($type); ?>" <?php selected($section['type'] ?? '', $type); ?>>
                                <?php echo esc_html($definition['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p>
                    <label><strong><?php esc_html_e('Label', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <input type="text" class="widefat" name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][label]" value="<?php echo esc_attr($section['label'] ?? ''); ?>">
                </p>

                <p>
                    <label><strong><?php esc_html_e('Enabled', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <label>
                        <input type="checkbox" name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][enabled]" value="1" <?php checked(! empty($section['enabled'])); ?>>
                        <?php esc_html_e('Render this section', 'op-travel-storefront-cms'); ?>
                    </label>
                </p>

                <p>
                    <label><strong><?php esc_html_e('Binding Mode', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <select name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][bindings][mode]">
                        <?php foreach (SectionRegistry::bindingModes() as $mode => $label) : ?>
                            <option value="<?php echo esc_attr($mode); ?>" <?php selected($bindings['mode'] ?? 'manual', $mode); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p>
                    <label><strong><?php esc_html_e('Item Count', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <input type="number" min="1" max="12" name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][settings][item_count]" value="<?php echo esc_attr((string) ($settings['item_count'] ?? 4)); ?>">
                </p>

                <p>
                    <label><strong><?php esc_html_e('Taxonomy', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <select name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][bindings][taxonomy]">
                        <?php foreach (SectionRegistry::taxonomyOptions() as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($bindings['taxonomy'] ?? 'destination', $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p>
                    <label><strong><?php esc_html_e('Post Source', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <select name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][bindings][post_type]">
                        <?php foreach (SectionRegistry::postTypeOptions() as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($bindings['post_type'] ?? 'product', $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
            </div>

            <div class="op-storefront-field-grid">
                <p>
                    <label><strong><?php esc_html_e('Eyebrow', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <input type="text" class="widefat" name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][content][eyebrow]" value="<?php echo esc_attr($content['eyebrow'] ?? ''); ?>">
                </p>

                <p>
                    <label><strong><?php esc_html_e('Title', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <input type="text" class="widefat" name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][content][title]" value="<?php echo esc_attr($content['title'] ?? ''); ?>">
                </p>
            </div>

            <p>
                <label><strong><?php esc_html_e('Body', 'op-travel-storefront-cms'); ?></strong></label><br>
                <textarea class="widefat" rows="4" name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][content][body]"><?php echo esc_textarea($content['body'] ?? ''); ?></textarea>
            </p>

            <div class="op-storefront-field-grid">
                <p>
                    <label><strong><?php esc_html_e('Primary Button Label', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <input type="text" class="widefat" name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][content][button_label]" value="<?php echo esc_attr($content['button_label'] ?? ''); ?>">
                </p>

                <p>
                    <label><strong><?php esc_html_e('Primary Button URL', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <input type="url" class="widefat" name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][content][button_url]" value="<?php echo esc_attr($content['button_url'] ?? ''); ?>">
                </p>

                <p>
                    <label><strong><?php esc_html_e('Secondary Button Label', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <input type="text" class="widefat" name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][content][secondary_label]" value="<?php echo esc_attr($content['secondary_label'] ?? ''); ?>">
                </p>

                <p>
                    <label><strong><?php esc_html_e('Secondary Button URL', 'op-travel-storefront-cms'); ?></strong></label><br>
                    <input type="url" class="widefat" name="op_travel_storefront_sections[<?php echo esc_attr($index); ?>][content][secondary_url]" value="<?php echo esc_attr($content['secondary_url'] ?? ''); ?>">
                </p>
            </div>
        </section>
        <?php
    }

    private static function resolvePreviewTargetUrl($routeKey)
    {
        $parsed = \OPTravelStorefrontCMS\Domain\RouteKey::parse($routeKey);

        if (! is_array($parsed)) {
            return '';
        }

        if ($parsed['route_type'] === 'home') {
            return home_url('/');
        }

        if ($parsed['route_type'] === 'shop_archive') {
            return function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
        }

        if ($parsed['route_type'] === 'product_single_default') {
            $products = get_posts([
                'post_type' => 'product',
                'post_status' => 'publish',
                'numberposts' => 1,
                'fields' => 'ids',
            ]);

            return isset($products[0]) ? get_permalink((int) $products[0]) : home_url('/');
        }

        if ($parsed['route_type'] === 'page' && (int) $parsed['target_id'] > 0) {
            return get_permalink((int) $parsed['target_id']);
        }

        return '';
    }
}
