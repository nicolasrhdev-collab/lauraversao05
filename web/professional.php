<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby Sync - Painel de Controle</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a0a;
            color: #e0e0e0;
            min-height: 100vh;
            padding: 0;
        }
        
        .topbar {
            background: #1a1a1a;
            border-bottom: 2px solid #ff6b35;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .topbar h1 {
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #252525;
            padding: 10px 20px;
            border-radius: 4px;
            border: 1px solid #333;
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #4ade80;
            animation: pulse 2s infinite;
        }
        
        .status-dot.inactive {
            background: #ef4444;
            animation: none;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .status-text {
            font-size: 14px;
            font-weight: 500;
            color: #a0a0a0;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
        }
        
        .grid {
            display: grid;
            gap: 24px;
        }
        
        .section {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 30px;
        }
        
        .section-header {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 1px solid #2a2a2a;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #252525;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 20px;
        }
        
        .stat-label {
            font-size: 13px;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #ff6b35;
        }
        
        .controls {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: #ff6b35;
            color: #ffffff;
        }
        
        .btn-primary:hover {
            background: #ff8555;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
        }
        
        .btn-secondary {
            background: #252525;
            color: #e0e0e0;
            border: 1px solid #404040;
        }
        
        .btn-secondary:hover {
            background: #303030;
            border-color: #ff6b35;
        }
        
        .btn-danger {
            background: #ef4444;
            color: #ffffff;
        }
        
        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #a0a0a0;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px 16px;
            background: #252525;
            border: 1px solid #404040;
            border-radius: 6px;
            color: #e0e0e0;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
        }
        
        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #ff6b35;
            background: #2a2a2a;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
            font-family: 'SF Mono', Monaco, monospace;
        }
        
        .form-hint {
            font-size: 12px;
            color: #666;
            margin-top: 6px;
        }
        
        .lobby-list {
            display: grid;
            gap: 12px;
        }
        
        .lobby-item {
            background: #252525;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lobby-item.master {
            border-left: 3px solid #ff6b35;
            background: #2a2015;
        }
        
        .lobby-path {
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 13px;
            color: #b0b0b0;
        }
        
        .lobby-type {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .lobby-type.master {
            color: #ff6b35;
        }
        
        .terminal {
            background: #0f0f0f;
            border: 1px solid #2a2a2a;
            border-radius: 6px;
            padding: 20px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            line-height: 1.6;
        }
        
        .terminal-line {
            color: #888;
            margin-bottom: 4px;
        }
        
        .terminal-line.success {
            color: #4ade80;
        }
        
        .terminal-line.error {
            color: #ef4444;
        }
        
        .terminal-line.warning {
            color: #fbbf24;
        }
        
        .terminal-line.info {
            color: #60a5fa;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 6px;
            margin-bottom: 24px;
            border-left: 3px solid;
            font-size: 14px;
        }
        
        .alert-success {
            background: #0d2818;
            border-color: #4ade80;
            color: #86efac;
        }
        
        .alert-error {
            background: #2a0d0d;
            border-color: #ef4444;
            color: #fca5a5;
        }
        
        .alert-info {
            background: #0d1a2a;
            border-color: #60a5fa;
            color: #93c5fd;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #2a2a2a;
        }
        
        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid #252525;
            color: #b0b0b0;
            font-size: 14px;
        }
        
        .table tr:hover {
            background: #252525;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-active {
            background: #0d2818;
            color: #4ade80;
        }
        
        .badge-inactive {
            background: #2a0d0d;
            color: #ef4444;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1a1a1a;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #404040;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #505050;
        }
    </style>
</head>
<body>
    <?php
    // Configurações
    $configFile = __DIR__ . '/../lobby-config.json';
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
                    $message = 'Configuração salva com sucesso';
                    $messageType = 'success';
                } else {
                    $message = 'Erro ao salvar configuração';
                    $messageType = 'error';
                }
                break;
                
            case 'start_sync':
                $cmd = sprintf('nohup php %s > /tmp/lobby-sync.log 2>&1 &', escapeshellarg($syncScript));
                exec($cmd);
                $message = 'Sincronização iniciada';
                $messageType = 'success';
                break;
                
            case 'stop_sync':
                exec("pkill -f 'php.*lobby-sync.php'");
                $message = 'Sincronização parada';
                $messageType = 'info';
                break;
                
            case 'sync_now':
                exec(sprintf('php %s --once 2>&1', escapeshellarg($syncScript)), $output);
                $message = 'Sincronização manual executada';
                $messageType = 'success';
                break;
        }
    }
    
    // Verifica status
    $isRunning = false;
    exec("pgrep -f 'php.*lobby-sync.php'", $pids);
    $isRunning = !empty($pids);
    ?>

    <div class="topbar">
        <h1>LOBBY SYNC</h1>
        <div class="status-indicator">
            <div class="status-dot <?php echo !$isRunning ? 'inactive' : ''; ?>"></div>
            <span class="status-text"><?php echo $isRunning ? 'Sistema Ativo' : 'Sistema Parado'; ?></span>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Status</div>
                <div class="stat-value"><?php echo $isRunning ? 'ATIVO' : 'PARADO'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Lobbys</div>
                <div class="stat-value"><?php echo count($config['lobbys'] ?? []); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pastas</div>
                <div class="stat-value"><?php echo count($config['watch_folders'] ?? []); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Modo</div>
                <div class="stat-value" style="font-size: 16px; margin-top: 8px;">
                    <?php echo extension_loaded('inotify') ? 'TEMPO REAL' : 'POLLING'; ?>
                </div>
            </div>
        </div>

        <!-- Controles -->
        <div class="section">
            <div class="section-header">CONTROLES</div>
            <div class="controls">
                <?php if (!$isRunning): ?>
                <button class="btn btn-primary" onclick="submitAction('start_sync')">
                    INICIAR SINCRONIZAÇÃO
                </button>
                <?php else: ?>
                <button class="btn btn-danger" onclick="submitAction('stop_sync')">
                    PARAR SINCRONIZAÇÃO
                </button>
                <?php endif; ?>
                
                <button class="btn btn-secondary" onclick="submitAction('sync_now')">
                    SINCRONIZAR AGORA
                </button>
                
                <button class="btn btn-secondary" onclick="location.reload()">
                    ATUALIZAR PÁGINA
                </button>
            </div>
        </div>

        <!-- Configuração -->
        <div class="section">
            <div class="section-header">CONFIGURAÇÃO</div>
            <form method="post" id="configForm">
                <input type="hidden" name="action" value="save_config">
                
                <div class="form-group">
                    <label class="form-label">Pasta Principal (Master)</label>
                    <input type="text" name="master_lobby" class="form-input" 
                           value="<?php echo htmlspecialchars($config['master_lobby'] ?? '/srv/lobby_master'); ?>" 
                           placeholder="/srv/lobby_master" required>
                    <div class="form-hint">Pasta fonte para sincronização</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Lobbys para Sincronizar</label>
                    <textarea name="lobbys" class="form-textarea" rows="4" 
                              placeholder="/srv/lobby1&#10;/srv/lobby2&#10;/srv/lobby3&#10;/srv/lobby4" required><?php 
                        echo htmlspecialchars(implode("\n", $config['lobbys'] ?? [])); 
                    ?></textarea>
                    <div class="form-hint">Um caminho por linha</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pastas para Monitorar</label>
                    <input type="text" name="watch_folders" class="form-input" 
                           value="<?php echo htmlspecialchars(implode(', ', $config['watch_folders'] ?? ['plugins', 'configs', 'scripts'])); ?>" 
                           placeholder="plugins, configs, scripts">
                    <div class="form-hint">Separadas por vírgula</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Padrões de Exclusão</label>
                    <input type="text" name="exclude_patterns" class="form-input" 
                           value="<?php echo htmlspecialchars(implode(', ', $config['exclude_patterns'] ?? ['*.tmp', '*.log', '*.lock'])); ?>" 
                           placeholder="*.tmp, *.log, *.lock">
                    <div class="form-hint">Arquivos ignorados</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Intervalo de Verificação (segundos)</label>
                    <input type="number" name="polling_interval" class="form-input" 
                           value="<?php echo $config['polling_interval'] ?? 2; ?>" 
                           min="1" max="60" required style="max-width: 150px;">
                    <div class="form-hint">Usado apenas se inotify não estiver disponível</div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    SALVAR CONFIGURAÇÃO
                </button>
            </form>
        </div>

        <!-- Lobbys Configurados -->
        <div class="section">
            <div class="section-header">LOBBYS CONFIGURADOS</div>
            <div class="lobby-list">
                <?php if (!empty($config['master_lobby'])): ?>
                <div class="lobby-item master">
                    <div>
                        <div class="lobby-type master">MASTER</div>
                        <div class="lobby-path"><?php echo htmlspecialchars($config['master_lobby']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php foreach ($config['lobbys'] ?? [] as $index => $lobby): ?>
                <div class="lobby-item">
                    <div>
                        <div class="lobby-type">LOBBY <?php echo $index + 1; ?></div>
                        <div class="lobby-path"><?php echo htmlspecialchars($lobby); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Logs -->
        <div class="section">
            <div class="section-header">LOGS DO SISTEMA</div>
            <div class="terminal" id="logViewer">
                <?php
                $logFile = '/tmp/lobby-sync.log';
                if (file_exists($logFile)) {
                    $lines = array_slice(file($logFile), -100);
                    foreach ($lines as $line) {
                        $class = '';
                        if (strpos($line, '✅') !== false || strpos($line, '✓') !== false) {
                            $class = 'success';
                        } elseif (strpos($line, '❌') !== false || strpos($line, '✗') !== false) {
                            $class = 'error';
                        } elseif (strpos($line, '⚠️') !== false) {
                            $class = 'warning';
                        } elseif (strpos($line, '📝') !== false || strpos($line, '🚀') !== false) {
                            $class = 'info';
                        }
                        echo '<div class="terminal-line ' . $class . '">' . htmlspecialchars($line) . '</div>';
                    }
                } else {
                    echo '<div class="terminal-line">Nenhum log disponível. Inicie a sincronização para gerar logs.</div>';
                }
                ?>
            </div>
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
        
        <?php if ($isRunning): ?>
        // Auto-refresh logs
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
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
