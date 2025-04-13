#!/bin/bash

# Caminho do arquivo .bashrc
BASHRC="$HOME/.bashrc"

# Linha que será adicionada
PROMPT_LINE='export PROMPT_COMMAND="history -a; $PROMPT_COMMAND"'

# Verifica se já está presente
if grep -Fxq "$PROMPT_LINE" "$BASHRC"; then
    echo "PROMPT_COMMAND já está configurado em $BASHRC."
else
    echo "$PROMPT_LINE" >> "$BASHRC"
    echo "Linha adicionada ao $BASHRC para salvar histórico automaticamente."
fi

# Aplica as mudanças na sessão atual
source "$BASHRC"

echo "Configuração aplicada. O histórico agora será salvo em tempo real no .bash_history."