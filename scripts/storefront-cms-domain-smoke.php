<?php

declare(strict_types=1);

require_once __DIR__ . '/../wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Domain/RouteKey.php';
require_once __DIR__ . '/../wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Domain/SectionSanitizer.php';
require_once __DIR__ . '/../wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Documents/DocumentRepository.php';
require_once __DIR__ . '/../wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Sections/SectionRegistry.php';
require_once __DIR__ . '/../wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Front/PreviewResolver.php';
require_once __DIR__ . '/../wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Front/RouteRenderer.php';

use OPTravelStorefrontCMS\Domain\RouteKey;
use OPTravelStorefrontCMS\Domain\SectionSanitizer;
use OPTravelStorefrontCMS\Documents\DocumentRepository;
use OPTravelStorefrontCMS\Front\PreviewResolver;
use OPTravelStorefrontCMS\Front\RouteRenderer;
use OPTravelStorefrontCMS\Sections\SectionRegistry;

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
    }
}

function assertTrueValue($condition, $message)
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

assertSameValue('home', RouteKey::fromParts('home'), 'Home route key should normalize.');
assertSameValue('page:15', RouteKey::fromParts('page', 15), 'Page route key should include page id.');

$threw = false;

try {
    RouteKey::fromParts('page', 0);
} catch (InvalidArgumentException $exception) {
    $threw = true;
}

assertTrueValue($threw, 'Invalid page IDs should throw.');

$sections = SectionSanitizer::sanitizeMany([
    [
        'type' => 'hero',
        'label' => 'Hero',
        'enabled' => '1',
        'settings' => ['style' => 'default'],
        'content' => ['title' => 'Hello'],
        'bindings' => ['mode' => 'manual'],
    ],
]);

assertSameValue(1, count($sections), 'One valid section should remain after sanitizing.');
assertSameValue('hero', $sections[0]['type'], 'Section type should be preserved.');
assertTrueValue(isset($sections[0]['id']) && $sections[0]['id'] !== '', 'Section id should be generated.');
assertSameValue(true, $sections[0]['enabled'], 'Enabled value should normalize to boolean true.');
assertSameValue('Hello', $sections[0]['content']['title'], 'Content should be preserved.');

$registry = SectionRegistry::all();
assertTrueValue(isset($registry['hero']), 'Registry should expose the hero section.');
assertTrueValue(isset($registry['tour_booking_panel']), 'Registry should expose the booking panel section.');

$hasConflict = DocumentRepository::hasPublishedRouteConflictInRecords('home', 15, [
    ['ID' => 4, 'post_status' => 'draft', 'route_key' => 'home'],
    ['ID' => 9, 'post_status' => 'publish', 'route_key' => 'shop_archive'],
    ['ID' => 12, 'post_status' => 'publish', 'route_key' => 'home'],
]);

assertSameValue(true, $hasConflict, 'Published route conflict should be detected.');

$hasConflict = DocumentRepository::hasPublishedRouteConflictInRecords('home', 12, [
    ['ID' => 12, 'post_status' => 'publish', 'route_key' => 'home'],
]);

assertSameValue(false, $hasConflict, 'Current record should be ignored when checking conflicts.');
assertSameValue(22, PreviewResolver::extractPreviewId(['op_travel_storefront_preview' => '22']), 'Preview parser should extract a positive integer.');
assertSameValue(0, PreviewResolver::extractPreviewId([]), 'Preview parser should return 0 when absent.');
assertSameValue(true, RouteRenderer::supportsRoute('shop_archive'), 'Renderer should accept supported route keys.');
assertSameValue(false, RouteRenderer::supportsRoute('bogus_route'), 'Renderer should reject unsupported route keys.');

echo "storefront cms domain smoke: ok\n";
