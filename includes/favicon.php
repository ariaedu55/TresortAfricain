<?php
if (!function_exists('brand_logo')) {
    require_once __DIR__ . '/brand.php';
}
$brand_favicon = brand_logo();
$brand_favicon_type = (substr($brand_favicon, -4) === '.png') ? 'image/png' : 'image/jpeg';
?>
<link rel="icon" type="<?php echo $brand_favicon_type; ?>" href="<?php echo htmlspecialchars($brand_favicon); ?>">
<link rel="shortcut icon" type="<?php echo $brand_favicon_type; ?>" href="<?php echo htmlspecialchars($brand_favicon); ?>">
<link rel="apple-touch-icon" href="<?php echo htmlspecialchars($brand_favicon); ?>">
