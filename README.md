# RabbitMQ E-commerce Study Project

Um projeto de estudo prático com RabbitMQ usando PHP puro, implementando uma arquitetura event-driven de um e-commerce.

## 📋 Estrutura do Projeto

```
.
├── php/bin/                      # Scripts executáveis
│   ├── migrate.php          # Cria as tabelas no MySQL
│   └── setup-rabbitmq.php   # Setup do RabbitMQ (exchanges, filas, bindings)
├── config/
│   └── bootstrap.php        # Carregamento de configurações
├── src/
│   ├── Database/
│   │   └── Connection.php   # Conexão com MySQL
│   ├── RabbitMQ/
│   │   └── Connection.php   # Conexão com RabbitMQ
│   ├── Services/            # Serviços (Order, Payment, etc)
│   └── Consumers/           # Consumers (listeners)
├── docker-compose.yml       # Containers (RabbitMQ + MySQL)
├── composer.json            # Dependências PHP
└── .env                     # Variáveis de ambiente
```

## 🚀 Começando

### 1. Clonar o projeto
```bash
cd /seu/caminho/projeto
```

### 2. Instalar dependências
```bash
composer install
```

### 3. Subir containers (RabbitMQ + MySQL)
```bash
docker-compose up -d
```

Aguarde ~15 segundos para os containers ficarem prontos.

### 4. Rodar migrations (criar tabelas)
```bash
php bin/migrate.php
```

Você deve ver:
```
✅ Table 'orders' created/verified
✅ Table 'payments' created/verified
✅ Table 'inventory' created/verified
✅ Table 'event_logs' created/verified
✅ Initial inventory data inserted
✨ Migrations completed successfully!
```

### 5. Setup do RabbitMQ
```bash
php bin/setup-rabbitmq.php
```

Você deve ver:
```
✅ Exchange 'ecommerce' created
✅ Queue 'payment-processor' created
... (mais filas)
✅ Bound 'payment-processor' to 'order.created'
... (mais bindings)
✨ RabbitMQ setup completed successfully!
```

## 🔗 Acessar RabbitMQ Management UI

Abra no navegador:
```
http://localhost:15672
```

- **Username**: guest
- **Password**: guest

Lá você consegue visualizar:
- Exchanges
- Queues
- Messages (quantas mensagens em cada fila)
- Consumers

## 📝 O que vamos fazer

### Fase 1: Order Service ✅ (Você tá aqui)
- Criar um endpoint POST que recebe pedidos
- Publicar evento `order.created` no RabbitMQ

### Fase 2: Payment Service
- Consumer que escuta `order.created`
- Simula processamento de pagamento
- Publica `payment.approved` ou `payment.failed`

### Fase 3: Notification Service
- Consumers que escutam `payment.approved` e `payment.failed`
- Envia "emails" (print no console)

### Fase 4: Inventory Service
- Consumer que escuta `payment.approved`
- Atualiza estoque

### Fase 5: Testes
- Enviar múltiplos pedidos
- Simular falhas
- Monitorar via Management UI

## 🛠️ Comandos Úteis

### Ver logs do RabbitMQ
```bash
docker logs rabbitmq-study -f
```

### Ver logs do MySQL
```bash
docker logs mysql-study -f
```

### Parar containers
```bash
docker-compose down
```

### Resetar tudo (deletar dados)
```bash
docker-compose down -v
```

### Acessar MySQL direto
```bash
mysql -h 127.0.0.1 -u ecommerce_user -p ecommerce
# Password: ecommerce_pass
```

## 📚 Próximos Passos

Cuando estiver pronto, avisa que vamos criar:
1. `src/Services/OrderService.php` - Cria pedidos
2. `src/Consumers/PaymentConsumer.php` - Processa pagamentos
3. E assim por diante...

Bora começar! 🚀
