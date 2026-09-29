<?php

require_once __DIR__ . '/../config/bootstrap.php';

use App\RabbitMQ\Connection;
use PhpAmqpLib\Exception\AMQPTimeoutException;

echo "\n" . str_repeat("=", 70) . "\n";
echo "🔴 CONSUMER WITH FAILURES - Testa NACK e Redelivery\n";
echo str_repeat("=", 70) . "\n\n";

try {
    $channel = Connection::getChannel();
    echo "✅ Conectado ao RabbitMQ!\n\n";

    echo "⚙️  Configurando QoS (prefetch = 1)...\n";
    $channel->basic_qos(null, 1, null);
    echo "✅ Configurado!\n\n";

    echo "📊 Modo: SIMULAR FALHAS\n";
    echo "   A cada 3 mensagens, 1 vai dar erro\n";
    echo "   Quando erro acontece:\n";
    echo "      - Não faz ACK\n";
    echo "      - Faz NACK (negative acknowledgement)\n";
    echo "      - RabbitMQ devolve a mensagem à fila\n";
    echo "      - Tenta de novo (redelivery)\n\n";

    $processadas = 0;
    $erros = 0;

    $callback = function ($msg) use (&$processadas, &$erros) {
        $processadas++;
        $body = $msg->getBody();
        $data = json_decode($body, true);

        echo "\n" . str_repeat("-", 70) . "\n";
        echo "📨 Mensagem #$processadas: " . $data['order_id'] . "\n";

        // Simula falha a cada 3 mensagens
        if ($processadas % 3 === 0) {
            $erros++;
            echo "   ⚠️  SIMULANDO ERRO DE PROCESSAMENTO!\n";
            echo "   ❌ NACK: Rejeitando a mensagem...\n";
            echo "   🔄 RabbitMQ vai recolocar na fila (redelivery)\n";
            echo str_repeat("-", 70) . "\n";

            // NACK com requeue = true (volta à fila)
            $msg->nack(true);
            return;
        }

        echo "   ✅ Processada com sucesso\n";
        echo "   ACK confirmado\n";
        echo str_repeat("-", 70) . "\n";

        $msg->ack();
    };

    $consumerTag = 'consumer-failures-' . uniqid();
    $channel->basic_consume(
        'payment-processor',
        $consumerTag,
        false,
        false,
        false,
        false,
        $callback
    );

    echo "📡 Registrado na fila\n";
    echo "⏳ Aguardando mensagens...\n";
    echo "   (Pressione CTRL+C para parar)\n\n";

    while ($channel->is_open()) {
        try {
            $channel->wait(null, false, 5);
        } catch (AMQPTimeoutException $e) {
            continue;
        }
    }

} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
} finally {
    Connection::close();
}