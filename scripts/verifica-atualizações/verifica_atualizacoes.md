# 📄 `verifica_atualizacoes.md` – Monitoramento de Atualizações no Amazon Linux 2023 com ntfy

Este guia descreve como configurar um script para:

- Verificar atualizações de pacotes com `dnf`
- Detectar se é necessário reiniciar o sistema
- Verificar se há uma nova release do Amazon Linux 2023
- Enviar notificações via `ntfy` (servidor privado, com autenticação)

---

## 🛠️ 1. Instalação do `ntfy` (CLI) em instâncias ARM

```bash
curl -LO https://github.com/binwiederhier/ntfy/releases/latest/download/ntfy-linux-arm64
chmod +x ntfy-linux-arm64
sudo mv ntfy-linux-arm64 /usr/local/bin/ntfy
ntfy --version
```

---

## 📁 2. Criar o script

Salve o conteúdo abaixo como `/opt/scripts/verifica_atualizacoes.sh`:

> 🔐 Substitua `NTFY_USER`, `NTFY_PASS`, `NTFY_URL`, e `NTFY_TOPIC` conforme sua configuração.

```bash
#!/bin/bash

NOME_AMIGAVEL="$1"
if [ -z "$NOME_AMIGAVEL" ]; then
    echo "Uso: $0 "Nome Amigável do Servidor""
    exit 1
fi

NTFY_USER="seu_usuario_aqui"
NTFY_PASS="seu_token_ou_senha_aqui"
NTFY_TOPIC="infra-vpn"
NTFY_URL="https://ntfy.seudominio.com"

HOSTNAME=$(hostname)

# Verifica atualizações de pacotes
UPDATES=$(dnf check-update --refresh 2>/dev/null | awk '/^Obsoleting Packages$/ {exit} /^[a-zA-Z0-9_.@+-]+/ {count++} END {print count+0}')

# Verifica se precisa de reboot
REBOOT_REQUIRED=0
if command -v needs-restarting &> /dev/null; then
    needs-restarting -r &> /dev/null
    if [ $? -eq 1 ]; then
        REBOOT_REQUIRED=1
    fi
fi

# Verifica nova release do Amazon Linux
RELEASE_ATUAL=$(grep -oP '^VERSION_ID="\K[^"]+' /etc/os-release)
RELEASE_NOVA=$(dnf check-update --refresh 2>&1 | grep -oP 'Version\s+\K2023\.\d+\.\d+' | sort -V | tail -n 1)

# Envia notificações via ntfy
enviar_ntfy() {
    local titulo="$1"
    local tags="$2"
    local mensagem="$3"
    curl -s -u "$NTFY_USER:$NTFY_PASS" \
         -H "Title: $titulo" \
         -H "Tags: $tags" \
         -d "$mensagem" \
         "$NTFY_URL/$NTFY_TOPIC"
}

if [ "$UPDATES" -gt 0 ]; then
    MSG="⚠️ *$UPDATES atualizações disponíveis* no servidor *$NOME_AMIGAVEL* (\`$HOSTNAME\`)."
    enviar_ntfy "Atualizações Pendentes" "package,warning" "$MSG"
fi

if [ "$REBOOT_REQUIRED" -eq 1 ]; then
    MSG="🔄 Reboot necessário no servidor *$NOME_AMIGAVEL* (\`$HOSTNAME\`)."
    enviar_ntfy "Reboot Necessário" "computer,repeat" "$MSG"
fi

if [ -n "$RELEASE_NOVA" ] && [ "$RELEASE_NOVA" != "$RELEASE_ATUAL" ]; then
    LINK="https://docs.aws.amazon.com/linux/al2023/release-notes/relnotes-$RELEASE_NOVA.html"
    MSG="🆕 Nova release do *Amazon Linux 2023* disponível:\n\n➡️ Atual: \`$RELEASE_ATUAL\`\n➡️ Nova: \`$RELEASE_NOVA\`\n📄 [Release Notes]($LINK)"
    enviar_ntfy "Nova Release Amazon Linux" "linux,info,upload" "$MSG"
fi
```

---

## 🔐 3. Permissões

```bash
chmod +x /opt/scripts/verifica_atualizacoes.sh
```

---

## ▶️ 4. Testar manualmente

```bash
/opt/scripts/verifica_atualizacoes.sh "Servidor VPN Principal"
```

---

## ⏰ 5. Agendar com `crontab`

Execute:

```bash
sudo crontab -e
```

Adicione a linha:

```cron
0 7 * * * /opt/scripts/verifica_atualizacoes.sh "Servidor VPN Principal"
```

---

## ✅ Resultado

Você receberá notificações no `ntfy` com:
- Número de pacotes a atualizar
- Alerta de reboot necessário
- Nova release do Amazon Linux com link oficial
