<?php

declare(strict_types=1);

$themeRoot = getenv('OP_TRAVEL_THEME_ROOT');

if (! is_string($themeRoot) || $themeRoot === '') {
    $themeRoot = __DIR__ . '/../wordpress/wp-content/themes/op-travel-shop';
}

function assertFileContains(string $path, string $needle, string $message): void
{
    $contents = @file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException('Unable to read file: ' . $path);
    }

    if (strpos($contents, $needle) === false) {
        throw new RuntimeException($message . ' Missing marker: ' . $needle);
    }
}

assertFileContains($themeRoot . '/header.php', 'op-site-header__topline', 'Header should expose the transplanted public shell.');
assertFileContains($themeRoot . '/footer.php', 'op-site-footer__column', 'Footer should expose the brighter multi-column layout.');
assertFileContains($themeRoot . '/front-page.php', 'op-hero-search', 'Homepage should expose the transplanted hero search band.');
assertFileContains($themeRoot . '/front-page.php', 'op-home-spotlight', 'Homepage should expose the destination spotlight section.');
assertFileContains($themeRoot . '/page.php', 'op-page-intro', 'Default pages should expose the shared page intro shell.');
assertFileContains($themeRoot . '/assets/css/theme.css', '--op-bg-soft', 'Theme CSS should expose the transplanted bright palette.');
assertFileContains($themeRoot . '/assets/css/theme.css', '.op-content-card', 'Theme CSS should expose the shared content-card surface.');
assertFileContains($themeRoot . '/assets/css/theme.css', '.op-home-spotlight', 'Theme CSS should expose homepage spotlight styling.');
assertFileContains($themeRoot . '/assets/css/theme.css', '.op-hero__content h1,', 'Hero content should explicitly override text contrast for light cards.');
assertFileContains($themeRoot . '/assets/css/theme.css', '.op-hero__badge h2,', 'Hero badge should explicitly override text contrast for light cards.');
assertFileContains($themeRoot . '/assets/css/theme.css', '.op-hero__support-item strong', 'Hero support items should explicitly override text contrast for light cards.');

echo "op travel shared ui smoke: ok\n";
