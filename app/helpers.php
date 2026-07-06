<?php

if (!function_exists('safe_asset')) {
    function safe_asset($path)
    {
        return app()->environment('production') 
            ? secure_asset($path) 
            : asset($path);
    }
}