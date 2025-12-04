# Verificando e Configurando o Horário do Logrotate no Amazon Linux 2023

O **Amazon Linux 2023** não usa mais `cron.daily` para acionar o `logrotate`.  
Agora ele é executado através de um **systemd timer** (`logrotate.timer`).

---

## 🔍 1. Verificando quando o logrotate roda

Liste todos os timers do `systemd` e filtre pelo `logrotate`:

```bash
systemctl list-timers | grep logrotate
```

Exemplo de saída:

```
Mon 2025-08-25 00:00:00 -03  11h ago   Mon 2025-08-25 00:00:00 -03  11h ago   logrotate.timer  logrotate.service
```

- **Próxima execução** → Coluna 1  
- **Última execução** → Coluna 3  

---

## 📖 2. Ver detalhes do agendamento

Mostre a configuração atual do timer:

```bash
systemctl cat logrotate.timer
```

Saída típica:

```ini
[Timer]
OnCalendar=daily
AccuracySec=1h
Persistent=true
```

Isso significa:
- Executa **1 vez por dia** (`OnCalendar=daily`)  
- Pode variar até **1 hora de tolerância** (`AccuracySec=1h`)  
- Garante que rode mesmo se o servidor ficou desligado (`Persistent=true`)  

---

## 📜 3. Conferindo execuções passadas

Para verificar o histórico de execuções:

```bash
journalctl -u logrotate.service -S "yesterday"
```

---

## 🕒 4. Alterando o horário de execução

Se você quiser fixar um horário, por exemplo **03:30 da manhã**, crie uma sobrescrita do timer:

```bash
sudo systemctl edit logrotate.timer
```

Adicione:

```ini
[Timer]
OnCalendar=*-*-* 03:30:00
```

Depois aplique:

```bash
sudo systemctl daemon-reload
sudo systemctl restart logrotate.timer
```

---

## 🚀 5. Forçando uma execução manual

Se precisar rodar o logrotate agora:

```bash
sudo systemctl start logrotate.service
```

Ou diretamente:

```bash
sudo logrotate -f /etc/logrotate.conf
```

---

## ✅ Resumo

- Use `systemctl list-timers | grep logrotate` para ver quando roda.  
- Por padrão, roda **1x por dia** em horário variável.  
- Você pode sobrescrever o horário com `systemctl edit logrotate.timer`.  
- Para rodar imediatamente, use `systemctl start logrotate.service`.  



