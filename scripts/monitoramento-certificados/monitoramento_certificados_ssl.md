# ✅ Monitoramento de Certificados SSL com PHP + MySQL + Fila de E-mails

Este tutorial descreve como automatizar a verificação dos certificados SSL de subdomínios e gerar alertas de vencimento com PHP, MySQL e uma tabela de fila de e-mails.

---

## 📦 Requisitos

- PHP com acesso a `shell_exec`
- MySQL
- Acesso ao terminal para configurar `cron`
- Certificados ativos nos domínios/subdomínios HTTPS

---

## 🧱 Estrutura do Banco de Dados

### 1. Tabela de Subdomínios e Certificados

```sql
CREATE TABLE certificados_ssl (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subdominio VARCHAR(255) NOT NULL,
  cert_expires_at DATETIME NULL,
  verificado_em DATETIME NULL
);
```

### 2. Tabela de Fila de E-mails

```sql
CREATE TABLE email_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  destinatario VARCHAR(255) NOT NULL,
  assunto VARCHAR(500) NOT NULL,
  corpo TEXT NOT NULL,
  remetente VARCHAR(255) DEFAULT 'no-reply@marioquintana.com.br',
  prioridade ENUM('baixa','normal','alta') DEFAULT 'normal',
  status ENUM('pendente','enviando','enviado','erro') DEFAULT 'pendente',
  tentativas INT DEFAULT 0,
  max_tentativas INT DEFAULT 3,
  erro_mensagem TEXT DEFAULT NULL,
  agendado_para DATETIME DEFAULT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  enviado_em TIMESTAMP NULL DEFAULT NULL,
  KEY idx_status (status),
  KEY idx_agendado (agendado_para),
  KEY idx_prioridade (prioridade)
);
```

---

## 🧾 Script PHP: `verifica_certificados.php`

```php
<?php

$host = 'localhost';
$db = 'nome_do_banco';
$user = 'usuario';
$pass = 'senha';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

$stmt = $pdo->query("SELECT id, subdominio FROM certificados_ssl");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row['id'];
    $subdominio = $row['subdominio'];
    $expiracao = obterDataExpiracao($subdominio);

    if ($expiracao) {
        $pdo->prepare("
            UPDATE certificados_ssl
            SET cert_expires_at = :exp, verificado_em = NOW()
            WHERE id = :id
        ")->execute([
            ':exp' => $expiracao->format('Y-m-d H:i:s'),
            ':id' => $id
        ]);

        echo "✅ $subdominio expira em " . $expiracao->format('Y-m-d H:i:s') . "\n";

        $diasRestantes = (new DateTime())->diff($expiracao)->days;
        if ($expiracao > new DateTime() && $diasRestantes <= 15) {
            inserirAlertaEmail($pdo, $subdominio, $expiracao, $diasRestantes);
        }

    } else {
        echo "❌ Erro ao verificar $subdominio\n";
    }
}

function obterDataExpiracao($host) {
    $cmd = "echo | openssl s_client -servername $host -connect $host:443 2>/dev/null | openssl x509 -noout -enddate";
    $output = shell_exec($cmd);
    if (preg_match('/notAfter=(.*)/', $output, $matches)) {
        return DateTime::createFromFormat('M d H:i:s Y T', trim($matches[1]));
    }
    return null;
}

function inserirAlertaEmail($pdo, $subdominio, $expiracao, $diasRestantes) {
    $assunto = "🚨 Certificado SSL expira em $diasRestantes dias: $subdominio";
    $corpo = <<<HTML
<p>O certificado SSL do subdomínio <strong>$subdominio</strong> expira em <strong>$diasRestantes dias</strong>, no dia <strong>{$expiracao->format('d/m/Y H:i')}</strong>.</p>
<p>Recomenda-se renovar o certificado o quanto antes para evitar interrupções no serviço.</p>
HTML;

    $stmt = $pdo->prepare("
        INSERT INTO email_queue (
            destinatario, assunto, corpo, prioridade, status, agendado_para
        ) VALUES (
            :destinatario, :assunto, :corpo, :prioridade, :status, :agendado
        )
    ");

    $stmt->execute([
        ':destinatario' => 'infra@marioquintana.com.br',
        ':assunto' => $assunto,
        ':corpo' => $corpo,
        ':prioridade' => 'alta',
        ':status' => 'pendente',
        ':agendado' => (new DateTime())->format('Y-m-d H:i:s')
    ]);

    echo "📧 Alerta agendado para $subdominio\n";
}
```

---

## ⏰ Agendamento no `cron`

1. Acesse o crontab do root:
```bash
sudo crontab -e
```

2. Adicione a linha abaixo para executar o script diariamente às 2h:
```bash
0 2 * * * /usr/bin/php /caminho/para/verifica_certificados.php >> /var/log/verifica_certificados.log 2>&1
```

---

## 🧪 Testes

Você pode testar manualmente o script com:
```bash
php verifica_certificados.php
```

E verificar os resultados:
- No console (log)
- Na tabela `certificados_ssl`
- Na fila de e-mails `email_queue`

---

## ✅ Próximos passos (sugestões)

- Criar painel web para visualizar subdomínios, vencimentos e alertas.
- Configurar um serviço para processar e enviar os e-mails da `email_queue`.
- Adicionar campos como `responsável`, `serviço`, ou `tags` na tabela `certificados_ssl` para melhor rastreabilidade.

---
