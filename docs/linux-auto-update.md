# Tutorial de Atualização Automática para Amazon Linux 2023

## 📋 Índice
1. [Pré-requisitos](#pré-requisitos)
2. [Download e Instalação](#download-e-instalação)
3. [Configuração do Script](#configuração-do-script)
4. [Instalação como Serviço](#instalação-como-serviço)
5. [Teste e Verificação](#teste-e-verificação)
6. [Monitoramento](#monitoramento)
7. [Solução de Problemas](#solução-de-problemas)
8. [Remoção](#remoção)

## 🎯 Pré-requisitos

### 1. **Permissões de Administrador**
```bash
sudo su -
# Ou verifique se tem acesso sudo
sudo -v
```

### 2. **Verificar Sistema Operacional**
```bash
cat /etc/system-release
# Deve mostrar: Amazon Linux release 2023.x
```

### 3. **Pacotes Necessários**
```bash
# Instalar dependências se necessário
sudo dnf install -y mailx curl wget
```

## 📥 Download e Instalação

### 1. **Download do Script**
```bash
# Criar o script localmente
sudo nano /usr/local/bin/auto-update-al.sh
```

Cole este conteúdo:

```bash
#!/bin/bash
# Script para atualização automática do Amazon Linux 2023
# Execute como root ou com sudo

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configurações
LOG_DIR="/var/log/auto-update"
LOG_FILE="${LOG_DIR}/update-$(date +%Y%m%d).log"
MAX_LOG_FILES=30
BACKUP_DIR="/var/backup/pre-update-$(date +%Y%m%d)"
EMAIL_NOTIFY=""
SYSLOG_TAG="auto-update"

# Função para logging
log_message() {
    local level="$1"
    local message="$2"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[${timestamp}] [${level}] ${message}" | tee -a "${LOG_FILE}"
    logger -t "${SYSLOG_TAG}" "${level}: ${message}"
}

# Função principal
perform_update() {
    log_message "INFO" "=== INICIANDO ATUALIZAÇÃO AUTOMÁTICA ==="
    
    # Obter versão mais recente
    LATEST_VERSION=$(/usr/bin/dnf check-release-update 2>/dev/null | grep "Version" | tail -1 | awk '{print $2}' | sed 's/://')
    
    if [[ -z "${LATEST_VERSION}" ]]; then
        log_message "ERROR" "Não foi possível determinar a versão mais recente"
        exit 1
    fi
    
    log_message "INFO" "Versão alvo: ${LATEST_VERSION}"
    
    # Executar atualização
    if dnf upgrade -y --releasever="${LATEST_VERSION}" 2>&1 | tee -a "${LOG_FILE}"; then
        log_message "SUCCESS" "Atualização concluída com sucesso!"
        
        # Limpeza
        dnf autoremove -y 2>&1 | tee -a "${LOG_FILE}"
        dnf clean all 2>&1 | tee -a "${LOG_FILE}"
        
        # Verificar reinicialização
        if needs-restarting -r 2>&1 | grep -q "Reboot is required"; then
            log_message "WARNING" "Reinicialização necessária - Agendando para 1 minuto"
            shutdown -r +1 "Reinicialização após atualização automática"
        fi
    else
        log_message "ERROR" "Falha na atualização"
        exit 1
    fi
}

# Instalar como serviço systemd
install_service() {
    cat > /etc/systemd/system/auto-update.service << EOF
[Unit]
Description=Auto Update Amazon Linux
After=network-online.target

[Service]
Type=oneshot
ExecStart=/usr/local/bin/auto-update-al.sh run

[Install]
WantedBy=multi-user.target
EOF

    cat > /etc/systemd/system/auto-update.timer << EOF
[Unit]
Description=Run auto update daily at 3 AM

[Timer]
OnCalendar=*-*-* 03:00:00
Persistent=true

[Install]
WantedBy=timers.target
EOF

    systemctl daemon-reload
    systemctl enable --now auto-update.timer
    echo "Serviço instalado para executar às 3 AM diariamente"
}

# Menu principal
main() {
    case "$1" in
        "run")
            mkdir -p "${LOG_DIR}"
            perform_update
            ;;
        "install")
            install_service
            ;;
        "test")
            echo "Versão mais recente disponível:"
            /usr/bin/dnf check-release-update | grep "Version" | tail -1
            ;;
        *)
            echo "Uso: $0 [run|install|test]"
            echo "  run     - Executar atualização agora"
            echo "  install - Instalar como serviço"
            echo "  test    - Verificar atualizações"
            exit 1
            ;;
    esac
}

# Verificar se é root
if [[ $EUID -ne 0 ]]; then
    echo "Este script deve ser executado como root"
    exit 1
fi

main "$@"
```

### 2. **Tornar Executável**
```bash
sudo chmod +x /usr/local/bin/auto-update-al.sh
```

### 3. **Testar Instalação**
```bash
sudo /usr/local/bin/auto-update-al.sh test
```

## ⚙️ Configuração do Script

### 1. **Configurar Email (Opcional)**
Edite o script:
```bash
EMAIL_NOTIFY="admin@exemplo.com"
```

### 2. **Alterar Horário de Execução**
Edite o timer após instalação:
```bash
sudo systemctl edit auto-update.timer
```

## 🔧 Instalação como Serviço

### Instalar Serviço Systemd
```bash
sudo /usr/local/bin/auto-update-al.sh install
```

### Verificar Instalação
```bash
sudo systemctl status auto-update.timer
sudo systemctl list-timers | grep auto-update
```

## 🧪 Teste e Verificação

### 1. **Teste Manual**
```bash
# Verificar atualizações disponíveis
sudo /usr/local/bin/auto-update-al.sh test

# Executar atualização manualmente
sudo /usr/local/bin/auto-update-al.sh run
```

### 2. **Verificar Logs**
```bash
# Verificar logs da execução
sudo tail -f /var/log/auto-update/update-*.log

# Verificar logs do systemd
sudo journalctl -u auto-update.service -f
```

### 3. **Testar Execução do Serviço**
```bash
# Executar serviço manualmente
sudo systemctl start auto-update.service

# Verificar resultado
sudo journalctl -u auto-update.service --no-pager -n 50
```

## 📊 Monitoramento

### Script de Monitoramento
Crie `/usr/local/bin/check-updates.sh`:
```bash
#!/bin/bash
echo "=== Status das Atualizações Automáticas ==="
echo "Última execução: $(ls -lt /var/log/auto-update/*.log 2>/dev/null | head -1 | awk '{print $6,$7,$8}')"
echo "Status: $(tail -5 /var/log/auto-update/*.log 2>/dev/null | grep -E "SUCCESS|ERROR" | tail -1)"
echo "Próxima execução: $(systemctl show auto-update.timer --property=NextElapseUSecRealtime 2>/dev/null | cut -d= -f2)"
```

### Agendar Monitoramento
```bash
# Executar verificação diária às 9 AM
echo "0 9 * * * root /usr/local/bin/check-updates.sh" | sudo tee /etc/cron.d/check-updates
```

## 🔍 Solução de Problemas

### Problemas Comuns e Soluções:

1. **Erro: "Command not found"**
```bash
# Verificar caminhos
which dnf
which systemctl

# Verificar permissões
ls -la /usr/local/bin/auto-update-al.sh
```

2. **Falha na Atualização**
```bash
# Verificar logs detalhados
sudo journalctl -u auto-update.service --no-pager -n 100

# Testar manualmente
sudo dnf upgrade --dry-run
```

3. **Serviço Não Inicia**
```bash
# Recarregar systemd
sudo systemctl daemon-reload

# Reativar timer
sudo systemctl enable --now auto-update.timer

# Verificar erros
sudo systemctl status auto-update.service
```

4. **Email Não Funciona**
```bash
# Testar envio manual
echo "Teste" | mail -s "Teste" seu-email@exemplo.com

# Instalar mailx se necessário
sudo dnf install mailx -y
```

## 🗑️ Remoção

### 1. **Remover Serviço**
```bash
sudo systemctl stop auto-update.timer
sudo systemctl disable auto-update.timer
sudo rm -f /etc/systemd/system/auto-update.*
sudo systemctl daemon-reload
```

### 2. **Remover Script**
```bash
sudo rm -f /usr/local/bin/auto-update-al.sh
sudo rm -rf /var/log/auto-update/
```

### 3. **Remover Cron (se instalado)**
```bash
sudo rm -f /etc/cron.d/check-updates
```

## 📝 Melhores Práticas

### 1. **Teste em Ambiente Não-Produção**
- Clone a instância EC2 primeiro
- Teste o script na cópia
- Monitore por 24-48 horas

### 2. **Configure Backups**
```bash
# Criar snapshot do EBS regularmente
# Ou criar AMI antes de atualizações
```

### 3. **Monitore Recursos**
```bash
# Verificar após atualizações
df -h /          # Espaço em disco
free -h          # Memória
top -b -n 1      # CPU
```

### 4. **Configure Alertas**
```bash
# Monitorar logs para erros
sudo tail -f /var/log/auto-update/*.log | grep -i "error\|failed"
```

### 5. **Mantenha Documentação**
- Registre todas as alterações
- Mantenha um log de atualizações aplicadas
- Documente procedimentos de rollback

## 🚨 Notas de Segurança

1. **O script executa como root** - Revise o código antes de usar
2. **Mantenha backups regulares**
3. **Configure firewall para permitir atualizações**
4. **Monitore logs para atividades suspeitas**
5. **Use IAM roles apropriadas para instâncias EC2**

## 📞 Suporte

### Para Obter Ajuda:

1. **Consulte os Logs**
```bash
sudo journalctl -u auto-update.service
sudo cat /var/log/auto-update/latest.log
```

2. **Documentação Oficial**
- [Amazon Linux 2023 Release Notes](https://docs.aws.amazon.com/linux/al2023/release-notes/)
- [Systemd Timer Documentation](https://www.freedesktop.org/software/systemd/man/systemd.timer.html)

3. **Teste Comandos Manualmente**
```bash
# Isolar problemas executando comandos individualmente
sudo dnf makecache --refresh
sudo dnf check-release-update
```

### Contato para Suporte:
- AWS Support: https://aws.amazon.com/contact-us/
- Amazon Linux Forums: https://forums.aws.amazon.com/forum.jspa?forumID=228

---

**Próximos Passos:**
1. [ ] Instale o script em ambiente de teste
2. [ ] Execute manualmente para validação
3. [ ] Configure notificações por email
4. [ ] Monitore a primeira execução automática
5. [ ] Documente os resultados

> **Dica Importante**: Sempre mantenha uma instância de backup com atualizações manuais para casos de emergência.

---

**Download deste tutorial:** [auto-update-tutorial.md](auto-update-tutorial.md)

**Script principal:** [auto-update-al.sh](auto-update-al.sh)

**Última atualização:** $(date +%Y-%m-%d)

