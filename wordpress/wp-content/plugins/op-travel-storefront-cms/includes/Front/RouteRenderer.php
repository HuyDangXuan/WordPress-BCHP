<?php

namespace OPTravelStorefrontCMS\Front;

use OPTravelStorefrontCMS\Documents\DocumentRepository;
use OPTravelStorefrontCMS\Domain\RouteKey;
use OPTravelStorefrontCMS\Sections\SectionRegistry;

final class RouteRenderer
{
    public static function supportsRoute($routeKey)
    {
        return RouteKey::isSupported($routeKey);
    }

    public static function render($routeKey, $context = [])
    {
        if (! self::supportsRoute($routeKey)) {
            return false;
        }

        if (! function_exists('op_travel_theme_render_storefront_document')) {
            return false;
        }

        $document = PreviewResolver::resolvePreviewDocument($routeKey);
        $isPreview = $document !== null;

        if (! $document) {
            $document = DocumentRepository::getPublishedDocumentByRouteKey($routeKey);
        }

        if (! $document) {
            return false;
        }

        $payload = [
            'document_id' => (int) $document->ID,
            'route_key' => $routeKey,
            'route' => RouteKey::parse($routeKey),
            'is_preview' => $isPreview,
            'sections' => self::filterRenderableSections(DocumentRepository::getSections($document->ID), $routeKey),
        ];

        return (bool) op_travel_theme_render_storefront_document($payload, is_array($context) ? $context : []);
    }

    private static function filterRenderableSections($sections, $routeKey)
    {
        $sections = is_array($sections) ? $sections : [];
        $routeType = RouteKey::parse($routeKey);
        $routeType = (string) ($routeType['route_type'] ?? '');
        $registry = SectionRegistry::all();
        $renderable = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $type = (string) ($section['type'] ?? '');

            if (! SectionRegistry::has($type)) {
                continue;
            }

            $supportedRoutes = $registry[$type]['routes'] ?? [];

            if (! in_array($routeType, $supportedRoutes, true)) {
                continue;
            }

            $renderable[] = $section;
        }

        return $renderable;
    }
}
