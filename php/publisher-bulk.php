<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\RabbitMQ\Connection;
use PhpAmqpLib\Message\AMQPMessage;

echo "\n" . str_repeat("=", 70) . "\n";
echo "📤 PUBLISHER BULK - Disparando múltiplas mensagens\n";
echo str_repeat("=", 70) . "\n\n";

try {
    $channel = Connection::getChannel();
    echo "✅ Conectado ao RabbitMQ!\n\n";

    // Quantas mensagens você quer publicar?
    $quantidade = isset($argv[1]) ? (int)$argv[1] : 10;

    echo "📊 Configuração:\n";
    echo "   Quantidade: $quantidade mensagens\n";
    echo "   Exchange: ecommerce\n";
    echo "   Routing Key: order.created\n\n";

    echo "🚀 Disparando mensagens...\n\n";

    $startTime = microtime(true);
    $publicadas = 0;

    for ($i = 1; $i <= $quantidade; $i++) {
        $order = [
            'order_id' => 'ORD-' . str_pad($i, 5, '0', STR_PAD_LEFT),
            'cliente_nome' => 'Cliente #' . $i,
            'valor' => rand(50, 5000) / 100 * 100,  // Valor aleatório
            'timestamp' => date('Y-m-d H:i:s'),
            'itens' => [
                [
                    'produto' => 'Produto ' . $i,
                    'quantidade' => rand(1, 5),
                    'preco' => rand(10, 500) / 100 * 100
                ]
            ]
        ];

        $msgBody = json_encode($order);
        $msg = new AMQPMessage(
            $msgBody,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'timestamp' => time()
            ]
        );

        $channel->basic_publish($msg, 'ecommerce', 'order.created');
        $publicadas++;

        // Mostra progresso a cada 10 mensagens
        if ($i % 10 === 0 || $i === $quantidade) {
            echo "   ✓ $i/$quantidade mensagens publicadas\n";
        }
    }

    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    $throughput = $publicadas / $duration;

    echo "\n" . str_repeat("=", 70) . "\n";
    echo "📊 RESULTADO:\n";
    echo "   Total publicadas: $publicadas\n";
    echo "   Tempo total: " . round($duration, 3) . " segundos\n";
    echo "   Throughput: " . round($throughput, 2) . " msgs/segundo\n";
    echo str_repeat("=", 70) . "\n\n";

    echo "💡 Próximo passo:\n";
    echo "   Terminal 1: php bin/consumer-prefetch-1.php\n";
    echo "   Terminal 2: php bin/consumer-prefetch-10.php\n";
    echo "   Observe a diferença de velocidade!\n\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}