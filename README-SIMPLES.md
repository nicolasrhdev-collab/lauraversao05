# 🎮 Lobby Sync - Sistema de Sincronização de Lobbys

Sistema **SUPER SIMPLES** para manter múltiplos lobbys de Minecraft sincronizados automaticamente!

## 🚀 O que faz?

- **Copia arquivos automaticamente** de uma pasta principal para todos os lobbys
- **Monitora mudanças em tempo real** (quando você salva um arquivo, ele já copia)
- **Suporta sincronização parcial** (copia só algumas linhas de configs específicas)
- **Interface web simples** para gerenciar tudo pelo navegador

## 📦 Instalação Rápida (Linux)

### Opção 1: Instalação Automática (RECOMENDADO)

```bash
# Baixa o projeto
git clone https://github.com/nicolasrhdev-collab/lauralegalteste01.git
cd lauralegalteste01

# Torna o instalador executável
chmod +x install.sh

# Executa o instalador
./install.sh
```

O instalador vai:
- ✅ Verificar se tem PHP instalado
- ✅ Instalar ferramentas necessárias
- ✅ Criar a estrutura de pastas
- ✅ Configurar tudo automaticamente

### Opção 2: Instalação Manual

```bash
# Instala PHP se não tiver
sudo apt update
sudo apt install php-cli

# Baixa o projeto
git clone https://github.com/nicolasrhdev-collab/lauralegalteste01.git
cd lauralegalteste01

# Torna executável
chmod +x lobby-sync.php
```

## 🎮 Como Usar (SUPER FÁCIL!)

### 1️⃣ Configurar pelo Navegador (Mais Fácil)

```bash
# Inicia a interface web
php -S localhost:8888 -t web

# Abre o navegador em: http://localhost:8888/simple.php
```

Na interface você pode:
- 📝 Configurar a pasta principal e os lobbys
- ▶️ Iniciar/Parar sincronização com 1 clique
- 📋 Ver logs em tempo real
- 🔄 Sincronizar manualmente quando quiser

### 2️⃣ Configurar por Arquivo (Avançado)

Edite o arquivo `lobby-config.json`:

```json
{
    "master_lobby": "/caminho/para/lobby_principal",
    "lobbys": [
        "/caminho/para/lobby1",
        "/caminho/para/lobby2",
        "/caminho/para/lobby3",
        "/caminho/para/lobby4"
    ],
    "watch_folders": [
        "plugins",
        "configs"
    ]
}
```

### 3️⃣ Iniciar a Sincronização

```bash
# Inicia o sistema
php lobby-sync.php

# Ou se instalou com o script:
cd ~/lobby-sync
./start.sh
```

## 🎯 Exemplos Práticos

### Exemplo 1: Sincronizar Plugins

1. Coloque um plugin novo na pasta: `/lobby_principal/plugins/`
2. **AUTOMATICAMENTE** é copiado para:
   - `/lobby1/plugins/`
   - `/lobby2/plugins/`
   - `/lobby3/plugins/`
   - `/lobby4/plugins/`

### Exemplo 2: Atualizar Config

1. Edite um arquivo em: `/lobby_principal/configs/config.yml`
2. **INSTANTANEAMENTE** atualiza em todos os lobbys!

### Exemplo 3: Sincronização Parcial

Para arquivos que não podem ser copiados inteiros (como `server.properties`), configure em `partial-sync.json`:

```json
{
    "rules": [
        {
            "file_pattern": "server.properties",
            "sync_mode": "lines",
            "sync_lines": [
                "max-players=",
                "difficulty="
            ]
        }
    ]
}
```

Isso copia **APENAS** as linhas especificadas, mantendo o resto único em cada lobby!

## 🛠️ Comandos Úteis

Se instalou com o script automático:

```bash
cd ~/lobby-sync

# Iniciar
./start.sh

# Parar
./stop.sh

# Ver status
./status.sh

# Abrir interface web
./web-ui.sh
```

## ❓ Perguntas Frequentes

### "Como faço para adicionar mais um lobby?"

1. Abra a interface web (`./web-ui.sh`)
2. Na caixa "Lobbys para Sincronizar", adicione uma nova linha
3. Clique em "SALVAR CONFIGURAÇÃO"
4. Pronto! 🎉

### "Posso excluir alguns arquivos da sincronização?"

Sim! Na interface web, no campo "Arquivos para Ignorar", adicione:
- `*.log` para ignorar logs
- `*.tmp` para ignorar temporários
- `cache/*` para ignorar pasta cache

### "E se eu quiser sincronizar só quando eu mandar?"

Não use o modo automático. Use apenas o botão "SINCRONIZAR AGORA" na interface web quando quiser.

### "Funciona com quantos lobbys?"

Quantos você quiser! Já testado com 10+ lobbys simultâneos.

## 🚨 Problemas Comuns

### "Não está copiando automaticamente"

- Verifique se o sistema está rodando: `./status.sh`
- Veja os logs: `tail -f ~/lobby-sync/logs/lobby-sync.log`

### "Diz que PHP não está instalado"

```bash
# Ubuntu/Debian
sudo apt install php-cli

# CentOS/RHEL
sudo yum install php-cli
```

### "Quero que inicie automaticamente quando ligar o servidor"

O instalador pergunta se quer instalar como serviço. Se disse sim:

```bash
# Ativar início automático
sudo systemctl enable lobby-sync

# Iniciar agora
sudo systemctl start lobby-sync
```

## 📞 Suporte

- **GitHub**: [https://github.com/nicolasrhdev-collab/lauralegalteste01](https://github.com/nicolasrhdev-collab/lauralegalteste01)
- **Issues**: Abra uma issue no GitHub se tiver problemas

## 🎉 É isso!

Super simples! Qualquer dúvida, só perguntar. O sistema foi feito para ser o mais fácil possível de usar, mesmo para quem não entende de programação.

**Dica**: Use a interface web! É muito mais fácil que editar arquivos. 😉
