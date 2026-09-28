<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\RabbitMQ\Connection;
use PhpAmqpLib\Message\AMQPMessage;

echo "\n" . str_repeat("=", 60) . "\n";
echo "📦 ORDER TEST - Criando e publicando pedido\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // Conecta ao RabbitMQ
    echo "🔗 Conectando ao RabbitMQ...\n";
    $channel = Connection::getChannel();
    echo "✅ Conectado!\n\n";

    // Cria um pedido fake
    $order = [
        'order_id' => uniqid('ORD-'),
        'cliente_nome' => 'Rodrigo Tavares Ferreira',
        'valor' => 7999.90,
        'timestamp' => date('Y-m-d H:i:s'),
        'itens' => [
            ['produto' => 'Desktop Computer', 'quantidade' => 1, 'preco' => 7999.90]
        ]
    ];

    echo "📝 Pedido criado:\n";
    echo json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // Cria a mensagem
    $msgBody = json_encode($order);
    $msg = new AMQPMessage(
        $msgBody,
        [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]
    );

    // Publica no exchange
    echo "📤 Publicando evento 'order.created' no RabbitMQ...\n";
    $channel->basic_publish($msg, 'ecommerce', 'order.created');
    echo "✅ Evento publicado com sucesso!\n\n";

    echo "📊 Informações da mensagem:\n";
    echo "   Exchange: ecommerce\n";
    echo "   Routing Key: order.created\n";
    echo "   Tamanho: " . strlen($msgBody) . " bytes\n";
    echo "   Durable: Sim (DELIVERY_MODE_PERSISTENT)\n\n";

    echo "🎯 Filas que devem receber esta mensagem:\n";
    echo "   • payment-processor (binding: order.created)\n";
    echo "   • notification-logs (binding: order.#)\n\n";

    Connection::close();

    echo str_repeat("=", 60) . "\n";
    echo "✨ Teste concluído com sucesso!\n";
    echo str_repeat("=", 60) . "\n\n";

    echo "💡 Próximos passos:\n";
    echo "   1. Acesse http://localhost:15672\n";
    echo "   2. Vá em 'Queues'\n";
    echo "   3. Verifique as mensagens em 'payment-processor' e 'notification-logs'\n";
    echo "   4. Execute novamente para criar mais pedidos\n\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}