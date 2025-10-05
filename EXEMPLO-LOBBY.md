# 🎮 EXEMPLO PRÁTICO - Como Funciona com Lobbys de Minecraft

## 📁 Estrutura de Pastas do Servidor

Vou mostrar exatamente como ficaria com 4 lobbys de Minecraft:

```
/home/servidor/
│
├── lobby_master/          👑 PASTA PRINCIPAL (você edita aqui)
│   ├── plugins/
│   │   ├── EssentialsX.jar
│   │   ├── LuckPerms.jar
│   │   ├── WorldEdit.jar
│   │   └── config/
│   │       ├── EssentialsX/
│   │       └── LuckPerms/
│   ├── configs/
│   │   ├── server.properties
│   │   ├── bukkit.yml
│   │   └── spigot.yml
│   └── scripts/
│       └── restart.sh
│
├── lobby1/                🎮 LOBBY 1 (recebe cópias)
│   ├── plugins/          ← copiado automaticamente
│   ├── configs/          ← copiado automaticamente
│   └── scripts/          ← copiado automaticamente
│
├── lobby2/                🎮 LOBBY 2 (recebe cópias)
│   ├── plugins/          ← copiado automaticamente
│   ├── configs/          ← copiado automaticamente
│   └── scripts/          ← copiado automaticamente
│
├── lobby3/                🎮 LOBBY 3 (recebe cópias)
│   └── (mesma estrutura)
│
└── lobby4/                🎮 LOBBY 4 (recebe cópias)
    └── (mesma estrutura)
```

## 🚀 Como Funciona na Prática

### Exemplo 1: Adicionando um Plugin Novo

**VOCÊ FAZ:**
```bash
# Coloca o plugin novo no lobby principal
cp ViaVersion.jar /home/servidor/lobby_master/plugins/
```

**SISTEMA FAZ AUTOMATICAMENTE:**
```
[12:34:56] 📝 Arquivo modificado: plugins/ViaVersion.jar
[12:34:56]    ✓ Copiado para: /home/servidor/lobby1
[12:34:56]    ✓ Copiado para: /home/servidor/lobby2
[12:34:56]    ✓ Copiado para: /home/servidor/lobby3
[12:34:56]    ✓ Copiado para: /home/servidor/lobby4
```

### Exemplo 2: Editando Configuração

**VOCÊ FAZ:**
```bash
# Edita config do EssentialsX no lobby principal
nano /home/servidor/lobby_master/plugins/EssentialsX/config.yml

# Muda algo como:
# teleport-cooldown: 3
# para:
# teleport-cooldown: 5
```

**SISTEMA FAZ AUTOMATICAMENTE:**
```
[12:35:10] 📝 Arquivo modificado: plugins/EssentialsX/config.yml
[12:35:10]    ✓ Copiado para: /home/servidor/lobby1
[12:35:10]    ✓ Copiado para: /home/servidor/lobby2
[12:35:10]    ✓ Copiado para: /home/servidor/lobby3
[12:35:10]    ✓ Copiado para: /home/servidor/lobby4
```

### Exemplo 3: Sincronização Parcial (ESPECIAL!)

Para arquivos como `server.properties` onde cada lobby tem porta diferente:

**CONFIGURAÇÃO (partial-sync.json):**
```json
{
  "rules": [
    {
      "file_pattern": "server.properties",
      "sync_mode": "lines",
      "sync_lines": [
        "max-players=",
        "difficulty=",
        "gamemode=",
        "pvp=",
        "spawn-protection="
      ],
      "description": "Sincroniza apenas configs gerais, mantém porta única"
    }
  ]
}
```

**COMO FUNCIONA:**

**lobby_master/configs/server.properties:**
```properties
server-port=25565        ← NÃO COPIA
max-players=100          ← COPIA
difficulty=normal        ← COPIA
gamemode=survival        ← COPIA
```

**lobby1/configs/server.properties:**
```properties
server-port=25566        ← MANTÉM ÚNICO!
max-players=100          ← ATUALIZADO
difficulty=normal        ← ATUALIZADO
gamemode=survival        ← ATUALIZADO
```

**lobby2/configs/server.properties:**
```properties
server-port=25567        ← MANTÉM ÚNICO!
max-players=100          ← ATUALIZADO
difficulty=normal        ← ATUALIZADO
gamemode=survival        ← ATUALIZADO
```

## 🎯 Casos de Uso Reais

### 1. Atualização de Plugin em Massa
```bash
# Você atualiza o LuckPerms
rm /home/servidor/lobby_master/plugins/LuckPerms-5.3.jar
cp LuckPerms-5.4.jar /home/servidor/lobby_master/plugins/

# RESULTADO: Todos os 4 lobbys recebem a versão nova instantaneamente!
```

### 2. Mudança de Configuração Global
```yaml
# Edita lobby_master/plugins/EssentialsX/config.yml
# Muda kit inicial, comandos, permissões...

# RESULTADO: Mudanças aplicadas em TODOS os lobbys na hora!
```

### 3. Rollback Rápido
```bash
# Algo deu errado? Volta o arquivo antigo no master
cp backup/plugin-antigo.jar /home/servidor/lobby_master/plugins/

# RESULTADO: Todos os lobbys voltam para versão anterior!
```

## 📊 Arquivos que DEVEM ser Sincronizados

✅ **SINCRONIZAR SEMPRE:**
- `/plugins/*.jar` - Todos os plugins
- `/plugins/*/config.yml` - Configs dos plugins
- `/plugins/*/messages.yml` - Mensagens
- `/plugins/*/lang/` - Traduções
- Ranks e permissões (LuckPerms)
- Kits e warps (Essentials)

❌ **NÃO SINCRONIZAR (usar exclude):**
- `/plugins/*/data/` - Dados de jogadores
- `/world/` - Mapas (cada lobby pode ter mapa diferente)
- `/logs/` - Logs do servidor
- `*.db` ou `*.sqlite` - Bancos de dados locais
- `/cache/` - Cache temporário

⚠️ **SINCRONIZAÇÃO PARCIAL:**
- `server.properties` - Só algumas linhas
- `bukkit.yml` - Só configurações gerais
- `spigot.yml` - Só otimizações

## 🔧 Configuração para Este Exemplo

**lobby-config.json:**
```json
{
  "master_lobby": "/home/servidor/lobby_master",
  "lobbys": [
    "/home/servidor/lobby1",
    "/home/servidor/lobby2",
    "/home/servidor/lobby3",
    "/home/servidor/lobby4"
  ],
  "watch_folders": [
    "plugins",
    "configs",
    "scripts"
  ],
  "exclude_patterns": [
    "*.log",
    "*.db",
    "*.sqlite",
    "*/data/*",
    "*/cache/*",
    "*/playerdata/*",
    "world/*"
  ],
  "polling_interval": 2
}
```

## 💡 Dicas Importantes

### 1. Estrutura Inicial
```bash
# Cria as pastas
mkdir -p /home/servidor/{lobby_master,lobby1,lobby2,lobby3,lobby4}
mkdir -p /home/servidor/lobby_master/{plugins,configs,scripts}

# Primeira sincronização manual
php lobby-sync.php --once
```

### 2. Teste Antes
```bash
# Sempre teste mudanças importantes
# 1. Faça a mudança no lobby_master
# 2. Veja se sincronizou
# 3. Teste em UM lobby primeiro
# 4. Se funcionou, está pronto!
```

### 3. Backup
```bash
# Antes de mudanças grandes
tar -czf backup-lobbys.tar.gz lobby_master/
```

## 🎮 Comandos do Dia a Dia

```bash
# Ver status
./status.sh
# Output: 🟢 Lobby Sync está RODANDO (PID: 12345)

# Ver o que está sendo sincronizado
tail -f ~/lobby-sync/logs/lobby-sync.log

# Parar temporariamente (para manutenção)
./stop.sh

# Iniciar novamente
./start.sh
```

## 📺 Interface Web

Acesse `http://seuservidor:8888/simple.php` e veja:

- **Status**: 🟢 Ativo | 4 Lobbys | 3 Pastas Monitoradas
- **Botão SINCRONIZAR AGORA**: Força sincronização manual
- **Logs em Tempo Real**: Vê tudo que está acontecendo
- **Configuração Visual**: Adiciona/remove lobbys facilmente

## ✅ Resumo

1. **Você edita APENAS** em `/lobby_master/`
2. **Sistema copia AUTOMATICAMENTE** para todos os lobbys
3. **Funciona em TEMPO REAL** (salva = copia na hora)
4. **Configurações especiais** mantém valores únicos (porta, etc)
5. **Interface web** para gerenciar tudo facilmente

É literalmente isso! Simples e eficiente! 🚀
