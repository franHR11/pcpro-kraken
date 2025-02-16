
<?php
/**
 * Archivo principal del panel de Analytics.
 * 
 * Este archivo genera la interfaz de usuario del panel de Analytics, mostrando métricas clave,
 * gráficos y tablas de datos. Permite la actualización de datos en tiempo real y la visualización
 * de tendencias y estadísticas de tráfico.
 * 
 * Autor: franHR
 */
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    echo "Intento de acceso incorrecto";
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.0.0/dist/chart.min.css" rel="stylesheet">
    <style>
        .metric-card {
            border-radius: 8px;
            padding: 20px;
            margin: 10px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .metric-title {
            color: #7f8c8d;
            font-size: 14px;
        }
        .chart-container {
            padding: 20px;
            background: white;
            border-radius: 8px;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .trend-up { color: #2ecc71; }
        .trend-down { color: #e74c3c; }
        .trend-indicator {
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Panel de Analytics</h1>
            <div>
                <p class="text-muted mb-0">
                    Última actualización: <span id="ultima-actualizacion">--:--:--</span>
                    <button onclick="actualizarDatos(true)" class="btn btn-sm btn-primary ms-2">
                        Actualizar ahora
                    </button>
                </p>
            </div>
        </div>
        
        <!-- Añadir filtros de tiempo -->
        <div class="mb-3">
            <select id="tiempo-filtro" class="form-select form-select-sm" style="width: auto;" onchange="actualizarDatos(true)">
                <option value="24h">Últimas 24 horas</option>
                <option value="7d" selected>Últimos 7 días</option>
                <option value="30d">Últimos 30 días</option>
                <option value="custom">Personalizado</option>
            </select>
        </div>

        <!-- Métricas principales -->
        <div class="row">
            <div class="col-md-3">
                <div class="metric-card">
                    <div id="visitas-totales" class="metric-value">0</div>
                    <div class="metric-title">Visitas Totales</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div id="usuarios-unicos" class="metric-value">0</div>
                    <div class="metric-title">Usuarios Únicos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div id="tiempo-promedio" class="metric-value">0:00</div>
                    <div class="metric-title">Tiempo Promedio</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div id="tasa-rebote" class="metric-value">0%</div>
                    <div class="metric-title">Tasa de Rebote</div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5>Visitas por Día</h5>
                    <canvas id="visitas-chart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5>Distribución de Dispositivos</h5>
                    <canvas id="dispositivos-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Añadir más métricas -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5>Horarios de mayor tráfico</h5>
                    <canvas id="horas-chart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5>Fuentes de tráfico</h5>
                    <canvas id="fuentes-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Modificar las tablas de datos -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Páginas más visitadas (Top 20)</h5>
                        <button id="ver-mas-paginas" class="btn btn-sm btn-outline-primary">
                            Ver todas las páginas
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="paginas-table">
                            <thead>
                                <tr>
                                    <th>URL</th>
                                    <th>Visitas</th>
                                    <th>% del Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Países (Top 20)</h5>
                        <button id="ver-mas-paises" class="btn btn-sm btn-outline-primary">
                            Ver todos los países
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="paises-table">
                            <thead>
                                <tr>
                                    <th>País</th>
                                    <th>Visitas</th>
                                    <th>% del Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modificar la tabla de IPs -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Registro de IPs (Top 20)</h5>
                        <button id="ver-mas-ips" class="btn btn-sm btn-outline-primary">
                            Ver todas las IPs
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="ips-table">
                            <thead>
                                <tr>
                                    <th>IP</th>
                                    <th>País</th>
                                    <th>Visitas</th>
                                    <th>Última visita</th>
                                    <th>% del Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Añadir función para mostrar tendencias
        function mostrarTendencia(elementId, valorActual, valorAnterior) {
            const elemento = document.getElementById(elementId);
            const diff = ((valorActual - valorAnterior) / valorAnterior * 100).toFixed(1);
            const indicator = document.createElement('span');
            indicator.classList.add('trend-indicator');
            
            if (diff > 0) {
                indicator.classList.add('trend-up');
                indicator.innerHTML = `↑ ${diff}%`;
            } else if (diff < 0) {
                indicator.classList.add('trend-down');
                indicator.innerHTML = `↓ ${Math.abs(diff)}%`;
            }
            
            elemento.appendChild(indicator);
        }

        // Modificar actualizarDatos para incluir nuevas métricas
        function actualizarDatos(forzarActualizacion = false) {
            // Limpiar gráficos existentes
            const charts = Chart.getChart('visitas-chart');
            if (charts) charts.destroy();
            const chartDisp = Chart.getChart('dispositivos-chart');
            if (chartDisp) chartDisp.destroy();
            const chartHoras = Chart.getChart('horas-chart');
            if (chartHoras) chartHoras.destroy();
            const chartFuentes = Chart.getChart('fuentes-chart');
            if (chartFuentes) chartFuentes.destroy();

            const filtroTiempo = document.getElementById('tiempo-filtro').value;
            const url = `get_analytics_data.php?refresh=${forzarActualizacion}&periodo=${filtroTiempo}`;
                
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Actualizar hora
                    const ahora = new Date();
                    document.getElementById('ultima-actualizacion').textContent = 
                        ahora.toLocaleTimeString();

                    // Actualizar métricas
                    document.getElementById('visitas-totales').textContent = data.visitasTotales;
                    document.getElementById('usuarios-unicos').textContent = data.usuariosUnicos;
                    document.getElementById('tiempo-promedio').textContent = data.tiempoPromedio;
                    document.getElementById('tasa-rebote').textContent = data.tasaRebote + '%';

                    // Actualizar gráficos
                    actualizarGraficos(data);
                    
                    // Actualizar tablas
                    actualizarTablas(data);
                })
                .catch(error => console.error('Error:', error));
        }

        function actualizarGraficos(data) {
            // Gráfico de visitas
            new Chart(document.getElementById('visitas-chart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.fechas,
                    datasets: [{
                        label: 'Visitas',
                        data: data.visitas,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                }
            });

            // Gráfico de dispositivos
            new Chart(document.getElementById('dispositivos-chart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Desktop', 'Mobile', 'Tablet'],
                    datasets: [{
                        data: data.dispositivos,
                        backgroundColor: [
                            'rgb(54, 162, 235)',
                            'rgb(255, 99, 132)',
                            'rgb(255, 205, 86)'
                        ]
                    }]
                }
            });

            // Gráfico de horas pico
            new Chart(document.getElementById('horas-chart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.horasPico.labels,
                    datasets: [{
                        label: 'Visitas por hora',
                        data: data.horasPico.data,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Gráfico de fuentes de tráfico
            new Chart(document.getElementById('fuentes-chart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: data.fuentesTrafico.labels,
                    datasets: [{
                        data: data.fuentesTrafico.data,
                        backgroundColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 205, 86)',
                            'rgb(75, 192, 192)',
                            'rgb(153, 102, 255)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }

        function actualizarTablas(data) {
            // Modificar actualización de tabla de páginas
            const paginasBody = document.querySelector('#paginas-table tbody');
            const verMasPaginasBtn = document.getElementById('ver-mas-paginas');
            let paginasData = data.paginas;
            let mostrandoTodasPaginas = false;

            function actualizarTablaPaginas(mostrarTodas = false) {
                const paginasAMostrar = mostrarTodas ? paginasData : paginasData.slice(0, 20);
                paginasBody.innerHTML = paginasAMostrar.map(p => `
                    <tr>
                        <td>${p.url}</td>
                        <td>${p.visitas}</td>
                        <td>${p.porcentaje}%</td>
                    </tr>
                `).join('');
            }

            verMasPaginasBtn.onclick = function() {
                mostrandoTodasPaginas = !mostrandoTodasPaginas;
                actualizarTablaPaginas(mostrandoTodasPaginas);
                verMasPaginasBtn.textContent = mostrandoTodasPaginas ? 'Mostrar Top 20' : 'Ver todas las páginas';
            };

            // Modificar actualización de tabla de países
            const paisesBody = document.querySelector('#paises-table tbody');
            const verMasPaisesBtn = document.getElementById('ver-mas-paises');
            let paisesData = data.paises;
            let mostrandoTodosPaises = false;

            function actualizarTablaPaises(mostrarTodos = false) {
                const paisesAMostrar = mostrarTodos ? paisesData : paisesData.slice(0, 20);
                paisesBody.innerHTML = paisesAMostrar.map(p => `
                    <tr>
                        <td>${p.pais}</td>
                        <td>${p.visitas}</td>
                        <td>${p.porcentaje}%</td>
                    </tr>
                `).join('');
            }

            verMasPaisesBtn.onclick = function() {
                mostrandoTodosPaises = !mostrandoTodosPaises;
                actualizarTablaPaises(mostrandoTodosPaises);
                verMasPaisesBtn.textContent = mostrandoTodosPaises ? 'Mostrar Top 20' : 'Ver todos los países';
            };

            // Mostrar inicialmente solo top 20
            actualizarTablaPaginas(false);
            actualizarTablaPaises(false);

            // Modificar la actualización de la tabla de IPs
            const ipsBody = document.querySelector('#ips-table tbody');
            const verMasBtn = document.getElementById('ver-mas-ips');
            let ipsData = data.ips;
            let mostrandoTodas = false;

            function actualizarTablaIPs(mostrarTodas = false) {
                const ipsAMostrar = mostrarTodas ? ipsData : ipsData.slice(0, 20);
                ipsBody.innerHTML = ipsAMostrar.map(ip => `
                    <tr>
                        <td>${ip.ip_address}</td>
                        <td>${ip.country}</td>
                        <td>${ip.visitas}</td>
                        <td>${new Date(ip.ultima_visita * 1000).toLocaleString()}</td>
                        <td>${ip.porcentaje}%</td>
                    </tr>
                `).join('');
            }

            verMasBtn.onclick = function() {
                mostrandoTodas = !mostrandoTodas;
                actualizarTablaIPs(mostrandoTodas);
                verMasBtn.textContent = mostrandoTodas ? 'Mostrar Top 20' : 'Ver todas las IPs';
            };

            // Mostrar inicialmente solo top 20
            actualizarTablaIPs(false);
        }

        // Actualizar datos cada minuto
        actualizarDatos();
        setInterval(actualizarDatos, 60000);
    </script>
    <script src="https://aurora.pcprogramacion.es/tracking/script.js"></script>
</body>
</html>