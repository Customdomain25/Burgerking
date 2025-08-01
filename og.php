<?php

// Headers

header("Access-Control-Allow-Origin: *");

// Set version

$og_version = '1.21';

// Set domain

$domain = 'hostedfiles.net';

// CDN var

if(isset($_GET['cdn'])) {

    $domain = 'cdn.' . $domain;

}

unset($_GET['cdn']);

// Input var

$u = ltrim($_GET['u'], '/');

if (empty($u)) {

    throw new Exception("Missing required query parameter 'u'.");

}

unset($_GET['u']);

// Get ip

if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {

    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];

} elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {

    $ip = $_SERVER['HTTP_CLIENT_IP'];

} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {

    $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];

} elseif(isset($_SERVER['REMOTE_ADDR'])) {

    $ip = $_SERVER['REMOTE_ADDR'];

}

if(is_null($ip)) {

    throw new Exception('Could not determine IP');

}

// Get user agent

$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

if(is_null($user_agent)) {

    throw new Exception('Missing server var HTTP_USER_AGENT');

}

// Get referrer

$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

// Prepare header array

$headers = [
    'X-Forwarded-For: ' . $ip,
    'X-OGAds-Mirrored: ' . $og_version,
];

// Add script filename to headers

if(isset($_SERVER['SCRIPT_FILENAME'])) {

    $headers[] = 'X-OGAds-Script-Filename: ' . basename($_SERVER['SCRIPT_FILENAME']);

};

// Set URL

$url = "https://$domain/$u?" . http_build_query($_GET);

// Start CURL

$ch = curl_init();

// Set CURL options

curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_USERAGENT      => $user_agent,
    CURLOPT_REFERER        => $referrer,
    CURLOPT_HTTPHEADER     => $headers,
]);

// Execute request

$content = curl_exec($ch);

// Get the host and content type of the URL we were redirected to

$url_new = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Check for error

if ($content === false) {

    // Throw exception if error found

    throw new Exception(curl_error($ch));

}

// Close CURL

curl_close($ch);

// Check URL host...

if (parse_url($url_new, PHP_URL_HOST) === $domain) {

    // If internal

    if (!is_null($content_type)) {

        // Set Content-Type header

        header("Content-Type: $content_type");

    }

    // Set X-Robots-Tag header (so search engines don't index this page)

    header("X-Robots-Tag: none");

    // Output contents

    echo $content;

} else {

    // If external; redirect

    header("Location: $url_new");

}
