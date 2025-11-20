<?php

if (!function_exists('highlightSearchTerm')) {
    function highlightSearchTerm($text, $term) {
        if (!$term) {
            return $text;
        }
        return preg_replace('/(' . preg_quote($term, '/') . ')/i', '<span class="bg-yellow-200">$1</span>', $text);
    }
}