# Webhook GitHub para Deploy Automático

Este projeto utiliza um webhook em PHP (`ituran.php`) para realizar **deploy automático** de um repositório Git em um servidor sempre que um `push` for feito no GitHub.

## 🌐 URL do Webhook

A URL pública do webhook configurada é:

```
https://hook.marioquintana.com.br/ituran.php
```

> ⚠️ Certifique-se de que essa URL esteja acessível externamente com um certificado SSL válido (HTTPS habilitado).

---

## 🛠️ Requisitos

- Um repositório Git local no servidor.
- Acesso de escrita ao diretório do repositório.
- PHP 7+ instalado no servidor web (Apache/Nginx).
- Certificado SSL ativo no domínio do webhook.
- Permissões adequadas de execução para o usuário do servidor web.

---

## 🔐 Segurança: Usando `secret` no GitHub

Seu script já espera um **`secret`** para validar a assinatura HMAC-SHA256 do payload.

### Passos para configurar no GitHub:

1. Acesse o repositório no GitHub.
2. Vá em **Settings > Webhooks**.
3. Clique em **"Add webhook"**.
4. Preencha os campos:

   - **Payload URL**:  
     ```
     https://hook.marioquintana.com.br/ituran.php
     ```

   - **Content type**:  
     `application/json`

   - **Secret**:  
     Insira o mesmo valor usado no script PHP (ex: `github_secret`)

   - **SSL verification**:  
     Deixe marcado como **Enable SSL verification**

   - **Which events would you like to trigger this webhook?**  
     Selecione: **Just the push event**

   - Clique em **"Add webhook"**

---

## 📝 Estrutura do Script

- O script verifica a assinatura do GitHub (`X-Hub-Signature-256`) com base no `secret` configurado.
- Se a verificação for bem-sucedida, executa `git pull` no diretório do repositório local.
- Todas as ações são registradas em um log, por padrão em:

```
/var/log/git.log
```

---

## 📂 Diretórios e Permissões

### Exemplo de configuração no script:

```php
$repoPath = '/var/www/html/teste/public_html/';
$webhookSecret = 'github_secret';
$logFile = '/var/log/git.log';
```

Certifique-se de que o usuário do Apache/Nginx (ex: `www-data` ou `apache`) tenha permissão para:

- Acessar e escrever no diretório `$repoPath`
- Executar `git pull`
- Gravar no arquivo de log

> Para permitir `git pull` sem senha, configure corretamente a chave SSH ou o GitHub CLI com token.

---

## 🔐 Segurança adicional (opcional)

- Mova `ituran.php` para um subdiretório com autenticação se quiser evitar requisições indesejadas.
- Monitore acessos no seu servidor usando Fail2Ban ou outros sistemas de IDS.

---

## 🧪 Testando o Webhook

1. Faça um `push` para o repositório do GitHub.
2. Verifique o log de execução em:

```
/var/log/git.log
```

3. Verifique se os arquivos foram atualizados no diretório do repositório.

---

## 🧑‍💻 Autor

Rogério Albandes  
CTO - Escola Mario Quintana  
Professor - Universidade Católica de Pelotas