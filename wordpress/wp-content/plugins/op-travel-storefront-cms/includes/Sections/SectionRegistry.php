<?php

namespace OPTravelStorefrontCMS\Sections;

final class SectionRegistry
{
    public static function all()
    {
        return [
            'hero' => [
                'label' => self::translate('Hero'),
                'routes' => ['home', 'shop_archive', 'product_single_default', 'page'],
            ],
            'rich_text' => [
                'label' => self::translate('Rich Text'),
                'routes' => ['home', 'shop_archive', 'product_single_default', 'page'],
            ],
            'cta_band' => [
                'label' => self::translate('CTA Band'),
                'routes' => ['home', 'shop_archive', 'product_single_default', 'page'],
            ],
            'media_text' => [
                'label' => self::translate('Media + Text'),
                'routes' => ['home', 'page'],
            ],
            'stats' => [
                'label' => self::translate('Stats'),
                'routes' => ['home', 'page'],
            ],
            'faq' => [
                'label' => self::translate('FAQ'),
                'routes' => ['home', 'page'],
            ],
            'featured_tours' => [
                'label' => self::translate('Featured Tours'),
                'routes' => ['home', 'shop_archive', 'page'],
            ],
            'taxonomy_grid' => [
                'label' => self::translate('Taxonomy Grid'),
                'routes' => ['home', 'shop_archive', 'page'],
            ],
            'testimonial_list' => [
                'label' => self::translate('Testimonials'),
                'routes' => ['home', 'page'],
            ],
            'promotion_list' => [
                'label' => self::translate('Promotions'),
                'routes' => ['home', 'page'],
            ],
            'tour_highlights' => [
                'label' => self::translate('Tour Highlights'),
                'routes' => ['product_single_default'],
            ],
            'tour_itinerary' => [
                'label' => self::translate('Tour Itinerary'),
                'routes' => ['product_single_default'],
            ],
            'tour_includes_excludes' => [
                'label' => self::translate('Tour Includes / Excludes'),
                'routes' => ['product_single_default'],
            ],
            'tour_booking_panel' => [
                'label' => self::translate('Tour Booking Panel'),
                'routes' => ['product_single_default'],
            ],
        ];
    }

    public static function bindingModes()
    {
        return [
            'manual' => self::translate('Manual content'),
            'current_page' => self::translate('Current page context'),
            'current_product' => self::translate('Current product context'),
            'query' => self::translate('Query content'),
            'taxonomy' => self::translate('Taxonomy content'),
            'post_type' => self::translate('Post type content'),
        ];
    }

    public static function taxonomyOptions()
    {
        return [
            'destination' => self::translate('Destination'),
            'tour_style' => self::translate('Tour Style'),
        ];
    }

    public static function postTypeOptions()
    {
        return [
            'product' => self::translate('Tours (Products)'),
            'promotion' => self::translate('Promotions'),
            'testimonial' => self::translate('Testimonials'),
        ];
    }

    public static function has($type)
    {
        return isset(self::all()[(string) $type]);
    }

    public static function defaultSection()
    {
        return [
            'id' => 'section-' . uniqid(),
            'type' => 'hero',
            'label' => '',
            'enabled' => true,
            'settings' => [
                'item_count' => 4,
            ],
            'content' => [
                'eyebrow' => '',
                'title' => '',
                'body' => '',
                'button_label' => '',
                'button_url' => '',
                'secondary_label' => '',
                'secondary_url' => '',
            ],
            'bindings' => [
                'mode' => 'manual',
                'taxonomy' => 'destination',
                'post_type' => 'product',
            ],
        ];
    }

    private static function translate($text)
    {
        if (function_exists('__')) {
            return __($text, 'op-travel-storefront-cms');
        }

        return $text;
    }
}
