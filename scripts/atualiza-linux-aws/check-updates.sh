#!/bin/bash
echo "=== Status das Atualizações Automáticas ==="
echo "Última execução: $(ls -lt /var/log/auto-update/*.log 2>/dev/null | head -1 | awk '{print $6,$7,$8}')"
echo "Status: $(tail -5 /var/log/auto-update/*.log 2>/dev/null | grep -E "SUCCESS|ERROR" | tail -1)"
echo "Próxima execução: $(systemctl show auto-update.timer --property=NextElapseUSecRealtime 2>/dev/null | cut -d= -f2)"