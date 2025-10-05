#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Lobby Sync - Sistema de sincronização automática para múltiplos lobbys
 * 
 * Recursos:
 * - FileWatcher com inotify (monitora mudanças em tempo real)
 * - Sincronização automática para múltiplos lobbys
 * - Suporte a sincronização parcial de arquivos (linhas específicas)
 * - Interface simples para configuração
 */

class LobbySync
{
    private array $config;
    private $inotify = null;
    private array $watches = [];
    private array $partialSyncRules = [];
    
    public function __construct(string $configFile = 'lobby-config.json')
    {
        $this->loadConfig($configFile);
        $this->loadPartialSyncRules();
        
        // Verifica se inotify está disponível
        if (!extension_loaded('inotify')) {
            $this->log("⚠️  AVISO: Extensão inotify não encontrada. Usando modo polling (menos eficiente).");
            $this->log("   Para instalar: sudo pecl install inotify");
        }
    }
    
    private function loadConfig(string $file): void
    {
        if (!file_exists($file)) {
            // Cria configuração padrão
            $default = [
                'master_lobby' => '/srv/lobby_master',
                'lobbys' => [
                    '/srv/lobby1',
                    '/srv/lobby2', 
                    '/srv/lobby3',
                    '/srv/lobby4'
                ],
                'watch_folders' => [
                    'plugins',
                    'configs',
                    'scripts'
                ],
                'exclude_patterns' => [
                    '*.tmp',
                    '*.log',
                    '*.lock',
                    '.git',
                    'cache/*'
                ],
                'polling_interval' => 2, // segundos (usado se inotify não estiver disponível)
                'auto_start' => true
            ];
            file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->log("📝 Configuração padrão criada em: $file");
            $this->log("   Edite o arquivo para configurar seus lobbys!");
            exit(0);
        }
        
        $json = file_get_contents($file);
        $this->config = json_decode($json, true) ?: [];
    }
    
    private function loadPartialSyncRules(): void
    {
        $file = 'partial-sync.json';
        if (!file_exists($file)) {
            // Cria regras de exemplo
            $default = [
                'rules' => [
                    [
                        'file_pattern' => 'server.properties',
                        'sync_mode' => 'lines',
                        'sync_lines' => [
                            'server-port=',
                            'max-players=',
                            'difficulty=',
                            'gamemode='
                        ],
                        'description' => 'Sincroniza apenas configurações específicas do servidor'
                    ],
                    [
                        'file_pattern' => 'bukkit.yml',
                        'sync_mode' => 'sections',
                        'sync_sections' => [
                            'settings:',
                            'spawn-limits:'
                        ],
                        'description' => 'Sincroniza seções específicas do Bukkit'
                    ]
                ]
            ];
            file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        
        $json = file_get_contents($file);
        $data = json_decode($json, true) ?: [];
        $this->partialSyncRules = $data['rules'] ?? [];
    }
    
    public function start(): void
    {
        $this->log("🚀 Iniciando Lobby Sync...");
        $this->log("📂 Pasta principal: " . $this->config['master_lobby']);
        $this->log("🎮 Lobbys configurados: " . count($this->config['lobbys']));
        
        // Verifica se as pastas existem
        if (!is_dir($this->config['master_lobby'])) {
            $this->log("❌ ERRO: Pasta principal não encontrada: " . $this->config['master_lobby']);
            exit(1);
        }
        
        foreach ($this->config['lobbys'] as $lobby) {
            if (!is_dir($lobby)) {
                $this->log("⚠️  Lobby não encontrado (será criado): $lobby");
                @mkdir($lobby, 0755, true);
            }
        }
        
        // Sincronização inicial
        $this->log("\n📋 Fazendo sincronização inicial...");
        $this->syncAll();
        
        // Inicia monitoramento
        if (extension_loaded('inotify')) {
            $this->startInotifyWatch();
        } else {
            $this->startPollingWatch();
        }
    }
    
    private function startInotifyWatch(): void
    {
        $this->log("\n👁️  Modo FileWatcher (inotify) ativado!");
        $this->log("   Monitorando mudanças em tempo real...\n");
        
        $this->inotify = inotify_init();
        stream_set_blocking($this->inotify, false);
        
        // Adiciona watches para cada pasta configurada
        foreach ($this->config['watch_folders'] as $folder) {
            $path = $this->config['master_lobby'] . '/' . $folder;
            if (is_dir($path)) {
                $this->addWatchRecursive($path);
            }
        }
        
        // Loop principal
        while (true) {
            $events = inotify_read($this->inotify);
            if ($events) {
                foreach ($events as $event) {
                    $this->handleFileChange($event);
                }
            }
            usleep(100000); // 100ms
        }
    }
    
    private function addWatchRecursive(string $path): void
    {
        $wd = inotify_add_watch($this->inotify, $path, 
            IN_MODIFY | IN_CREATE | IN_DELETE | IN_MOVED_FROM | IN_MOVED_TO | IN_CLOSE_WRITE);
        $this->watches[$wd] = $path;
        
        // Adiciona watches para subdiretórios
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $wd = inotify_add_watch($this->inotify, $file->getPathname(),
                    IN_MODIFY | IN_CREATE | IN_DELETE | IN_MOVED_FROM | IN_MOVED_TO | IN_CLOSE_WRITE);
                $this->watches[$wd] = $file->getPathname();
            }
        }
    }
    
    private function handleFileChange(array $event): void
    {
        $path = $this->watches[$event['wd']] ?? '';
        $file = $path . '/' . $event['name'];
        
        // Ignora padrões excluídos
        foreach ($this->config['exclude_patterns'] as $pattern) {
            if (fnmatch($pattern, $event['name'])) {
                return;
            }
        }
        
        $relativePath = str_replace($this->config['master_lobby'] . '/', '', $file);
        
        if ($event['mask'] & IN_DELETE) {
            $this->log("🗑️  Arquivo removido: $relativePath");
            $this->deleteFromLobbys($relativePath);
        } elseif ($event['mask'] & (IN_CREATE | IN_MODIFY | IN_CLOSE_WRITE)) {
            $this->log("📝 Arquivo modificado: $relativePath");
            $this->syncFile($relativePath);
        }
    }
    
    private function startPollingWatch(): void
    {
        $this->log("\n👁️  Modo Polling ativado (verificação a cada {$this->config['polling_interval']}s)");
        $this->log("   Monitorando mudanças...\n");
        
        $lastState = $this->scanDirectory($this->config['master_lobby']);
        
        while (true) {
            sleep($this->config['polling_interval']);
            
            $currentState = $this->scanDirectory($this->config['master_lobby']);
            $changes = $this->detectChanges($lastState, $currentState);
            
            foreach ($changes['added'] as $file) {
                $this->log("➕ Novo arquivo: $file");
                $this->syncFile($file);
            }
            
            foreach ($changes['modified'] as $file) {
                $this->log("📝 Arquivo modificado: $file");
                $this->syncFile($file);
            }
            
            foreach ($changes['deleted'] as $file) {
                $this->log("🗑️  Arquivo removido: $file");
                $this->deleteFromLobbys($file);
            }
            
            $lastState = $currentState;
        }
    }
    
    private function scanDirectory(string $dir): array
    {
        $result = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $path = str_replace($dir . '/', '', $file->getPathname());
                
                // Verifica exclusões
                $excluded = false;
                foreach ($this->config['exclude_patterns'] as $pattern) {
                    if (fnmatch($pattern, basename($path))) {
                        $excluded = true;
                        break;
                    }
                }
                
                if (!$excluded) {
                    $result[$path] = [
                        'size' => $file->getSize(),
                        'mtime' => $file->getMTime()
                    ];
                }
            }
        }
        
        return $result;
    }
    
    private function detectChanges(array $old, array $new): array
    {
        $changes = [
            'added' => [],
            'modified' => [],
            'deleted' => []
        ];
        
        // Arquivos novos ou modificados
        foreach ($new as $file => $info) {
            if (!isset($old[$file])) {
                $changes['added'][] = $file;
            } elseif ($old[$file]['mtime'] != $info['mtime'] || $old[$file]['size'] != $info['size']) {
                $changes['modified'][] = $file;
            }
        }
        
        // Arquivos deletados
        foreach ($old as $file => $info) {
            if (!isset($new[$file])) {
                $changes['deleted'][] = $file;
            }
        }
        
        return $changes;
    }
    
    private function syncAll(): void
    {
        foreach ($this->config['watch_folders'] as $folder) {
            $sourcePath = $this->config['master_lobby'] . '/' . $folder;
            if (!is_dir($sourcePath)) {
                continue;
            }
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace($this->config['master_lobby'] . '/', '', $file->getPathname());
                    $this->syncFile($relativePath);
                }
            }
        }
        
        $this->log("✅ Sincronização inicial concluída!");
    }
    
    private function syncFile(string $relativePath): void
    {
        $sourcePath = $this->config['master_lobby'] . '/' . $relativePath;
        
        if (!file_exists($sourcePath)) {
            return;
        }
        
        // Verifica se precisa de sincronização parcial
        $partialRule = $this->getPartialSyncRule($relativePath);
        
        foreach ($this->config['lobbys'] as $lobby) {
            $destPath = $lobby . '/' . $relativePath;
            
            // Cria diretório se necessário
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            
            if ($partialRule) {
                $this->syncPartial($sourcePath, $destPath, $partialRule);
            } else {
                // Cópia completa do arquivo
                if (@copy($sourcePath, $destPath)) {
                    $this->log("   ✓ Copiado para: $lobby");
                } else {
                    $this->log("   ✗ Erro ao copiar para: $lobby");
                }
            }
        }
    }
    
    private function getPartialSyncRule(string $file): ?array
    {
        $filename = basename($file);
        
        foreach ($this->partialSyncRules as $rule) {
            if (fnmatch($rule['file_pattern'], $filename)) {
                return $rule;
            }
        }
        
        return null;
    }
    
    private function syncPartial(string $source, string $dest, array $rule): void
    {
        if ($rule['sync_mode'] === 'lines') {
            $this->syncLines($source, $dest, $rule['sync_lines']);
        } elseif ($rule['sync_mode'] === 'sections') {
            $this->syncSections($source, $dest, $rule['sync_sections']);
        }
    }
    
    private function syncLines(string $source, string $dest, array $patterns): void
    {
        if (!file_exists($dest)) {
            // Se o arquivo não existe no destino, copia completo
            copy($source, $dest);
            return;
        }
        
        $sourceLines = file($source, FILE_IGNORE_NEW_LINES);
        $destLines = file($dest, FILE_IGNORE_NEW_LINES);
        
        // Atualiza apenas as linhas que correspondem aos padrões
        foreach ($sourceLines as $i => $sourceLine) {
            foreach ($patterns as $pattern) {
                if (strpos($sourceLine, $pattern) === 0) {
                    // Encontra e atualiza a linha correspondente no destino
                    $updated = false;
                    foreach ($destLines as $j => $destLine) {
                        if (strpos($destLine, $pattern) === 0) {
                            $destLines[$j] = $sourceLine;
                            $updated = true;
                            break;
                        }
                    }
                    
                    // Se não encontrou, adiciona a linha
                    if (!$updated) {
                        $destLines[] = $sourceLine;
                    }
                }
            }
        }
        
        // Salva o arquivo atualizado
        file_put_contents($dest, implode("\n", $destLines));
        $this->log("   ✓ Sincronização parcial (linhas): " . basename($dest));
    }
    
    private function syncSections(string $source, string $dest, array $sections): void
    {
        // Implementação simplificada para YAML/configs com seções
        $sourceContent = file_get_contents($source);
        $destContent = file_exists($dest) ? file_get_contents($dest) : '';
        
        foreach ($sections as $section) {
            // Extrai a seção do arquivo fonte
            $pattern = '/^' . preg_quote($section, '/') . '.*?(?=^\w|\z)/ms';
            if (preg_match($pattern, $sourceContent, $matches)) {
                $sectionContent = $matches[0];
                
                // Substitui ou adiciona a seção no destino
                if (preg_match($pattern, $destContent)) {
                    $destContent = preg_replace($pattern, $sectionContent, $destContent);
                } else {
                    $destContent .= "\n" . $sectionContent;
                }
            }
        }
        
        file_put_contents($dest, $destContent);
        $this->log("   ✓ Sincronização parcial (seções): " . basename($dest));
    }
    
    private function deleteFromLobbys(string $relativePath): void
    {
        foreach ($this->config['lobbys'] as $lobby) {
            $path = $lobby . '/' . $relativePath;
            if (file_exists($path)) {
                if (@unlink($path)) {
                    $this->log("   ✓ Removido de: $lobby");
                }
            }
        }
    }
    
    private function log(string $message): void
    {
        echo "[" . date('H:i:s') . "] " . $message . PHP_EOL;
    }
}

// Tratamento de sinais para shutdown gracioso
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function() {
        echo "\n\n🛑 Encerrando Lobby Sync...\n";
        exit(0);
    });
    pcntl_signal(SIGTERM, function() {
        echo "\n\n🛑 Encerrando Lobby Sync...\n";
        exit(0);
    });
}

// Inicia o sistema
$sync = new LobbySync();
$sync->start();
