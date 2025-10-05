Sincronizador de Pastas em PHP (Linux)

Este projeto fornece um script CLI em PHP que sincroniza uma pasta de origem para uma pasta de destino em sistemas Linux. Suporta espelhamento, exclusões, dry-run, checksum, modo contínuo (watch) e preservação de horários de modificação.

### Requisitos

- PHP 8.1+ com extensões padrão
- Sistema de arquivos acessível de leitura (origem) e escrita (destino)

### Instalação

1. Torne o script executável:

```bash
chmod +x ./sync.php
```

2. (Opcional) Adicione ao PATH ou crie um link simbólico:

```bash
sudo ln -s "$(pwd)/sync.php" /usr/local/bin/php-sync
```

### Uso Básico

```bash
php sync.php --source /caminho/origem --dest /caminho/destino [opções]
```

#### Opções

- `--mirror`: Espelha destino (remove arquivos que não existem na origem)
- `--exclude Padrão`: Exclui caminhos (pode repetir). Usa `fnmatch` sobre caminho relativo
- `--exclude-from ARQ`: Lê padrões de exclusão de arquivo (um por linha, `#` para comentários)
- `--dry-run`: Não altera nada; apenas mostra o que faria
- `--checksum`: Compara por checksum (sha1) ao invés de tamanho+mtime
- `--watch`: Laço contínuo; repete a sincronização periodicamente
- `--interval N`: Intervalo (segundos) entre execuções quando em `--watch` (padrão: 3)
- `--preserve-times`: Preserva `mtime` do arquivo de origem ao copiar
- `--verbose`: Saída detalhada (padrão)
- `--quiet`: Saída mínima
- `--no-lock`: Desabilita trava de execução (flock)
- `--follow-symlinks`: Segue symlinks (por padrão são ignorados)
- `--help`: Mostra ajuda

### Exemplos

- Simular espelhamento com exclusões:

```bash
php sync.php --source /data/in --dest /data/out --mirror --exclude "*.tmp" --exclude "cache/" --dry-run
```

- Copiar usando verificação por checksum (mais confiável, porém mais lenta):

```bash
php sync.php --source ./src --dest ./backup --checksum
```

- Rodar continuamente a cada 5s:

```bash
php sync.php --source /a --dest /b --watch --interval 5
```

### Múltiplas pastas (config JSON)

Para sincronizar várias origens para vários destinos, use `--config` com um arquivo JSON:

```bash
php sync.php --config ./config.json
```

Modelo de configuração: veja `config.sample.json`.

No JSON, a seção `global` define opções padrão e a lista `jobs` contém os trabalhos. Cada job pode ter `sources` (origens) e `destinations` (destinos) e opcionalmente sobrescrever opções globais como `mirror`, `interval`, `exclude`, etc. O modo `watch` também é suportado por configuração.

### Exclusões

- `--exclude` aceita padrões tipo shell (`fnmatch`), comparados contra o caminho relativo. Exemplos:
  - `*.tmp`, `*.log`, `node_modules/`, `cache/`, `build/*`
- `--exclude-from arquivo.txt` permite listar padrões (um por linha). Linhas iniciadas por `#` são ignoradas.

### Symlinks

- Por padrão, symlinks na origem são ignorados. Use `--follow-symlinks` para recriá-los no destino com o mesmo alvo.

### Notas de Segurança/Desempenho

- `--mirror` pode remover arquivos no destino; teste antes com `--dry-run`.
- `--checksum` lê e calcula hash de todos os arquivos (mais CPU/IO). Use apenas quando necessário.
- O script utiliza `flock` para evitar concorrência entre instâncias para o mesmo par origem/destino (desative com `--no-lock`).

### systemd (agendamento)

Exemplo de serviço `php-folder-sync.service` e timer `php-folder-sync.timer` (editar caminhos conforme necessário):

```ini
[Unit]
Description=PHP Folder Sync
After=network.target

[Service]
Type=oneshot
ExecStart=/usr/bin/php /opt/php-folder-sync/sync.php --source /data/in --dest /data/out --mirror --exclude-from /opt/php-folder-sync/excludes.txt
Nice=10
IOSchedulingClass=best-effort
IOSchedulingPriority=7

[Install]
WantedBy=multi-user.target
```

```ini
[Unit]
Description=Run PHP Folder Sync every 5 minutes

[Timer]
OnBootSec=2min
OnUnitActiveSec=5min
Persistent=true

[Install]
WantedBy=timers.target
```

Instalação do systemd:

```bash
sudo cp php-folder-sync.service /etc/systemd/system/
sudo cp php-folder-sync.timer /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now php-folder-sync.timer
```

Logs:

```bash
journalctl -u php-folder-sync.service -f
```

### Licença

MIT

## Plataforma Web (opcional)

Para facilitar a gestão do `config.json` e executar sincronizações via navegador:

1. Inicie o servidor embutido do PHP na pasta `web/`:

```bash
php -S 0.0.0.0:8080 -t web
```

2. Acesse `http://localhost:8080`.

Funcionalidades:
- Editor do `config.json` com salvamento
- Botão para executar todos os jobs (uma vez)
- Botão para executar job específico (uma vez)

Observações:
- A UI roda o CLI (`sync.php --config ... --once`) e mostra a saída.
- Para produção, proteja com autenticação, HTTPS e permissões adequadas de arquivo.


