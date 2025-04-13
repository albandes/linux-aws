#!/bin/bash

# Verifica se o diretório foi passado como argumento
if [ -z "$1" ]; then
  echo "Uso: $0 <diretório_destino>"
  exit 1
fi

HISTORY_DIR="$1"
mkdir -p "$HISTORY_DIR" || {
  echo "Erro ao criar diretório $HISTORY_DIR"
  exit 2
}

# Caminho absoluto do arquivo de histórico
USER_HOME="/root"  # ajuste se seu usuário for diferente
CURRENT_HISTORY="$USER_HOME/.bash_history"
LAST_SAVED="$HISTORY_DIR/last_saved_history.txt"
DATE=$(date +"%Y%m%d-%H%M%S")
INCREMENT_FILE="$HISTORY_DIR/${DATE}-history-incremental.txt"
LOG_FILE="/var/log/pipegrep/cron_history.log"

# Garante que o diretório de log exista
mkdir -p /var/log/pipegrep

# Executa history -a para garantir atualização do .bash_history
/usr/bin/bash -i -c "history -a"

# Se não existe histórico anterior, salva tudo
if [ ! -f "$LAST_SAVED" ]; then
  cp "$CURRENT_HISTORY" "$LAST_SAVED"
  cp "$CURRENT_HISTORY" "$INCREMENT_FILE"
  echo "[$(date +"%Y-%m-%d %H:%M:%S")] Primeiro backup: arquivo completo salvo." >> "$LOG_FILE" 2>&1
else
  LAST_SAVED_LINE=$(tail -n 1 "$LAST_SAVED")

  NEW_LINES=$(awk -v last_line="$LAST_SAVED_LINE" 'BEGIN {found=0} 
    {
      if (found) {
        print $0
      }
      if ($0 == last_line) {
        found=1
      }
    }' "$CURRENT_HISTORY")

  if [ -n "$NEW_LINES" ]; then
    echo "$NEW_LINES" > "$INCREMENT_FILE"
    cp "$CURRENT_HISTORY" "$LAST_SAVED"
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] Incremento salvo em $INCREMENT_FILE" >> "$LOG_FILE" 2>&1
  else
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] Nenhuma nova linha para salvar." >> "$LOG_FILE" 2>&1
  fi
fi

