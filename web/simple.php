<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎮 Gerenciador de Lobbys - Painel Simples</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .status-card {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-item {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .status-item.active {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
        }
        
        .status-item h3 {
            font-size: 1.1em;
            margin-bottom: 10px;
            color: #333;
        }
        
        .status-item .value {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .lobby-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .lobby-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .lobby-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .lobby-card.master {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            border-color: #ff9a9e;
        }
        
        .lobby-card h3 {
            font-size: 1.3em;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .lobby-card .path {
            font-family: monospace;
            background: white;
            padding: 8px;
            border-radius: 5px;
            margin-bottom: 15px;
            word-break: break-all;
            font-size: 0.9em;
        }
        
        .lobby-card .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            color: #2c3e50;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
            color: #2c3e50;
        }
        
        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .control-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .big-button {
            padding: 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.2em;
            font-weight: bold;
            transition: all 0.3s ease;
            text-align: center;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .big-button.start {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            color: #2c3e50;
        }
        
        .big-button.stop {
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
            color: #2c3e50;
        }
        
        .big-button.sync {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #2c3e50;
        }
        
        .big-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .big-button .icon {
            font-size: 2em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #495057;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .log-viewer {
            background: #2b2b2b;
            color: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }
        
        .log-line {
            margin-bottom: 5px;
            padding: 2px 0;
        }
        
        .log-line.error { color: #ff6b6b; }
        .log-line.success { color: #51cf66; }
        .log-line.info { color: #339af0; }
        
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Gerenciador de Lobbys</h1>
            <p>Sistema de sincronização automática para múltiplos servidores</p>
        </div>

        <?php
        // Configurações
        $configFile = __DIR__ . '/../lobby-config.json';
        $partialSyncFile = __DIR__ . '/../partial-sync.json';
        $syncScript = __DIR__ . '/../lobby-sync.php';
        
        // Carrega configuração
        $config = [];
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true) ?: [];
        }
        
        // Processa ações
        $message = '';
        $messageType = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'save_config':
                    $config['master_lobby'] = $_POST['master_lobby'] ?? '';
                    $config['lobbys'] = array_filter(array_map('trim', explode("\n", $_POST['lobbys'] ?? '')));
                    $config['watch_folders'] = array_filter(array_map('trim', explode(',', $_POST['watch_folders'] ?? '')));
                    $config['exclude_patterns'] = array_filter(array_map('trim', explode(',', $_POST['exclude_patterns'] ?? '')));
                    $config['polling_interval'] = (int)($_POST['polling_interval'] ?? 2);
                    
                    if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                        $message = '✅ Configuração salva com sucesso!';
                        $messageType = 'success';
                    } else {
                        $message = '❌ Erro ao salvar configuração!';
                        $messageType = 'error';
                    }
                    break;
                    
                case 'start_sync':
                    // Inicia o processo em background
                    $cmd = sprintf('nohup php %s > /tmp/lobby-sync.log 2>&1 &', escapeshellarg($syncScript));
                    exec($cmd, $output, $returnVar);
                    $message = '🚀 Sincronização iniciada! Verifique os logs abaixo.';
                    $messageType = 'success';
                    break;
                    
                case 'stop_sync':
                    // Para o processo
                    exec("pkill -f 'php.*lobby-sync.php'");
                    $message = '🛑 Sincronização parada!';
                    $messageType = 'info';
                    break;
                    
                case 'sync_now':
                    // Executa sincronização única
                    exec(sprintf('php %s --once 2>&1', escapeshellarg($syncScript)), $output);
                    $message = '🔄 Sincronização manual executada!';
                    $messageType = 'success';
                    break;
            }
        }
        
        // Verifica status do processo
        $isRunning = false;
        exec("pgrep -f 'php.*lobby-sync.php'", $pids);
        $isRunning = !empty($pids);
        ?>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Status -->
        <div class="status-card">
            <div class="status-item <?php echo $isRunning ? 'active' : ''; ?>">
                <h3>Status do Sistema</h3>
                <div class="value"><?php echo $isRunning ? '🟢 Ativo' : '🔴 Parado'; ?></div>
            </div>
            <div class="status-item">
                <h3>Lobbys Configurados</h3>
                <div class="value"><?php echo count($config['lobbys'] ?? []); ?></div>
            </div>
            <div class="status-item">
                <h3>Pastas Monitoradas</h3>
                <div class="value"><?php echo count($config['watch_folders'] ?? []); ?></div>
            </div>
            <div class="status-item">
                <h3>Modo</h3>
                <div class="value"><?php echo extension_loaded('inotify') ? '⚡ Tempo Real' : '🔄 Polling'; ?></div>
            </div>
        </div>

        <!-- Controles Principais -->
        <div class="card">
            <h2 style="margin-bottom: 20px;">🎮 Controles Principais</h2>
            <div class="control-panel">
                <?php if (!$isRunning): ?>
                <button class="big-button start" onclick="submitAction('start_sync')">
                    <span class="icon">▶️</span>
                    <span>INICIAR SINCRONIZAÇÃO</span>
                </button>
                <?php else: ?>
                <button class="big-button stop" onclick="submitAction('stop_sync')">
                    <span class="icon">⏹️</span>
                    <span>PARAR SINCRONIZAÇÃO</span>
                </button>
                <?php endif; ?>
                
                <button class="big-button sync" onclick="submitAction('sync_now')">
                    <span class="icon">🔄</span>
                    <span>SINCRONIZAR AGORA</span>
                </button>
            </div>
        </div>

        <!-- Configuração Simples -->
        <div class="card">
            <h2 style="margin-bottom: 20px;">⚙️ Configuração Rápida</h2>
            <form method="post" id="configForm">
                <input type="hidden" name="action" value="save_config">
                
                <div class="form-group">
                    <label>📁 Pasta Principal (Master)</label>
                    <input type="text" name="master_lobby" value="<?php echo htmlspecialchars($config['master_lobby'] ?? '/srv/lobby_master'); ?>" 
                           placeholder="/srv/lobby_master" required>
                    <small style="color: #6c757d;">Esta pasta será a fonte para sincronização</small>
                </div>
                
                <div class="form-group">
                    <label>🎮 Lobbys para Sincronizar (um por linha)</label>
                    <textarea name="lobbys" rows="4" placeholder="/srv/lobby1&#10;/srv/lobby2&#10;/srv/lobby3&#10;/srv/lobby4" required><?php 
                        echo htmlspecialchars(implode("\n", $config['lobbys'] ?? [])); 
                    ?></textarea>
                    <small style="color: #6c757d;">Digite o caminho completo de cada lobby</small>
                </div>
                
                <div class="form-group">
                    <label>📂 Pastas para Monitorar (separadas por vírgula)</label>
                    <input type="text" name="watch_folders" value="<?php echo htmlspecialchars(implode(', ', $config['watch_folders'] ?? ['plugins', 'configs', 'scripts'])); ?>" 
                           placeholder="plugins, configs, scripts">
                    <small style="color: #6c757d;">Apenas estas pastas serão sincronizadas</small>
                </div>
                
                <div class="form-group">
                    <label>🚫 Arquivos para Ignorar (separados por vírgula)</label>
                    <input type="text" name="exclude_patterns" value="<?php echo htmlspecialchars(implode(', ', $config['exclude_patterns'] ?? ['*.tmp', '*.log', '*.lock', '.git', 'cache/*'])); ?>" 
                           placeholder="*.tmp, *.log, *.lock">
                    <small style="color: #6c757d;">Estes arquivos não serão sincronizados</small>
                </div>
                
                <div class="form-group">
                    <label>⏱️ Intervalo de Verificação (segundos)</label>
                    <input type="number" name="polling_interval" value="<?php echo $config['polling_interval'] ?? 2; ?>" 
                           min="1" max="60" required>
                    <small style="color: #6c757d;">Usado apenas se inotify não estiver disponível</small>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    💾 SALVAR CONFIGURAÇÃO
                </button>
            </form>
        </div>

        <!-- Visualização dos Lobbys -->
        <div class="card">
            <h2 style="margin-bottom: 20px;">🏠 Lobbys Configurados</h2>
            <div class="lobby-grid">
                <?php if (!empty($config['master_lobby'])): ?>
                <div class="lobby-card master">
                    <h3>👑 Master Lobby</h3>
                    <div class="path"><?php echo htmlspecialchars($config['master_lobby']); ?></div>
                    <div class="actions">
                        <button class="btn btn-success tooltip">
                            ✅ Fonte Principal
                            <span class="tooltiptext">Todas as mudanças aqui são replicadas</span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php foreach ($config['lobbys'] ?? [] as $index => $lobby): ?>
                <div class="lobby-card">
                    <h3>🎮 Lobby <?php echo $index + 1; ?></h3>
                    <div class="path"><?php echo htmlspecialchars($lobby); ?></div>
                    <div class="actions">
                        <button class="btn btn-primary tooltip">
                            📥 Recebe Atualizações
                            <span class="tooltiptext">Sincronizado automaticamente</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Logs -->
        <div class="card">
            <h2 style="margin-bottom: 20px;">📋 Últimos Logs</h2>
            <div class="log-viewer" id="logViewer">
                <?php
                $logFile = '/tmp/lobby-sync.log';
                if (file_exists($logFile)) {
                    $lines = array_slice(file($logFile), -50); // Últimas 50 linhas
                    foreach ($lines as $line) {
                        $class = '';
                        if (strpos($line, '✅') !== false || strpos($line, '✓') !== false) {
                            $class = 'success';
                        } elseif (strpos($line, '❌') !== false || strpos($line, '✗') !== false) {
                            $class = 'error';
                        } elseif (strpos($line, '📝') !== false || strpos($line, '🚀') !== false) {
                            $class = 'info';
                        }
                        echo '<div class="log-line ' . $class . '">' . htmlspecialchars($line) . '</div>';
                    }
                } else {
                    echo '<div class="log-line">Nenhum log disponível ainda. Inicie a sincronização para ver os logs.</div>';
                }
                ?>
            </div>
            <button onclick="refreshLogs()" class="btn btn-primary" style="margin-top: 10px;">
                🔄 Atualizar Logs
            </button>
        </div>
    </div>

    <script>
        function submitAction(action) {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `<input type="hidden" name="action" value="${action}">`;
            document.body.appendChild(form);
            form.submit();
        }
        
        function refreshLogs() {
            location.reload();
        }
        
        // Auto-refresh logs se o sistema estiver rodando
        <?php if ($isRunning): ?>
        setInterval(function() {
            const logViewer = document.getElementById('logViewer');
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newLogs = doc.getElementById('logViewer');
                    if (newLogs) {
                        logViewer.innerHTML = newLogs.innerHTML;
                        logViewer.scrollTop = logViewer.scrollHeight;
                    }
                });
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
