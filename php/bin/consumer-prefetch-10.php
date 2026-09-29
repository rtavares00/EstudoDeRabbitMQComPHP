<?php

require_once __DIR__ . '/../config/bootstrap.php';

use App\RabbitMQ\Connection;
use PhpAmqpLib\Exception\AMQPTimeoutException;

echo "\n" . str_repeat("=", 70) . "\n";
echo "🟢 CONSUMER PREFETCH=10 (Mais rápido)\n";
echo str_repeat("=", 70) . "\n\n";

try {
    $channel = Connection::getChannel();
    echo "✅ Conectado ao RabbitMQ!\n\n";

    // Configura QoS: prefetch = 10
    // Significa: manda até 10 mensagens por vez
    echo "⚙️  Configurando QoS (prefetch = 10)...\n";
    $channel->basic_qos(null, 10, null);
    echo "✅ Configurado!\n\n";

    echo "📊 Modo: PREFETCH = 10\n";
    echo "   Significado: Processa até 10 mensagens em paralelo\n";
    echo "   Vantagem: Mais rápido, maior throughput\n";
    echo "   Desvantagem: Se crashar, perde até 10 msgs\n\n";

    $processadas = 0;
    $startTime = time();

    $callback = function ($msg) use (&$processadas, &$startTime) {
        $processadas++;
        $tempoDecorrido = time() - $startTime;
        $throughput = $processadas / max(1, $tempoDecorrido);

        $body = $msg->getBody();
        $data = json_decode($body, true);

        echo "\n" . str_repeat("-", 70) . "\n";
        echo "📨 Mensagem #$processadas recebida!\n\n";
        echo "   Order ID: " . $data['order_id'] . "\n";
        echo "   Cliente: " . $data['cliente_nome'] . "\n";
        echo "   Valor: R$ " . number_format($data['valor'], 2, ',', '.') . "\n";
        echo "   Routing Key: " . $msg->getRoutingKey() . "\n";

        // Simula processamento (1 segundo)
        sleep(1);

        echo "   ✅ Processada\n";
        echo "   Throughput: " . round($throughput, 2) . " msgs/seg\n";
        echo str_repeat("-", 70) . "\n";

        $msg->ack();
    };

    $consumerTag = 'consumer-prefetch-10-' . uniqid();
    $channel->basic_consume(
        'payment-processor',
        $consumerTag,
        false,
        false,
        false,
        false,
        $callback
    );

    echo "📡 Registrado na fila (consumer tag: $consumerTag)\n";
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