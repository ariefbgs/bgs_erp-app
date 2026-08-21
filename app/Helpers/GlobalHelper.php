<?php

if (!function_exists('romanMonth')) {
    function romanMonth($month = null)
    {
        $month = $month ?? date('n');
        $romans = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        return $romans[$month - 1];
    }
}