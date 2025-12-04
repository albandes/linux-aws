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