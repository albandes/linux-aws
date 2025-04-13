
# 🛡️ Tutorial de Instalação e Configuração do Fail2Ban no Amazon Linux

Este guia mostra como instalar, configurar e proteger seu servidor **Amazon Linux 2 ou 2023** com **Fail2Ban**, incluindo integração com `ntfy` para alertas em tempo real.

---

## ✅ 1. Instalação no Amazon Linux

```bash
sudo amazon-linux-extras enable epel
sudo yum clean metadata
sudo yum install epel-release
sudo yum install fail2ban -y
```

---

## ⚙️ 2. Configuração do Fail2Ban

Crie uma cópia da configuração padrão:

```bash
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
```

Abra para editar:

```bash
sudo nano /etc/fail2ban/jail.local
```

Ative a proteção básica para SSH:

```ini
[sshd]
enabled = true
port    = ssh
logpath = /var/log/secure
maxretry = 5
bantime = 3600
```

---

#
## 🌐 3. Proteção contra ataques web (SQLi, scans, etc.)

### 🛡️ O que esse filtro cobre?

Este filtro detecta padrões comuns de ataques em requisições HTTP/HTTPS que aparecem nos arquivos de log do Apache, como:

- 🧱 **Directory Traversal**: `../` ou `..\` tentando acessar arquivos fora do diretório permitido
- 🔓 **Tentativas de acesso ao `/etc/passwd`**, `/proc/self/environ`, ou similares
- 🔍 **Scans de WordPress**: requisições para `/wp-admin`, `/wp-login.php`, etc.
- ⚠️ **Injeção de SQL**: URLs com `UNION SELECT`, `SELECT ... FROM`, `concat()`, `sleep()`, `benchmark()`
- 🧬 **Execução remota**: uso de `eval()`, `base64_decode()` via parâmetros
- 🛠️ **Exploração automatizada de falhas conhecidas**

---

### 🧩 Crie o filtro customizado:

```bash
sudo nano /etc/fail2ban/filter.d/php-ataques.conf
```

Conteúdo:

```ini
[Definition]
failregex = <HOST> -.*"(GET|POST).*(\..\./|\..\\|/etc/passwd|/wp-admin|select.+from|union.+select|concat\(|base64_decode\(|/proc/self/environ|eval\(|sleep\(|benchmark\(|/\.env).*HTTP.*"
ignoreregex =
```

> 💡 Esse regex é sensível e cobre muitas formas de ataque automatizado. Você pode ajustá-lo conforme seu caso de uso.

---

### ✍️ Adicione a jail no `jail.local` com suporte a HTTP e HTTPS:

```ini
[php-ataques]
enabled  = true
filter   = php-ataques

# Caminhos de log para HTTP e HTTPS (ajuste conforme seu Apache)
logpath  = /var/log/httpd/access_log
          /var/log/httpd/ssl_access_log

maxretry = 3
findtime = 300
bantime  = 3600

# Protege ambas as portas (80 e 443)
port     = http,https
protocol = tcp

action = iptables[name=php-ataques, port="http,https", protocol=tcp], ntfy-notify
```

> ✅ Se seus logs HTTPS e HTTP são unificados em um só arquivo, mantenha apenas o `access_log`.

---

### ▶️ Reinicie o Fail2Ban:

```bash
sudo systemctl restart fail2ban
```

### 🔍 Verifique se está funcionando:

```bash
sudo fail2ban-client status php-ataques
```

Você pode simular ataques com:

```bash
curl "http://seudominio.com/?id=1 UNION SELECT username, password FROM users"
```

Depois de 3 tentativas (com `maxretry = 3`), o IP será banido.



### Crie o filtro customizado:

```bash
sudo nano /etc/fail2ban/filter.d/php-ataques.conf
```

Conteúdo:

```ini
[Definition]
failregex = <HOST> -.*"(GET|POST).*(\..\./|\..\\|/etc/passwd|/wp-admin|select.+from|union.+select|concat\(|base64_decode\(|/proc/self/environ|eval\(|sleep\(|benchmark\(|/\.env).*HTTP.*"
ignoreregex =
```

### Adicione a jail no `jail.local`:

```ini
[php-ataques]
enabled  = true
filter   = php-ataques

# Caminhos de log para HTTP e HTTPS (ajuste conforme seu Apache)
logpath  = /var/log/httpd/access_log
          /var/log/httpd/ssl_access_log

maxretry = 3
findtime = 300
bantime  = 3600

# Protege ambas as portas (80 e 443)
port     = http,https
protocol = tcp

action = iptables[name=php-ataques, port="http,https", protocol=tcp], ntfy-notify
```

---

## 📲 4. Envio de alertas para `ntfy`

> ℹ️ Observação: neste exemplo final usamos `iptables-multiport` para permitir múltiplas portas (HTTP e HTTPS). A integração com ntfy pode ser feita via ação combinada (ver exemplo abaixo).

### Crie a ação `ntfy-notify`:

```bash
sudo nano /etc/fail2ban/action.d/ntfy-notify.conf
```

Conteúdo:

```ini
[Definition]
actionstart =
actionstop =
actioncheck =
actionban = curl -X POST -H "Authorization: Bearer SEU_TOKEN" -d "🚨 IP <ip> bloqueado pela jail <name>" "http://ntfy.seudominio.com.br:82/fail2ban"
actionunban =
```

> Substitua `SEU_TOKEN` pelo token JWT gerado para o usuário `ec2bot`.

### Use na jail:

```ini
action = iptables[name=php-ataques, port=http, protocol=tcp], ntfy-notify
```

---

## 👤 5. Configuração segura do servidor ntfy (ACL)

### Edite `/etc/ntfy/server.yml`:

```yaml
listen-http: ":82"
base-url: "http://ntfy.seudominio.com.br:82"

access-control:
  enabled: true

  users:
    ec2bot: $2y$05$...   # usuário do servidor
    rogerio: $2y$05$...  # usuário do app

  topics:
    "*":
      publish:
        - user: ec2bot
      read:
        - user: rogerio
```

> Gere hashes com:
```bash
htpasswd -nbB ec2bot senha123
```

### Reinicie o ntfy:

```bash
sudo systemctl restart ntfy
```

---

## 🛠️ 6. Script para gerenciar IPs banidos

Crie o script:

```bash
sudo nano /usr/local/bin/fail2ban-manager.sh
```

Conteúdo:

```bash
#!/bin/bash
echo "=== Fail2Ban Manager ==="
echo "1) Listar jails"
echo "2) Ver IPs banidos em uma jail"
echo "3) Desbanir IP"
echo "4) Sair"
read -p "Escolha uma opção: " opt

case $opt in
  1)
    fail2ban-client status
    ;;
  2)
    read -p "Jail: " jail
    fail2ban-client status "$jail"
    ;;
  3)
    read -p "Jail: " jail
    read -p "IP: " ip
    fail2ban-client set "$jail" unbanip "$ip"
    ;;
  4)
    echo "Saindo..."
    ;;
  *)
    echo "Opção inválida"
    ;;
esac
```

Permissões:

```bash
chmod +x /usr/local/bin/fail2ban-manager.sh
```

---

## ✅ Verificações úteis

### Listar regras do iptables:

```bash
sudo iptables -L -n
```

### Ver jails e IPs banidos:

```bash
sudo fail2ban-client status
sudo fail2ban-client status php-ataques
```

### Desbanir IP manualmente:

```bash
sudo fail2ban-client set php-ataques unbanip 203.0.113.45
```

---

## 🧠 Observações Finais

- Fail2Ban atua **dentro da instância**, não substitui Security Groups
- Verifique se a porta `82` está liberada no SG da AWS para usar o `ntfy`
- Combine com outras ferramentas como `ufw`, `iptables-persistent` ou `CloudWatch Logs` para segurança mais completa

---

**Pronto! Agora seu servidor EC2 está blindado contra bots, scanners e ataques comuns com alertas em tempo real 🚨.**
