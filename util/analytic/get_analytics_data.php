
<?php
/**
 * Archivo para obtener datos de analytics.
 * 
 * Este archivo procesa y devuelve datos de analytics en formato JSON. Utiliza caché para mejorar
 * el rendimiento y permite la actualización forzada de datos. Filtra eventos por período y calcula
 * diversas métricas y estadísticas.
 * 
 * Autor: franHR
 */
header('Content-Type: application/json');
session_start();

// Configuración de rutas relativas
$baseDir = dirname(dirname(dirname(__FILE__))); 
$dataDir = $baseDir . '/inc/analytics';
$cacheFile = $dataDir . '/cache.json';
$eventsFile = $dataDir . '/events.json';

// Verificar que los directorios y archivos existan
if (!file_exists($dataDir)) {
    die(json_encode(['error' => 'Directorio de analytics no encontrado', 'path' => $dataDir]));
}

// Configuración de caché
$cache_time = 300; // 5 minutos
$force_refresh = isset($_GET['refresh']) && $_GET['refresh'] === 'true';

// Debug mode
$debug = isset($_GET['debug']) && $_GET['debug'] === 'true';
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

try {
    // Intentar usar caché primero
    if (!$force_refresh && file_exists($cacheFile)) {
        $cache_data = json_decode(file_get_contents($cacheFile), true);
        if ($cache_data && time() - ($cache_data['timestamp'] ?? 0) < $cache_time) {
            echo json_encode($cache_data['data']);
            exit;
        }
    }

    // Verificar archivo de eventos
    if (!file_exists($eventsFile)) {
        die(json_encode(['error' => 'No hay datos de eventos disponibles']));
    }

    // Leer y procesar datos
    $data = leerDatosAnalytics();

    // Guardar en caché
    $cache_data = [
        'timestamp' => time(),
        'data' => $data
    ];
    file_put_contents($cacheFile, json_encode($cache_data));

    // Enviar respuesta
    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

function leerDatosAnalytics() {
    global $eventsFile;
    $events = [];
    
    if (file_exists($eventsFile)) {
        $events = json_decode(file_get_contents($eventsFile), true) ?? [];
    }

    // Filtrar por período
    $periodo = $_GET['periodo'] ?? '7d';
    $limitTime = time();
    
    switch($periodo) {
        case '24h':
            $limitTime = strtotime('-24 hours');
            break;
        case '7d':
            $limitTime = strtotime('-7 days');
            break;
        case '30d':
            $limitTime = strtotime('-30 days');
            break;
        case 'custom':
            // Aquí puedes añadir lógica para fechas personalizadas
            $limitTime = strtotime('-7 days'); // valor por defecto
            break;
        default:
            $limitTime = strtotime('-7 days');
    }

    $events = array_filter($events, function($event) use ($limitTime) {
        return isset($event['timestamp']) && $event['timestamp'] >= $limitTime;
    });
    
    // Procesar datos
    $visitasTotales = count($events);
    $usuariosUnicos = count(array_unique(array_column($events, 'visitor_id')));
    
    // Calcular tiempo promedio
    $tiemposVisita = array_column($events, 'page_time');
    $tiempoPromedio = $tiemposVisita ? array_sum($tiemposVisita) / count($tiemposVisita) : 0;
    
    // Calcular estadísticas
    $horasPico = obtenerHorasPico($events);
    $fuentesTrafico = obtenerFuentesTrafico($events);
    
    $stats = [
        'visitasTotales' => $visitasTotales,
        'usuariosUnicos' => $usuariosUnicos,
        'tiempoPromedio' => gmdate('i:s', $tiempoPromedio),
        'tasaRebote' => calcularTasaRebote($events),
        'fechas' => obtenerUltimos7Dias($events),
        'visitas' => obtenerVisitasPorDia($events),
        'dispositivos' => contarDispositivos($events),
        'paginas' => obtenerPaginasPopulares($events),
        'paises' => obtenerPaisesPrincipales($events),
        'horasPico' => $horasPico,
        'fuentesTrafico' => $fuentesTrafico,
        'tendencias' => calcularTendencias($events),
        'engagement' => calcularEngagement($events),
        'velocidadCarga' => obtenerVelocidadCarga($events),
        'ips' => obtenerRegistroIPs($events)
    ];
    
    return $stats;
}

// Funciones auxiliares
function calcularTasaRebote($events) {
    $sesiones = [];
    foreach ($events as $event) {
        $sesiones[$event['session_id']][] = $event;
    }
    
    $rebotes = 0;
    foreach ($sesiones as $sesion) {
        if (count($sesion) === 1) $rebotes++;
    }
    
    return round(($rebotes / count($sesiones)) * 100);
}

function obtenerUltimos7Dias($events) {
    // ... implementar lógica
    return array_slice(array_unique(array_map(function($e) {
        return date('Y-m-d', $e['timestamp']);
    }, $events)), -7);
}

function obtenerVisitasPorDia($events) {
    // ... implementar lógica
    $visitas = [];
    foreach ($events as $event) {
        $fecha = date('Y-m-d', $event['timestamp']);
        $visitas[$fecha] = ($visitas[$fecha] ?? 0) + 1;
    }
    return array_values($visitas);
}

function contarDispositivos($events) {
    $dispositivos = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0];
    foreach ($events as $event) {
        $dispositivos[$event['device_type']]++;
    }
    return array_values($dispositivos);
}

function obtenerPaginasPopulares($events) {
    $paginas = [];
    foreach ($events as $event) {
        $paginas[$event['request_uri']] = ($paginas[$event['request_uri']] ?? 0) + 1;
    }
    arsort($paginas);
    
    return array_map(function($url, $visits) use ($events) {
        return [
            'url' => $url,
            'visitas' => $visits,
            'porcentaje' => round(($visits / count($events)) * 100, 1)
        ];
    }, array_keys($paginas), array_values($paginas));
}

function obtenerPaisesPrincipales($events) {
    // ... implementar lógica similar a obtenerPaginasPopulares
    $paises = [];
    foreach ($events as $event) {
        $paises[$event['country']] = ($paises[$event['country']] ?? 0) + 1;
    }
    arsort($paises);
    
    return array_map(function($pais, $visits) use ($events) {
        return [
            'pais' => $pais,
            'visitas' => $visits,
            'porcentaje' => round(($visits / count($events)) * 100, 1)
        ];
    }, array_keys($paises), array_values($paises));
}

function obtenerHorasPico($events) {
    $horas = array_fill(0, 24, 0);
    foreach ($events as $event) {
        $hora = (int)date('G', $event['timestamp']);
        $horas[$hora]++;
    }
    
    return [
        'labels' => array_map(function($h) { 
            return str_pad($h, 2, '0', STR_PAD_LEFT) . ':00'; 
        }, array_keys($horas)),
        'data' => array_values($horas)
    ];
}

function obtenerFuentesTrafico($events) {
    $fuentes = [];
    foreach ($events as $event) {
        $referrer = empty($event['referrer']) ? 'Directo' : parse_url($event['referrer'], PHP_URL_HOST);
        if ($referrer === null || $referrer === '') {
            $referrer = 'Directo';
        }
        $fuentes[$referrer] = ($fuentes[$referrer] ?? 0) + 1;
    }
    arsort($fuentes);
    $top_fuentes = array_slice($fuentes, 0, 5);
    
    return [
        'labels' => array_keys($top_fuentes),
        'data' => array_values($top_fuentes)
    ];
}

function calcularEngagement($events) {
    $sesiones = [];
    foreach ($events as $event) {
        $sesiones[$event['session_id']][] = $event;
    }
    
    $engagement = [
        'tiempo_promedio_sesion' => 0,
        'paginas_por_sesion' => 0,
        'tasa_conversion' => 0
    ];
    
    foreach ($sesiones as $sesion) {
        $engagement['tiempo_promedio_sesion'] += end($sesion)['timestamp'] - $sesion[0]['timestamp'];
        $engagement['paginas_por_sesion'] += count($sesion);
    }
    
    $numSesiones = count($sesiones);
    if ($numSesiones > 0) {
        $engagement['tiempo_promedio_sesion'] /= $numSesiones;
        $engagement['paginas_por_sesion'] /= $numSesiones;
    }
    
    return $engagement;
}

function calcularTendencias($events) {
    $periodoActual = array_filter($events, function($event) {
        return $event['timestamp'] >= strtotime('-7 days');
    });
    
    $periodoAnterior = array_filter($events, function($event) {
        return $event['timestamp'] < strtotime('-7 days') && 
               $event['timestamp'] >= strtotime('-14 days');
    });
    
    return [
        'visitas' => [
            'actual' => count($periodoActual),
            'anterior' => count($periodoAnterior)
        ],
        'usuarios' => [
            'actual' => count(array_unique(array_column($periodoActual, 'visitor_id'))),
            'anterior' => count(array_unique(array_column($periodoAnterior, 'visitor_id')))
        ]
    ];
}

function obtenerVelocidadCarga($events) {
    return [
        'promedio' => 0, // Implementar si tienes datos de velocidad de carga
        'maximo' => 0,
        'minimo' => 0
    ];
}

function obtenerRegistroIPs($events) {
    $ips = [];
    foreach ($events as $event) {
        $ip = $event['ip_address'];
        if (!isset($ips[$ip])) {
            $ips[$ip] = [
                'ip_address' => $ip,
                'country' => $event['country'],
                'visitas' => 0,
                'ultima_visita' => 0
            ];
        }
        $ips[$ip]['visitas']++;
        $ips[$ip]['ultima_visita'] = max($ips[$ip]['ultima_visita'], $event['timestamp']);
    }
    
    // Ordenar por número de visitas
    uasort($ips, function($a, $b) {
        return $b['visitas'] - $a['visitas'];
    });
    
    // Calcular porcentajes
    $total_visitas = array_sum(array_column($ips, 'visitas'));
    foreach ($ips as &$ip) {
        $ip['porcentaje'] = round(($ip['visitas'] / $total_visitas) * 100, 1);
    }
    
    return array_values($ips);
}
?>
