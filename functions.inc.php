<?php

/* Generic routines */

function sanitize($string, $stripall = true, $filter = true) {
    // Trim any leading or trailing whitespace
    $clean = trim("$string");

    // Convert any special characters to their normal parts
    $clean = html_entity_decode($clean, ENT_COMPAT, "UTF-8");

    // By default strip all html
    $allowedtags = ($stripall) ? '' : '<a><b><i><img><u><br>';

    // Strip out the shit we don't allow
    $clean = strip_tags($clean, $allowedtags);
    // If we decide to strip double quotes instead of encoding them uncomment the 
    //	next line
    // $clean=($stripall)?str_replace('"','',$clean):$clean;
    // What is this gonna do ?
    if ($filter) $clean = filter_var($clean, FILTER_SANITIZE_SPECIAL_CHARS);

    // There shoudln't be anything left to escape but wtf do it anyway
    $clean = addslashes($clean);

    return $clean;
}

function ArraySearchRecursive($Needle, $Haystack, $NeedleKey = "", $Strict = false, $Path = array()) {
    if (is_object($Haystack)) {
        $Haystack = (array) $Haystack;
    } elseif (!is_array($Haystack)) {
        return false;
    }
    foreach ($Haystack as $Key => $Val) {
        if ((is_array($Val) || is_object($Val)) && $SubPath = ArraySearchRecursive($Needle, $Val, $NeedleKey, $Strict, $Path)) {
            $Path = array_merge($Path, array($Key), $SubPath);
            return $Path;
        } elseif ((!$Strict && $Val == $Needle && $Key == (strlen($NeedleKey) > 0 ? $NeedleKey : $Key)) || ($Strict && $Val === $Needle && $Key == (strlen($NeedleKey) > 0 ? $NeedleKey : $Key))) {
            $Path[] = $Key;
            return $Path;
        }
    }
    return false;
}

/**
 * temporary logging function
 *
 * @param  mixed $content
 * @return void
 */
function save_log($content) {
    $logfile = '/var/www/html/dcim2/assets/reports/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    if (is_array($content) || is_object($content)) {
        $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    $logentry = "[$timestamp] $content" . PHP_EOL;
    file_put_contents($logfile, $logentry, FILE_APPEND | LOCK_EX);
}
