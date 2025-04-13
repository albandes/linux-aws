# Backup Incremental do .bash_history

Este repositório contém um script Bash para realizar **backups incrementais** do histórico de comandos do terminal (`.bash_history`). Cada execução do script salva apenas **novas linhas** adicionadas desde o último backup, evitando duplicação e economizando espaço.

## 🛠️ Funcionalidades

- Backup incremental: apenas novos comandos são salvos.
- Armazenamento com carimbo de data e hora.
- Geração automática de diretório de destino, se não existir.
- Registro de logs da execução em `/var/log/pipegrep/cron_history.log`.

## 📦 Estrutura dos Arquivos

O script gera os seguintes arquivos no diretório informado:

- `YYYYMMDD-HHMMSS-history-incremental.txt`: contém apenas os comandos novos desde o último backup.
- `last_saved_history.txt`: cópia do `.bash_history` da última execução, usada como referência para o próximo diff.
- Log de execução: atualizado em `/var/log/pipegrep/cron_history.log`.

## 🚀 Como Usar

### 1. Clone este repositório

```bash
git clone https://github.com/seuusuario/bash-history-backup.git
cd bash-history-backup
```

### 2. Torne o script executável

```bash
chmod +x save_history_incremental.sh
```

### 3. Execute o script com o diretório de destino como argumento

```bash
./save_history_incremental.sh /caminho/para/destino
```

Exemplo:

```bash
./save_history_incremental.sh "$HOME/history_backups"
```

## 🧠 Como Funciona

1. **Primeira execução**:  
   - Salva todo o conteúdo do `.bash_history` como referência (`last_saved_history.txt`).
   - Cria um arquivo de backup com o conteúdo completo.

2. **Execuções subsequentes**:  
   - Compara o `.bash_history` atual com o último salvo.
   - Extrai e salva apenas as linhas novas.
   - Atualiza o arquivo de referência (`last_saved_history.txt`).

## 📝 Requisitos

- Linux com Bash
- Permissão de leitura no arquivo `~/.bash_history`
- Permissão de escrita em `/var/log/pipegrep`

## 📄 Logrotate

Para evitar que o arquivo de log cresça indefinidamente, recomenda-se configurar o `logrotate`:

1. Crie o diretório de logs, se necessário:

```bash
sudo mkdir -p /var/log/pipegrep
sudo chown $USER:$USER /var/log/pipegrep
```

2. Crie o arquivo de configuração do logrotate:

```bash
sudo tee /etc/logrotate.d/pipegrep <<EOF
/var/log/pipegrep/cron_history.log {
    weekly
    rotate 4
    compress
    missingok
    notifempty
    create 0644 root root
}
EOF
```

3. Teste o logrotate:

```bash
sudo logrotate --debug /etc/logrotate.d/pipegrep
```

## 📅 Agendamento com `cron` (opcional)

Você pode configurar o script para ser executado automaticamente:

```bash
crontab -e
```

E adicione, por exemplo:

```cron
0 * * * * /caminho/para/save_history_incremental.sh /caminho/do/backup
```

Isso fará o backup a cada hora.

## 🧑‍💻 Autor

Rogério Albandes  
CTO - Escola Mario Quintana  
Professor - Universidade Católica de Pelotas
## 🧩 Importante: Salvando comandos em tempo real no .bash_history

O Bash **não grava automaticamente** os comandos no arquivo `.bash_history` até que a sessão seja encerrada. Isso pode fazer com que o script não detecte os comandos recentes.

### ✅ Solução: configurar `PROMPT_COMMAND`

Adicione a linha abaixo ao final do seu arquivo `~/.bashrc` (ou `~/.bash_profile`, dependendo da distribuição):

```bash
export PROMPT_COMMAND="history -a; $PROMPT_COMMAND"
```

Isso garante que **cada comando executado no terminal seja gravado imediatamente** no `.bash_history`.

Após salvar a alteração, ative com:

```bash
source ~/.bashrc
```

### 🔁 Por que isso é necessário?

O comando `history -a` força o Bash a adicionar os comandos da sessão atual ao arquivo `.bash_history`. Sem isso, o script de backup incremental pode não detectar comandos recém-executados.

Com essa configuração feita, o script funcionará corretamente mesmo quando executado por `cron`, `systemd`, ou manualmente, sem depender do encerramento da sessão de terminal.

---
