<?php
date_default_timezone_set('America/Sao_Paulo');
// Caminho do repositório local
$repoPath = '/var/www/html/ituran/public_html/';

// Secret do webhook (se configurado no GitHub)
$webhookSecret = 'github_secret';

// Caminho do arquivo de log
$logFile = '/var/log/git.log';

// Função para gravar mensagens no log
function logMessage($message, $logFile)
{
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

// Obtendo o payload enviado pelo GitHub
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Validação do segredo
if ($webhookSecret) {
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $webhookSecret);
    if (!hash_equals($hash, $signature)) {
        logMessage('Invalid signature', $logFile);
        http_response_code(403);
        die('Invalid signature');
    }
}

// Decodifica o JSON do payload
$data = json_decode($payload, true);

// Verifica se é um evento de push
if (isset($data['ref']) && $data['ref'] === 'refs/heads/develop-php8') {
    logMessage('Evento recebido: Atualizando o repositório.', $logFile);

    // Executa o comando para atualizar o repositório
    $output = [];
    exec("cd $repoPath && git pull 2>&1", $output, $returnVar);

    // Verifica se o comando foi executado com sucesso
    if ($returnVar !== 0) {
        logMessage("Erro ao atualizar o repositório:\n" . implode("\n", $output), $logFile);
        http_response_code(500);
        exit;
    }

    logMessage("Repositório atualizado com sucesso:\n" . implode("\n", $output), $logFile);
    echo "Repositório atualizado com sucesso.";
} else {
    logMessage('Evento ignorado. Ref: ' . ($data['ref'] ?? 'Indefinido'), $logFile);
    echo "Evento ignorado.";
}

