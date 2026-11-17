<?php
/**
 * Identité visuelle du site
 */

if (!function_exists('brand_name')) {
    function brand_name() {
        return 'Trésor Africa';
    }
}

if (!function_exists('brand_logo')) {
    function brand_logo() {
        return '/image/logo-brand.png';
    }
}
