<?php
/**
 * Feuilles de style identité Trésor Africain
 */
if (!function_exists('asset_version_query')) {
    require_once __DIR__ . '/asset_version.php';
}
?>
<link rel="stylesheet" href="/css/brand-theme.css<?php echo asset_version_query(); ?>">
