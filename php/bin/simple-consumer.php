<?php

require_once __DIR__ . '/../config/bootstrap.php';

use App\RabbitMQ\Connection;
use PhpAmqpLib\Exception\AMQPTimeoutException;

echo "\n" . str_repeat("=", 70) . "\n";
echo "🔴 SIMPLE CONSUMER - Escutando fila payment-processor\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Conecta
    echo "🔗 Conectando ao RabbitMQ...\n";
    $channel = Connection::getChannel();
    echo "✅ Conectado!\n\n";

    // Configura prefetch
    echo "⚙️  Configurando QoS (prefetch = 1)...\n";
    $channel->basic_qos(null, 1, null);
    echo "✅ Configurado!\n\n";

    // Define o callback (função que executa quando chega mensagem)
    $callback = function ($msg) {
        echo "\n" . str_repeat("-", 70) . "\n";
        echo "📨 MENSAGEM RECEBIDA!\n\n";

        // Pega o conteúdo
        $body = $msg->getBody();
        echo "Body (bruto):\n";
        echo "  " . $body . "\n\n";

        // Decodifica JSON
        $data = json_decode($body, true);
        echo "Dados decodificados:\n";
        
        foreach ($data as $chave => $valor):
            if (is_array($valor)) {
                echo "  $chave: [ARRAY]\n";
            } else {
                echo "  $chave: $valor\n";
            }
        endforeach;

        echo "\nMetadados da mensagem:\n";
        echo "  Routing Key: " . $msg->getRoutingKey() . "\n";
        echo "  Delivery Tag: " . $msg->getDeliveryTag() . "\n";
        echo "  Content Type: " . $msg->get('content_type') . "\n";

        // Confirma que processou (ACK)
        echo "\n✅ Confirmando recebimento (ACK)...\n";
        $msg->ack();

        echo str_repeat("-", 70) . "\n";
    };

    // Registra pra escutar a fila
    echo "📡 Registrando consumer na fila 'payment-processor'...\n";
    $consumerTag = 'simple-consumer-' . uniqid();
    $channel->basic_consume(
        'payment-processor',  // Fila
        $consumerTag,    // Consumer tag (nome único)
        false,                // No local
        false,                // No auto ack (manual)
        false,                // No exclusive
        false,                // No wait
        $callback             // Função que executa
    );
    echo "✅ Consumer registrado!\n\n";

    // Entra no loop infinito
    echo "⏳ Aguardando mensagens...\n";
    echo "   (Pressione CTRL+C para parar)\n\n";

    while ($channel->is_open()) {
        try {
            // Timeout explícito: sem ele, wait() usa timeout=0, que na versão
            // instalada do php-amqplib (3.7.5) equivale a uma leitura quase
            // não-bloqueante e faz o consumer "perder" as mensagens em vez de
            // esperar por elas.
            $channel->wait(null, false, 5);
        } catch (AMQPTimeoutException $e) {
            // Nenhuma mensagem chegou dentro do timeout, volta a esperar.
            continue;
        }
    }

} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
} finally {
    Connection::close();
}