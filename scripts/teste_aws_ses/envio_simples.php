<?php
require 'vendor/autoload.php';

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

$SesClient = new SesClient([
    'version' => '2010-12-01',
    'region'  => 'us-east-1', // Altere para a região correta do SES
    'credentials' => [
        'key'    => 'AWS_KEY',
        'secret' => 'AWS_SECRET',
    ],
]);

$sender_email = 'EMAIL_VALIDADO_NA_AWS';
$recipient_email = 'PARA_QUEM';

$subject = 'Teste via SES com chave e segredo';
$body_text = 'Este é um e-mail de teste enviado via SES com autenticação manual.';
$charset = 'UTF-8';

try {
    $result = $SesClient->sendEmail([
        'Destination' => [
            'ToAddresses' => [$recipient_email],
        ],
        'ReplyToAddresses' => [$sender_email],
        'Source' => $sender_email,
        'Message' => [
            'Body' => [
                'Text' => [
                    'Charset' => $charset,
                    'Data' => $body_text,
                ],
            ],
            'Subject' => [
                'Charset' => $charset,
                'Data' => $subject,
            ],
        ],
    ]);

    echo "E-mail enviado com sucesso! Message ID: " . $result['MessageId'] . "\n";
} catch (AwsException $e) {
    echo "Erro ao enviar e-mail: " . $e->getAwsErrorMessage() . "\n";
}

