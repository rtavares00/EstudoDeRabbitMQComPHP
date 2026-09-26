<?php

require_once __DIR__ . '/../config/bootstrap.php';

use App\Database\Connection;

$pdo = Connection::getConnection();

echo "🔧 Running migrations...\n";

// Create orders table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        cliente_nome VARCHAR(255) NOT NULL,
        valor DECIMAL(10, 2) NOT NULL,
        status VARCHAR(50) DEFAULT 'pendente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "✅ Table 'orders' created/verified\n";

// Create payments table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS payments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        status VARCHAR(50) DEFAULT 'pendente',
        amount DECIMAL(10, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "✅ Table 'payments' created/verified\n";

// Create inventory table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS inventory (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_name VARCHAR(255) NOT NULL,
        quantity INT DEFAULT 100,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "✅ Table 'inventory' created/verified\n";

// Create logs table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS event_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        event_name VARCHAR(255) NOT NULL,
        event_data JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "✅ Table 'event_logs' created/verified\n";

// Insert some initial inventory
try {
    $pdo->exec("INSERT INTO inventory (product_name, quantity) VALUES ('Iphone', 100)");
    $pdo->exec("INSERT INTO inventory (product_name, quantity) VALUES ('Notebook', 50)");
    $pdo->exec("INSERT INTO inventory (product_name, quantity) VALUES ('Monitor', 75)");
    echo "✅ Initial inventory data inserted\n";
} catch (\Exception $e) {
    echo "ℹ️  Inventory data already exists\n";
}

Connection::close();

echo "\n✨ Migrations completed successfully!\n";
