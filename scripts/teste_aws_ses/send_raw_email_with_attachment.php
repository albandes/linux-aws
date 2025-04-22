<?php
require 'vendor/autoload.php';

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

// Substitua por suas credenciais e região
$SesClient = new SesClient([
    'version' => '2010-12-01',
    'region'  => 'us-east-1',
    'credentials' => [
        'key'    => 'AWS_KEY',
        'secret' => 'AWS_SECRET',
    ],
]);

$sender = 'EMAIL_VALIDADO_NA_AWS';
$recipient = 'PARA_QUEM';
$subject = 'E-mail com anexo via SES (Raw)';
$body_text = "Este é o corpo do e-mail em texto simples.";
$filename = 'exemplo.txt';
$file_content = file_get_contents($filename);
$attachment = chunk_split(base64_encode($file_content));
$boundary = uniqid('np');

$raw_message = <<<EOD
From: $sender
To: $recipient
Subject: $subject
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary="$boundary"

--$boundary
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: 7bit

$body_text

--$boundary
Content-Type: text/plain; name="$filename"
Content-Disposition: attachment; filename="$filename"
Content-Transfer-Encoding: base64

$attachment
--$boundary--
EOD;

try {
    $result = $SesClient->sendRawEmail([
        'RawMessage' => [
            'Data' => $raw_message,
        ],
    ]);

    echo "E-mail enviado com sucesso! Message ID: " . $result['MessageId'] . "\n";
} catch (AwsException $e) {
    echo "Erro ao enviar e-mail: " . $e->getAwsErrorMessage() . "\n";
}
