<?php
ob_start();
session_start();

// Configuración inicial con rutas relativas
$baseDir = dirname(__FILE__);
$dataDir = $baseDir . '/analytics';
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0777, true);
}

// Definir archivos de datos y caché
$csvFile = $dataDir . '/visits.csv';
$eventsFile = $dataDir . '/events.json';
$cacheFile = $dataDir . '/cache.json';
date_default_timezone_set('Europe/Madrid');

// Definir todas las funciones primero
function getVisitorCountry($ip) {
    try {
        if ($ip == '::1' || $ip == '127.0.0.1') {
            return 'Local';
        }
        $api_url = "http://ip-api.com/json/" . $ip;
        $response = @file_get_contents($api_url);
        if ($response) {
            $data = json_decode($response);
            if ($data && $data->status == 'success') {
                return $data->country;
            }
        }
        return 'Unknown';
    } catch (Exception $e) {
        return 'Unknown';
    }
}

function getRealIP() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = array_map('trim', explode(',', $_SERVER[$header]));
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function getBrowser() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $browsers = [
        'Edge' => '/Edge|Edg/i',
        'Chrome' => '/Chrome/i',
        'Firefox' => '/Firefox/i',
        'Safari' => '/Safari/i',
        'Opera' => '/Opera|OPR/i',
        'Internet Explorer' => '/MSIE|Trident/i'
    ];

    foreach ($browsers as $browser => $pattern) {
        if (preg_match($pattern, $user_agent)) {
            if ($browser === 'Safari' && preg_match('/Chrome/i', $user_agent)) {
                continue;
            }
            if ($browser === 'Chrome' && preg_match('/Edge|Edg/i', $user_agent)) {
                return 'Edge';
            }
            return $browser;
        }
    }
    return 'Other';
}

function getOS() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $os_array = [
        'Windows' => '/Windows/i',
        'Mac OS X' => '/Mac OS X/i',
        'Mac' => '/Mac/i',
        'Linux' => '/Linux/i',
        'Ubuntu' => '/Ubuntu/i',
        'Android' => '/Android/i',
        'iOS' => '/iPhone|iPad|iPod/i'
    ];

    foreach ($os_array as $os => $pattern) {
        if (preg_match($pattern, $user_agent)) {
            return $os;
        }
    }
    return 'Unknown';
}

function getVisitorId() {
    $data = '';
    $data .= $_SERVER['HTTP_USER_AGENT'] ?? '';
    $data .= $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $data .= getRealIP();
    return hash('sha256', $data);
}

// Nuevas funciones de analytics
function trackPageView($data) {
    $data['event_type'] = 'pageview';
    $data['page_time'] = isset($_SESSION['page_entry_time']) ? 
        time() - $_SESSION['page_entry_time'] : 0;
    $_SESSION['page_entry_time'] = time();
    
    return $data;
}

function getDeviceInfo() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $device_type = 'desktop';
    
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
        $device_type = 'tablet';
    }
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $user_agent)) {
        $device_type = 'mobile';
    }
    
    $screen_resolution = $_SESSION['screen_resolution'] ?? 'unknown';
    
    return [
        'device_type' => $device_type,
        'screen_resolution' => $screen_resolution,
        'is_mobile' => $device_type != 'desktop',
        'is_bot' => preg_match('/bot|crawl|slurp|spider/i', $user_agent)
    ];
}

function trackUserBehavior() {
    return [
        'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
        'landing_page' => $_SESSION['landing_page'] ?? $_SERVER['REQUEST_URI'],
        'visit_count' => isset($_SESSION['visit_count']) ? $_SESSION['visit_count'] + 1 : 1,
        'pages_viewed' => isset($_SESSION['pages_viewed']) ? 
            array_merge($_SESSION['pages_viewed'], [$_SERVER['REQUEST_URI']]) : 
            [$_SERVER['REQUEST_URI']]
    ];
}

function prepareServerData() {
    $real_ip = getRealIP();
    $visitor_id = getVisitorId();
    $device_info = getDeviceInfo();
    $behavior = trackUserBehavior();
    $country_info = getVisitorCountry($real_ip);
    
    $data = [
        'timestamp' => time(),
        'date' => date('Y-m-d H:i:s'),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '/',
        'ip_address' => $real_ip,
        'visitor_id' => $visitor_id,
        'country' => $country_info,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'browser' => getBrowser(),
        'os' => getOS(),
        'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        'session_id' => session_id(),
        'device_type' => $device_info['device_type'],
        'screen_resolution' => $device_info['screen_resolution'],
        'is_mobile' => $device_info['is_mobile'],
        'is_bot' => $device_info['is_bot'],
        'referrer' => $behavior['referrer'],
        'landing_page' => $behavior['landing_page'],
        'visit_count' => $behavior['visit_count'],
        'pages_viewed' => implode(',', array_slice($behavior['pages_viewed'], -5))
    ];
    
    // Actualizar datos de sesión
    $_SESSION['visit_count'] = $behavior['visit_count'];
    $_SESSION['pages_viewed'] = $behavior['pages_viewed'];
    if (!isset($_SESSION['landing_page'])) {
        $_SESSION['landing_page'] = $_SERVER['REQUEST_URI'];
    }
    
    return trackPageView($data);
}

// Código principal
try {
    $serverData = prepareServerData();
    
    // Guardar en CSV con ruta relativa
    $fileExists = file_exists($csvFile);
    $fileHandle = fopen($csvFile, 'a');
    if ($fileHandle === false) {
        throw new Exception("No se puede abrir el archivo CSV.");
    }
    
    if (!$fileExists) {
        fputcsv($fileHandle, array_keys($serverData), '|');
        chmod($csvFile, 0666);
    }
    
    fputcsv($fileHandle, $serverData, '|');
    fclose($fileHandle);
    
    // Guardar eventos en JSON
    $events = [];
    if (file_exists($eventsFile)) {
        $events = json_decode(file_get_contents($eventsFile), true) ?? [];
    }
    $events[] = $serverData;
    file_put_contents($eventsFile, json_encode($events, JSON_PRETTY_PRINT));
    
    // Limpiar caché cuando hay nuevos datos
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
    
} catch (Exception $e) {
    error_log("Error en analytics: " . $e->getMessage(), 0);
}

ob_end_flush();
?>

<script>
// Detectar resolución de pantalla
window.onload = function() {
    let resolution = window.screen.width + 'x' + window.screen.height;
    document.cookie = 'screen_resolution=' + resolution + '; path=/';
}
</script>