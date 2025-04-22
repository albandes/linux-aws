# Envio de E-mails com Amazon SES usando PHP

Este repositório contém dois exemplos de scripts em PHP para envio de e-mails utilizando o **Amazon Simple Email Service (SES)** com a SDK da AWS para PHP (`aws/aws-sdk-php`).

## Pré-requisitos

- Conta na AWS com o serviço **SES** habilitado na região desejada.
- Um endereço de e-mail **verificado** na AWS SES para ser usado como remetente.
- Credenciais (chave e segredo) com permissão para enviar e-mails via SES.
- PHP 7.4+ com Composer e dependências instaladas via:

```bash
composer require aws/aws-sdk-php
```

---

## Script 1 – Envio Simples (`envio_simples.php`)

Este script realiza o envio de um e-mail **simples** (sem anexo) usando a API `sendEmail` do SES.

### Principais Características

- Usa autenticação manual com `key` e `secret`.
- Envia um e-mail em texto plano.
- Define remetente, destinatário e assunto de forma programática.

### Uso

Configure os seguintes parâmetros no script:

```php
'key' => 'AWS_KEY',
'secret' => 'AWS_SECRET',
$sender_email = 'EMAIL_VALIDADO_NA_AWS';
$recipient_email = 'PARA_QUEM';
```

Execute:

```bash
php envio_simples.php
```

---

## Script 2 – Envio com Anexo (`envio_com_anexo.php`)

Este script realiza o envio de e-mail com **anexo** utilizando a API `sendRawEmail` do SES, que permite personalizar completamente o conteúdo do e-mail.

### Principais Características

- Criação manual de uma mensagem MIME multipart.
- Permite adicionar anexos (exemplo: `exemplo.txt`).
- Útil para casos em que o `sendEmail` não suporta os recursos desejados.

### Uso

1. Certifique-se de que o arquivo `exemplo.txt` (ou outro desejado) esteja no mesmo diretório do script.
2. Edite o script para configurar:

```php
'key' => 'AWS_KEY',
'secret' => 'AWS_SECRET',
$sender = 'EMAIL_VALIDADO_NA_AWS';
$recipient = 'PARA_QUEM';
```

3. Execute:

```bash
php envio_com_anexo.php
```

---

## Observações Importantes

- O endereço do remetente deve estar verificado no Amazon SES.
- Se sua conta estiver em **modo sandbox**, você só poderá enviar para destinatários verificados.
- O envio com anexo exige a montagem manual da mensagem com cabeçalhos MIME.

---

## Licença

Este projeto é disponibilizado sob a licença MIT. Sinta-se livre para adaptar e reutilizar conforme necessário.

---

## Contato

Em caso de dúvidas ou sugestões, entre em contato com o mantenedor do projeto.