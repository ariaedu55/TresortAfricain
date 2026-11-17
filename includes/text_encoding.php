<?php
/**
 * Utilitaires d'encodage UTF-8
 */

if (!function_exists('init_app_encoding')) {
    function init_app_encoding() {
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }
        if (function_exists('mb_http_output')) {
            mb_http_output('UTF-8');
        }
        ini_set('default_charset', 'UTF-8');
    }
}

if (!function_exists('utf8_quality_score')) {
    function utf8_quality_score($text) {
        if (!is_string($text) || $text === '') {
            return 0;
        }

        $score = 0;
        if (mb_check_encoding($text, 'UTF-8')) {
            $score += 10;
        }
        if (strpos($text, "\xEF\xBF\xBD") === false) {
            $score += 5;
        }
        if (strpos($text, 'Ã') === false && strpos($text, 'â€') === false) {
            $score += 5;
        }
        if (preg_match('/[àâäéèêëïîôùûüçœæÀÂÄÉÈÊËÏÎÔÙÛÜÇŒÆ]/u', $text)) {
            $score += 3;
        }
        if (preg_match('/[ÃÂâ€�]/u', $text)) {
            $score -= 15;
        }

        return $score;
    }
}

if (!function_exists('utf8_needs_fix')) {
    function utf8_needs_fix($text) {
        if (!is_string($text) || $text === '') {
            return false;
        }

        if (!mb_check_encoding($text, 'UTF-8')) {
            return true;
        }
        if (strpos($text, "\xEF\xBF\xBD") !== false) {
            return true;
        }
        if (preg_match('/[ÃÂâ€�]/u', $text)) {
            return true;
        }

        if (preg_match('/[\x80-\xFF]/', $text) && !preg_match('//u', $text)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('normalize_utf8')) {
    function normalize_utf8($text) {
        if (!is_string($text) || $text === '') {
            return $text;
        }

        if (!utf8_needs_fix($text)) {
            return $text;
        }

        $candidates = [$text];
        $from_latin1 = @mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        if (is_string($from_latin1) && $from_latin1 !== '') {
            $candidates[] = $from_latin1;
        }

        $from_cp1252 = @mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        if (is_string($from_cp1252) && $from_cp1252 !== '') {
            $candidates[] = $from_cp1252;
        }

        $latin1_bytes = @mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        if (is_string($latin1_bytes) && $latin1_bytes !== '' && $latin1_bytes !== $text) {
            $from_double = @mb_convert_encoding($latin1_bytes, 'UTF-8', 'ISO-8859-1');
            if (is_string($from_double) && $from_double !== '') {
                $candidates[] = $from_double;
            }
        }

        $best = $text;
        $best_score = utf8_quality_score($text);
        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '' || !mb_check_encoding($candidate, 'UTF-8')) {
                continue;
            }
            $score = utf8_quality_score($candidate);
            if ($score > $best_score) {
                $best = $candidate;
                $best_score = $score;
            }
        }

        return $best;
    }
}

if (!function_exists('normalize_utf8_array')) {
    function normalize_utf8_array(array $data) {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = normalize_utf8($value);
            } elseif (is_array($value)) {
                $data[$key] = normalize_utf8_array($value);
            }
        }

        return $data;
    }
}

if (!function_exists('normalize_utf8_rows')) {
    function normalize_utf8_rows($rows) {
        if (!is_array($rows) || $rows === []) {
            return $rows;
        }

        if (isset($rows[0]) && is_array($rows[0])) {
            return array_map('normalize_utf8_array', $rows);
        }

        return normalize_utf8_array($rows);
    }
}

if (!function_exists('e')) {
    function e($text) {
        return htmlspecialchars(normalize_utf8((string) $text), ENT_QUOTES, 'UTF-8');
    }
}
