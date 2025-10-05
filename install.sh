#!/bin/bash
# Instalador automático do Lobby Sync para Linux

echo "============================================"
echo "   🎮 INSTALADOR DO LOBBY SYNC"
echo "   Sistema de Sincronização de Lobbys"
echo "============================================"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verifica se está rodando como root
if [[ $EUID -eq 0 ]]; then
   echo -e "${RED}❌ Este script não deve ser executado como root!${NC}"
   echo "   Execute como usuário normal: ./install.sh"
   exit 1
fi

# Verifica PHP
echo "📋 Verificando requisitos..."
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP não encontrado!${NC}"
    echo "   Instale com: sudo apt install php-cli"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✅ PHP encontrado: $PHP_VERSION${NC}"

# Verifica/instala inotify
echo ""
echo "📋 Verificando inotify-tools..."
if ! command -v inotifywait &> /dev/null; then
    echo -e "${YELLOW}⚠️  inotify-tools não encontrado${NC}"
    echo "   Instalando inotify-tools..."
    sudo apt update && sudo apt install -y inotify-tools
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ inotify-tools instalado com sucesso!${NC}"
    else
        echo -e "${YELLOW}⚠️  Não foi possível instalar inotify-tools${NC}"
        echo "   O sistema funcionará em modo polling (menos eficiente)"
    fi
else
    echo -e "${GREEN}✅ inotify-tools já instalado${NC}"
fi

# Verifica extensão PHP inotify (opcional)
echo ""
echo "📋 Verificando extensão PHP inotify..."
if php -m | grep -q inotify; then
    echo -e "${GREEN}✅ Extensão PHP inotify encontrada${NC}"
else
    echo -e "${YELLOW}⚠️  Extensão PHP inotify não encontrada${NC}"
    echo "   Para melhor performance, instale com:"
    echo "   sudo pecl install inotify"
    echo "   O sistema funcionará sem ela, mas com performance reduzida"
fi

# Cria diretórios padrão
echo ""
echo "📁 Criando estrutura de diretórios..."
mkdir -p ~/lobby-sync
mkdir -p ~/lobby-sync/logs
mkdir -p ~/lobby-sync/web

# Copia arquivos
echo "📋 Copiando arquivos..."
cp lobby-sync.php ~/lobby-sync/
cp web/simple.php ~/lobby-sync/web/index.php
chmod +x ~/lobby-sync/lobby-sync.php

# Cria arquivo de configuração inicial
if [ ! -f ~/lobby-sync/lobby-config.json ]; then
    echo "📝 Criando configuração inicial..."
    cat > ~/lobby-sync/lobby-config.json << 'EOF'
{
    "master_lobby": "/home/$USER/lobbys/master",
    "lobbys": [
        "/home/$USER/lobbys/lobby1",
        "/home/$USER/lobbys/lobby2",
        "/home/$USER/lobbys/lobby3",
        "/home/$USER/lobbys/lobby4"
    ],
    "watch_folders": [
        "plugins",
        "configs",
        "scripts"
    ],
    "exclude_patterns": [
        "*.tmp",
        "*.log",
        "*.lock",
        ".git",
        "cache/*"
    ],
    "polling_interval": 2,
    "auto_start": true
}
EOF
    # Substitui $USER pelo usuário atual
    sed -i "s/\$USER/$USER/g" ~/lobby-sync/lobby-config.json
    echo -e "${GREEN}✅ Configuração criada${NC}"
fi

# Cria script de inicialização
echo "📝 Criando scripts auxiliares..."
cat > ~/lobby-sync/start.sh << 'EOF'
#!/bin/bash
cd "$(dirname "$0")"
echo "🚀 Iniciando Lobby Sync..."
nohup php lobby-sync.php > logs/lobby-sync.log 2>&1 &
echo $! > lobby-sync.pid
echo "✅ Lobby Sync iniciado! PID: $(cat lobby-sync.pid)"
echo "📋 Logs em: logs/lobby-sync.log"
EOF
chmod +x ~/lobby-sync/start.sh

cat > ~/lobby-sync/stop.sh << 'EOF'
#!/bin/bash
cd "$(dirname "$0")"
if [ -f lobby-sync.pid ]; then
    PID=$(cat lobby-sync.pid)
    if ps -p $PID > /dev/null; then
        echo "🛑 Parando Lobby Sync (PID: $PID)..."
        kill $PID
        rm lobby-sync.pid
        echo "✅ Lobby Sync parado!"
    else
        echo "⚠️  Processo não está rodando"
        rm lobby-sync.pid
    fi
else
    echo "⚠️  Arquivo PID não encontrado"
    pkill -f "php.*lobby-sync.php"
fi
EOF
chmod +x ~/lobby-sync/stop.sh

cat > ~/lobby-sync/status.sh << 'EOF'
#!/bin/bash
cd "$(dirname "$0")"
if [ -f lobby-sync.pid ]; then
    PID=$(cat lobby-sync.pid)
    if ps -p $PID > /dev/null; then
        echo "🟢 Lobby Sync está RODANDO (PID: $PID)"
        echo ""
        echo "📋 Últimas linhas do log:"
        tail -n 10 logs/lobby-sync.log
    else
        echo "🔴 Lobby Sync está PARADO"
    fi
else
    echo "🔴 Lobby Sync não está rodando"
fi
EOF
chmod +x ~/lobby-sync/status.sh

# Cria serviço systemd (opcional)
echo ""
echo "📋 Deseja instalar como serviço do sistema (systemd)?"
echo "   Isso permitirá iniciar automaticamente no boot"
read -p "   Instalar serviço? (s/N): " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Ss]$ ]]; then
    cat > /tmp/lobby-sync.service << EOF
[Unit]
Description=Lobby Sync - Sistema de Sincronização de Lobbys
After=network.target

[Service]
Type=simple
User=$USER
WorkingDirectory=/home/$USER/lobby-sync
ExecStart=/usr/bin/php /home/$USER/lobby-sync/lobby-sync.php
Restart=on-failure
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
    
    sudo mv /tmp/lobby-sync.service /etc/systemd/system/
    sudo systemctl daemon-reload
    sudo systemctl enable lobby-sync
    echo -e "${GREEN}✅ Serviço instalado!${NC}"
    echo "   Comandos úteis:"
    echo "   - Iniciar: sudo systemctl start lobby-sync"
    echo "   - Parar: sudo systemctl stop lobby-sync"
    echo "   - Status: sudo systemctl status lobby-sync"
    echo "   - Logs: journalctl -u lobby-sync -f"
fi

# Cria atalho para interface web
cat > ~/lobby-sync/web-ui.sh << 'EOF'
#!/bin/bash
echo "🌐 Iniciando interface web..."
echo "   Acesse: http://localhost:8888"
echo "   Pressione Ctrl+C para parar"
cd "$(dirname "$0")/web"
php -S localhost:8888
EOF
chmod +x ~/lobby-sync/web-ui.sh

# Finalização
echo ""
echo "============================================"
echo -e "${GREEN}✅ INSTALAÇÃO CONCLUÍDA!${NC}"
echo "============================================"
echo ""
echo "📁 Instalado em: ~/lobby-sync"
echo ""
echo "🎮 COMANDOS DISPONÍVEIS:"
echo "   cd ~/lobby-sync"
echo "   ./start.sh    - Iniciar sincronização"
echo "   ./stop.sh     - Parar sincronização"
echo "   ./status.sh   - Ver status"
echo "   ./web-ui.sh   - Abrir interface web"
echo ""
echo "⚙️  CONFIGURAÇÃO:"
echo "   Edite: ~/lobby-sync/lobby-config.json"
echo "   Ou use a interface web: ./web-ui.sh"
echo ""
echo "📚 PRÓXIMOS PASSOS:"
echo "   1. Configure seus lobbys em lobby-config.json"
echo "   2. Execute ./start.sh para iniciar"
echo "   3. Ou use ./web-ui.sh para configurar via navegador"
echo ""
echo "============================================"
