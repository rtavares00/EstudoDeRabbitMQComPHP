<?php

require_once __DIR__ . '/../config/bootstrap.php';

use App\RabbitMQ\Connection;
use PhpAmqpLib\Exception\AMQPTimeoutException;

echo "\n" . str_repeat("=", 70) . "\n";
echo "⚫ CONSUMER DLQ - Monitora Mensagens Mortas\n";
echo str_repeat("=", 70) . "\n\n";

try {
    $channel = Connection::getChannel();
    echo "✅ Conectado ao RabbitMQ!\n\n";

    echo "⚙️  Configurando QoS (prefetch = 1)...\n";
    $channel->basic_qos(null, 1, null);
    echo "✅ Configurado!\n\n";

    echo "📊 Monitorando fila: payment-processor-dlq\n";
    echo "   Estas são mensagens que falharam no processamento\n";
    echo "   e foram rejeitadas múltiplas vezes\n\n";

    $mortas = 0;

    $callback = function ($msg) use (&$mortas) {
        $mortas++;
        $body = $msg->getBody();
        $data = json_decode($body, true);

        echo "\n" . str_repeat("=", 70) . "\n";
        echo "☠️  MENSAGEM MORTA #$mortas ENCONTRADA!\n\n";

        echo "📋 Conteúdo:\n";
        echo "   Order ID: " . $data['order_id'] . "\n";
        echo "   Cliente: " . $data['cliente_nome'] . "\n";
        echo "   Valor: R$ " . number_format($data['valor'], 2, ',', '.') . "\n";
        echo "   Data: " . $data['timestamp'] . "\n\n";

        echo "📍 Metadados:\n";
        echo "   Delivery Tag: " . $msg->getDeliveryTag() . "\n";
        echo "   Redelivered: " . ($msg->isRedelivered() ? 'SIM' : 'NÃO') . "\n";
        echo "   Content Type: " . $msg->get('content_type') . "\n";

        // Tenta extrair informações de redelivery do header
        if ($msg->has('x-death')) {
            $xdeath = $msg->get('x-death');
            echo "\n   ⚠️  x-death headers (histórico de rejeições):\n";
            if (is_array($xdeath)) {
                foreach ($xdeath as $death) {
                    echo "      • Queue: " . ($death['queue'] ?? 'N/A') . "\n";
                    echo "        Count: " . ($death['count'] ?? 'N/A') . "\n";
                    echo "        Reason: " . ($death['reason'] ?? 'N/A') . "\n";
                }
            }
        }

        echo "\n💡 Próximas ações:\n";
        echo "   1. Investigar por que falhou\n";
        echo "   2. Corrigir o código do consumer\n";
        echo "   3. Reprocessar manualmente (se implementado)\n";
        echo "   4. Ou deletar se for irrelevante\n";

        echo str_repeat("=", 70) . "\n";

        $msg->ack();
    };

    $consumerTag = 'consumer-dlq-' . uniqid();
    $channel->basic_consume(
        'payment-processor-dlq',
        $consumerTag,
        false,
        false,
        false,
        false,
        $callback
    );

    echo "📡 Registrado na fila DLQ\n";
    echo "⏳ Aguardando mensagens mortas...\n";
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