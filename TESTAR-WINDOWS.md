# 🚀 COMO TESTAR NO WINDOWS (Super Simples!)

## Opção 1: Instalar PHP no Windows (5 minutos)

### 1️⃣ Baixar PHP
1. Acesse: https://windows.php.net/download/
2. Baixe: **PHP 8.2 VS16 x64 Non Thread Safe** (arquivo .zip)
3. Extraia para: `C:\php`

### 2️⃣ Adicionar ao PATH
1. Abra: Configurações → Sistema → Sobre → Configurações avançadas do sistema
2. Clique em: "Variáveis de Ambiente"
3. Em "Path", adicione: `C:\php`
4. Clique OK

### 3️⃣ Testar
```cmd
# Abra novo terminal (cmd ou PowerShell)
php -v

# Deve mostrar: PHP 8.2.x ...
```

## Opção 2: Usar XAMPP (Mais Fácil!)

1. Baixe XAMPP: https://www.apachefriends.org/
2. Instale (só precisa do PHP, pode desmarcar MySQL/Apache)
3. PHP estará em: `C:\xampp\php\php.exe`

## 🎮 TESTANDO O SISTEMA

### 1. Criar Estrutura de Teste

```powershell
# No PowerShell, crie pastas de teste
cd C:\Users\nicol\Documents\dev\pessoal\frame

# Criar pastas de exemplo
mkdir teste_lobbys
mkdir teste_lobbys\lobby_master
mkdir teste_lobbys\lobby_master\plugins
mkdir teste_lobbys\lobby_master\configs
mkdir teste_lobbys\lobby1
mkdir teste_lobbys\lobby2
mkdir teste_lobbys\lobby3
mkdir teste_lobbys\lobby4
```

### 2. Configurar para Teste

Crie `teste-config.json`:
```json
{
  "master_lobby": "C:/Users/nicol/Documents/dev/pessoal/frame/teste_lobbys/lobby_master",
  "lobbys": [
    "C:/Users/nicol/Documents/dev/pessoal/frame/teste_lobbys/lobby1",
    "C:/Users/nicol/Documents/dev/pessoal/frame/teste_lobbys/lobby2",
    "C:/Users/nicol/Documents/dev/pessoal/frame/teste_lobbys/lobby3",
    "C:/Users/nicol/Documents/dev/pessoal/frame/teste_lobbys/lobby4"
  ],
  "watch_folders": [
    "plugins",
    "configs"
  ],
  "exclude_patterns": [
    "*.tmp",
    "*.log"
  ],
  "polling_interval": 2
}
```

### 3. Testar Interface Web (MAIS FÁCIL!)

```powershell
# Inicia servidor web local
php -S localhost:8080 -t web

# Abra no navegador:
# http://localhost:8080/simple.php
```

### 4. Testar Sincronização Manual

```powershell
# Teste simples - cria um arquivo no master
echo "teste" > teste_lobbys\lobby_master\plugins\teste.txt

# Roda sincronização uma vez
php sync.php --config teste-config.json --once

# Verifica se copiou
dir teste_lobbys\lobby1\plugins\
dir teste_lobbys\lobby2\plugins\
# Deve mostrar teste.txt em todos!
```

### 5. Testar Modo Watch (Automático)

```powershell
# Inicia monitoramento
php lobby-sync.php

# Em outro terminal, crie/edite arquivos em lobby_master
# Veja eles sendo copiados automaticamente!
```

## 🎯 TESTE RÁPIDO SEM INSTALAR NADA!

Se não quiser instalar PHP agora, use um simulador online:

1. Acesse: https://onecompiler.com/php
2. Cole o código do `lobby-sync.php`
3. Veja funcionando!

## 📝 Script de Teste Simples

Crie `teste-simples.php`:
```php
<?php
echo "✅ PHP funcionando!\n";
echo "📁 Pasta atual: " . getcwd() . "\n";
echo "🎮 Pronto para sincronizar lobbys!\n";

// Teste básico de cópia
$origem = "teste_origem.txt";
$destino = "teste_destino.txt";

file_put_contents($origem, "Teste de sincronização!");
copy($origem, $destino);

if (file_exists($destino)) {
    echo "✅ Sistema de cópia funcionando!\n";
} else {
    echo "❌ Erro no sistema de cópia\n";
}
?>
```

Execute:
```powershell
php teste-simples.php
```

## 🚀 RESUMO RÁPIDO

**NÃO PRECISA:**
- ❌ Node.js
- ❌ npm/yarn
- ❌ Compilar nada
- ❌ Dependências complexas

**SÓ PRECISA:**
- ✅ PHP (qualquer versão 7.4+)
- ✅ Os arquivos do projeto

**Para testar AGORA:**
1. Instale PHP (5 minutos)
2. Abra PowerShell na pasta do projeto
3. Execute: `php -S localhost:8080 -t web`
4. Abra: http://localhost:8080/simple.php
5. Configure e teste!

É isso! Super simples! 🎉
