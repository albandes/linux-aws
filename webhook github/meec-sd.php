<?php
date_default_timezone_set('America/Sao_Paulo');

$repoBasePath = '/var/www/deploy/meec-sd/';      // Path Raiz do Repositório

// Diretórios do repositório Git (origem)
$repoPaths = [
    "$repoBasePath/public_html"
];

// Diretórios públicos do site (destino)
$publicPaths = [
    "/var/www/html/meec/"
];


$excludeFile    = '/var/www/deploy/rsync_meec-sd.txt';    // Arquivo com os caminhos/padrões a excluir
$webhookSecret  = 'dRMNXBTjQ4r2';               // Secret do webhook
$logFile        = '/var/www/logs/meeec-sd.log';  // Arquivo do log

// Função de log
function logMessage($message, $logFile)
{
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

// Recebe o payload do GitHub
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Valida assinatura
if ($webhookSecret) {
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $webhookSecret);
    if (!hash_equals($hash, $signature)) {
        logMessage('Invalid signature', $logFile);
        http_response_code(403);
        die('Invalid signature');
    }
}

$data = json_decode($payload, true);
$eventType = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

// Só processa PR mesclada na main
if ($eventType === 'pull_request') {
    $action = $data['action'] ?? '';
    $merged = $data['pull_request']['merged'] ?? false;
    $baseBranch = $data['pull_request']['base']['ref'] ?? '';

    if ($action === 'closed' && $merged && $baseBranch === 'main') {
        logMessage("Pull Request mesclada na branch 'main'. Atualizando repositório...", $logFile);

        // Git pull
        $output = [];
        exec("cd $repoBasePath  && git pull origin main 2>&1", $output, $returnVar);
	$gitOutput = implode("\n", $output);

        if ($returnVar !== 0) {
            logMessage("Erro ao atualizar o repositório:\n" . implode("\n", $output), $logFile);
            http_response_code(500);
            exit;
        }

	// Verifica se já estava atualizado
	if (strpos($gitOutput, 'Already up to date') !== false || strpos($gitOutput, 'Already up-to-date') !== false) {
    	   logMessage("Nenhuma atualização detectada no repositório. Ignorando rsync.", $logFile);
    	   echo "Nenhuma atualização detectada.";
           exit;
        }



        logMessage("Repositório atualizado com sucesso:\n" . implode("\n", $output), $logFile);

        // Testa arquivo de exclude
        if (!file_exists($excludeFile)) {
            logMessage("Arquivo de exclude não existe: $excludeFile\n" . implode("\n", $output), $logFile);
            http_response_code(500);
            exit;
        } 
        
        // Sincroniza os diretórios individualmente com rsync
        for ($i = 0; $i < count($repoPaths); $i++) {
            $source = $repoPaths[$i];
            $destination = $publicPaths[$i];

            $rsyncOutput = [];
            exec("rsync -av --delete --exclude-from=$excludeFile $source $destination 2>&1", $rsyncOutput, $rsyncStatus);

            if ($rsyncStatus !== 0) {
                logMessage("Erro ao copiar de $source para $destination:\n" . implode("\n", $rsyncOutput), $logFile);
                http_response_code(500);
                exit;
            }

            logMessage("rsync executado com sucesso de $source para $destination\n" . implode("\n", $rsyncOutput), $logFile);
        }

	logMessage("Webhook executado com sucesso\n" . implode("\n", $rsyncOutput), $logFile);        
        
/*        
        // Rsync com arquivo de exclusões
        $rsyncOutput = [];
        exec("rsync -av --delete --exclude-from=$excludeFile $repoPath $publicPath 2>&1", $rsyncOutput, $rsyncStatus);
        if ($rsyncStatus !== 0) {
            logMessage("Erro ao copiar arquivos:\n" . implode("\n", $rsyncOutput), $logFile);
            http_response_code(500);
            exit;
        }

        logMessage("Arquivos copiados para produção com sucesso:\n" . implode("\n", $rsyncOutput), $logFile);
        echo "Repositório atualizado e arquivos copiados com sucesso.";
*/        
    } else {
        logMessage("Pull Request ignorada. Ação: $action | Merged: " . ($merged ? 'true' : 'false') . " | Base: $baseBranch", $logFile);
        echo "Pull Request ignorada.";
    }
} else {
    logMessage("Evento ignorado. Tipo: $eventType", $logFile);
    echo "Evento ignorado.";
}
?>
