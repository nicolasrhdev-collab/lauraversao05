# 🚀 COMO ATIVAR NO LINUX - GUIA COMPLETO

## 1️⃣ CLONAR O PROJETO

```bash
# Entrar na pasta home
cd ~

# Clonar o repositório
git clone https://github.com/nicolasrhdev-collab/lauraversao05.git
cd lauraversao05
```

## 2️⃣ INSTALAÇÃO AUTOMÁTICA (RECOMENDADO)

```bash
# Tornar o instalador executável
chmod +x install.sh

# Executar o instalador
./install.sh
```

O instalador vai:
- ✅ Verificar se tem PHP
- ✅ Instalar inotify-tools (se não tiver)
- ✅ Criar estrutura de pastas
- ✅ Configurar scripts de controle

## 3️⃣ CONFIGURAR SEUS LOBBYS

### Opção A: Editar arquivo direto

```bash
# Editar configuração
nano ~/lobby-sync/lobby-config.json
```

Mude para suas pastas reais:
```json
{
  "master_lobby": "/home/seu_usuario/servidor/lobby_master",
  "lobbys": [
    "/home/seu_usuario/servidor/lobby1",
    "/home/seu_usuario/servidor/lobby2",
    "/home/seu_usuario/servidor/lobby3",
    "/home/seu_usuario/servidor/lobby4"
  ],
  "watch_folders": [
    "plugins",
    "configs",
    "scripts"
  ]
}
```

### Opção B: Usar interface web

```bash
# Iniciar interface web
cd ~/lobby-sync
./web-ui.sh

# Ou manualmente:
php -S 0.0.0.0:8080 -t web
```

Acesse no navegador:
- Interface simples: http://SEU_IP:8080/simple.php
- Interface profissional: http://SEU_IP:8080/professional.php

## 4️⃣ INICIAR O SISTEMA

### Método 1: Scripts prontos (MAIS FÁCIL)

```bash
cd ~/lobby-sync

# Iniciar
./start.sh

# Verificar status
./status.sh

# Parar
./stop.sh
```

### Método 2: Comando direto

```bash
# Iniciar em background
nohup php ~/lobby-sync/lobby-sync.php > ~/lobby-sync/logs/sync.log 2>&1 &

# Ver logs em tempo real
tail -f ~/lobby-sync/logs/sync.log

# Parar
pkill -f "php.*lobby-sync.php"
```

### Método 3: Systemd (iniciar com o sistema)

```bash
# Criar serviço
sudo nano /etc/systemd/system/lobby-sync.service
```

Cole isso (ajuste o usuário):
```ini
[Unit]
Description=Lobby Sync - Sistema de Sincronização
After=network.target

[Service]
Type=simple
User=SEU_USUARIO
WorkingDirectory=/home/SEU_USUARIO/lobby-sync
ExecStart=/usr/bin/php /home/SEU_USUARIO/lobby-sync/lobby-sync.php
Restart=on-failure
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Ativar:
```bash
# Recarregar systemd
sudo systemctl daemon-reload

# Habilitar início automático
sudo systemctl enable lobby-sync

# Iniciar agora
sudo systemctl start lobby-sync

# Ver status
sudo systemctl status lobby-sync

# Ver logs
journalctl -u lobby-sync -f
```

## 5️⃣ TESTAR SE ESTÁ FUNCIONANDO

```bash
# 1. Criar um arquivo de teste no lobby master
echo "teste" > /caminho/lobby_master/plugins/teste.txt

# 2. Verificar se copiou para os outros lobbys
ls -la /caminho/lobby1/plugins/teste.txt
ls -la /caminho/lobby2/plugins/teste.txt
ls -la /caminho/lobby3/plugins/teste.txt
ls -la /caminho/lobby4/plugins/teste.txt

# Todos devem ter o arquivo!
```

## 6️⃣ COMANDOS ÚTEIS DO DIA A DIA

```bash
# Ver status
~/lobby-sync/status.sh

# Ver logs em tempo real
tail -f ~/lobby-sync/logs/sync.log

# Reiniciar
~/lobby-sync/stop.sh && ~/lobby-sync/start.sh

# Interface web
~/lobby-sync/web-ui.sh
```

## 🔧 RESOLUÇÃO DE PROBLEMAS

### "PHP não encontrado"
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php-cli

# CentOS/RHEL
sudo yum install php-cli

# Arch
sudo pacman -S php
```

### "Permission denied"
```bash
# Dar permissão de execução
chmod +x ~/lobby-sync/*.sh
chmod +x ~/lobby-sync/lobby-sync.php
```

### "Não está sincronizando"
```bash
# Verificar se está rodando
ps aux | grep lobby-sync

# Ver erros nos logs
tail -100 ~/lobby-sync/logs/sync.log

# Verificar permissões das pastas
ls -la /caminho/para/lobbys/
```

### "inotify não funciona"
```bash
# Instalar inotify-tools
sudo apt install inotify-tools

# Verificar limite de watches
cat /proc/sys/fs/inotify/max_user_watches

# Aumentar se necessário
echo fs.inotify.max_user_watches=524288 | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

## 📊 MONITORAMENTO

### Ver estatísticas em tempo real
```bash
# Terminal 1 - Ver logs
tail -f ~/lobby-sync/logs/sync.log

# Terminal 2 - Ver processos
watch -n 1 'ps aux | grep lobby-sync'

# Terminal 3 - Ver uso de recursos
htop
```

### Dashboard Web
Acesse: http://SEU_IP:8080/professional.php
- Status em tempo real
- Logs coloridos
- Controles start/stop
- Configuração visual

## ✅ CHECKLIST RÁPIDO

- [ ] PHP instalado (`php -v`)
- [ ] Repositório clonado
- [ ] Instalador executado (`./install.sh`)
- [ ] Configuração ajustada (`lobby-config.json`)
- [ ] Sistema iniciado (`./start.sh`)
- [ ] Teste feito (criar arquivo e ver se copia)

## 🎯 EXEMPLO COMPLETO

```bash
# Do zero ao funcionando em 5 comandos:
cd ~
git clone https://github.com/nicolasrhdev-collab/lauraversao05.git
cd lauraversao05
chmod +x install.sh && ./install.sh
cd ~/lobby-sync
./start.sh

# Pronto! Sistema rodando!
```

## 💡 DICA IMPORTANTE

Para servidores de produção, use sempre o **systemd** para garantir que o sistema:
- Inicie automaticamente no boot
- Reinicie em caso de erro
- Tenha logs centralizados

---

**Dúvidas?** Abra uma issue em: https://github.com/nicolasrhdev-collab/lauraversao05/issues
