<?php

require_once __DIR__ . '/../config/bootstrap.php';

use App\RabbitMQ\Connection;
use PhpAmqpLib\Message\AMQPMessage;

$channel = Connection::getChannel();

echo "🔧 Setting up RabbitMQ...\n\n";

// ========== EXCHANGE ==========
echo "📢 Creating exchange 'ecommerce' (TOPIC)...\n";
$channel->exchange_declare(
    'ecommerce',      // exchange name
    'topic',          // type
    false,            // passive
    true,             // durable
    false             // auto_delete
);
echo "✅ Exchange 'ecommerce' created\n\n";

// ========== QUEUES ==========
echo "📦 Creating queues...\n";

// Queue 1: Payment Processor
$channel->queue_declare(
    'payment-processor',
    false,    // passive
    true,     // durable
    false,    // exclusive
    false     // auto_delete
);
echo "✅ Queue 'payment-processor' created\n";

// Queue 2: Notification - Approved
$channel->queue_declare(
    'notification-approved',
    false, true, false, false
);
echo "✅ Queue 'notification-approved' created\n";

// Queue 3: Notification - Failed
$channel->queue_declare(
    'notification-failed',
    false, true, false, false
);
echo "✅ Queue 'notification-failed' created\n";

// Queue 4: Notification - Logs
$channel->queue_declare(
    'notification-logs',
    false, true, false, false
);
echo "✅ Queue 'notification-logs' created\n";

// Queue 5: Inventory Processor
$channel->queue_declare(
    'inventory-processor',
    false, true, false, false
);
echo "✅ Queue 'inventory-processor' created\n\n";

// ========== BINDINGS ==========
echo "🔗 Creating bindings...\n";

// payment-processor escuta order.created
$channel->queue_bind(
    'payment-processor',
    'ecommerce',
    'order.created'
);
echo "✅ Bound 'payment-processor' to 'order.created'\n";

// notification-approved escuta payment.approved
$channel->queue_bind(
    'notification-approved',
    'ecommerce',
    'payment.approved'
);
echo "✅ Bound 'notification-approved' to 'payment.approved'\n";

// notification-failed escuta payment.failed
$channel->queue_bind(
    'notification-failed',
    'ecommerce',
    'payment.failed'
);
echo "✅ Bound 'notification-failed' to 'payment.failed'\n";

// notification-logs escuta qualquer coisa relacionada a order (order.#)
$channel->queue_bind(
    'notification-logs',
    'ecommerce',
    'order.#'
);
echo "✅ Bound 'notification-logs' to 'order.#'\n";

// inventory-processor escuta payment.approved
$channel->queue_bind(
    'inventory-processor',
    'ecommerce',
    'payment.approved'
);
echo "✅ Bound 'inventory-processor' to 'payment.approved'\n\n";

Connection::close();

echo "✨ RabbitMQ setup completed successfully!\n";
echo "\n📊 RabbitMQ Management UI: http://localhost:15672\n";
echo "   Username: guest\n";
echo "   Password: guest\n";
