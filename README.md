# RabbitMQ E-commerce Study Project

Um projeto de estudo prático com RabbitMQ usando PHP puro, implementando uma arquitetura event-driven de um e-commerce.

## 📋 Estrutura do Projeto

```
.
├── php/bin/                      # Scripts executáveis
│   ├── migrate.php               # Cria as tabelas no MySQL
│   ├── setup-rabbitmq.php        # Setup do RabbitMQ (exchanges, filas, bindings)
│   └── simple-consumer.php       # Consumer básico que escuta a fila payment-processor ✅ FASE 1
├── php/                          # Scripts de teste/desenvolvimento
│   ├── test-order.php            # Publica eventos de pedido (test/demo)
│   └── src/
│       ├── Database/
│       │   └── Connection.php    # Conexão com MySQL (singleton)
│       ├── RabbitMQ/
│       │   └── Connection.php    # Conexão com RabbitMQ (singleton)
│       ├── Services/             # Serviços (OrderService, PaymentService, etc)
│       └── Consumers/            # Consumers para diferentes eventos
├── config/
│   └── bootstrap.php             # Carregamento de configurações e constantes
├── docker-compose.yml            # Containers (RabbitMQ + MySQL)
├── composer.json                 # Dependências PHP
└── .env                          # Variáveis de ambiente
```

## 🚀 Começando

### 1. Clonar o projeto
```bash
git clone git@github.com:rtavares00/EstudoDeRabbitMQComPHP.git
cd EstudoDeRabbitMQComPHP
```

### 2. Instalar dependências
```bash
composer install
```

### 3. Subir containers (RabbitMQ + MySQL)
```bash
docker-compose up -d
```

Aguarde ~15 segundos para os containers ficarem prontos. Você pode verificar com:
```bash
docker-compose ps
```

### 4. Rodar migrations (criar tabelas)
```bash
docker-compose exec php php bin/migrate.php
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
docker-compose exec php php bin/setup-rabbitmq.php
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
- **Exchanges**: `ecommerce` (tipo TOPIC, durable)
- **Queues**: payment-processor, notification-logs, inventory-processor, etc
- **Messages**: Número de mensagens em cada fila em tempo real
- **Consumers**: Quem está conectado escutando cada fila

---

## 🔴 FASE 1: Message Loop & Simple Consumer ✅ COMPLETO

Esta é a base do projeto. Você aprendeu como funciona o **loop de escuta** do RabbitMQ.

### 📖 Como Funciona o Message Loop

O consumer é basicamente um **loop infinito** que:

1. **Conecta ao RabbitMQ** e registra um callback
2. **Fica aguardando** por mensagens (bloqueado com `$channel->wait()`)
3. **Quando mensagem chega**, executa o callback
4. **Processa** a mensagem (lê dados, faz lógica)
5. **Confirma** com ACK (acknowledgement)
6. **Volta a aguardar** a próxima mensagem

**Diagrama Visual:**

```
┌─────────────────────────────────────┐
│   Iniciar Consumer                  │
├─────────────────────────────────────┤
│                                     │
│  1. Conectar ao RabbitMQ            │
│  2. Registrar callback (handler)    │
│  3. Chamar basic_consume()          │
│                                     │
│  ┌──────────────────────────────┐   │
│  │  LOOP INFINITO               │   │
│  │                              │   │
│  │  wait() ← BLOQUEADO AQUI     │   │
│  │    ↑ (esperando mensagem)    │   │
│  │    │                         │   │
│  │  [MENSAGEM CHEGA]            │   │
│  │    │                         │   │
│  │    ↓                         │   │
│  │  Executa callback()          │   │
│  │    - Processa dados          │   │
│  │    - ack() ← CONFIRMA        │   │
│  │    - Volta pro wait()        │   │
│  │                              │   │
│  └──────────────────────────────┘   │
│                                     │
│  4. Fechar conexão (CTRL+C)         │
│                                     │
└─────────────────────────────────────┘
```

### 💻 Testando o Simple Consumer

**Terminal 1 - Inicie o consumer:**
```bash
docker-compose exec php php bin/simple-consumer.php
```

Você verá:
```
======================================================================
🔴 SIMPLE CONSUMER - Escutando fila payment-processor
======================================================================

🔗 Conectando ao RabbitMQ...
✅ Conectado!

⚙️  Configurando QoS (prefetch = 1)...
✅ Configurado!

📡 Registrando consumer na fila 'payment-processor'...
✅ Consumer registrado!

⏳ Aguardando mensagens...
   (Pressione CTRL+C para parar)

```

**Terminal 2 - Publique um pedido:**
```bash
docker-compose exec php php test-order.php
```

**Resultado em Terminal 1:**
```
----------------------------------------------------------------------
📨 MENSAGEM RECEBIDA!

Body (bruto):
  {"order_id":"ORD-67a2f3c4","cliente_nome":"Rodrigo Tavares...

Dados decodificados:
  order_id: ORD-67a2f3c4
  cliente_nome: Rodrigo Tavares Ferreira
  valor: 7999.9
  timestamp: 2026-09-28 14:32:15
  itens: [ARRAY]

Metadados da mensagem:
  Routing Key: order.created
  Delivery Tag: 1
  Content Type: application/json

✅ Confirmando recebimento (ACK)...
----------------------------------------------------------------------
```

### 🔧 Entendendo o Código do Simple Consumer

**Arquivo:** `php/bin/simple-consumer.php`

**Seções principais:**

1. **Conectar:**
```php
$channel = Connection::getChannel();
```

2. **Configurar QoS (Quality of Service):**
```php
$channel->basic_qos(null, 1, null);
```
Isso significa: "processa apenas 1 mensagem por vez". Importante para não sobrecarregar.

3. **Definir callback (o que fazer quando mensagem chegar):**
```php
$callback = function ($msg) {
    $data = json_decode($msg->getBody(), true);
    // Lógica aqui
    $msg->ack();  // CONFIRMA que processou
};
```

4. **Registrar na fila:**
```php
$channel->basic_consume(
    'payment-processor',  // Qual fila escutar
    $consumerTag,         // Nome único deste consumer
    false,                // No auto-ack (manual)
    false,                // No exclusive
    false,                // No wait
    false,                // No nowait
    $callback             // Função que executa
);
```

5. **LOOP INFINITO (bloqueado):**
```php
while ($channel->is_open()) {
    try {
        $channel->wait(null, false, 5);  // ← BLOQUEADO AQUI
    } catch (AMQPTimeoutException $e) {
        continue;  // Timeout? Volta a esperar
    }
}
```

### ⚠️ Pontos Críticos Aprendidos

**1. Connection::close() quebra o consumer**
- Na versão inicial, `test-order.php` chamava `Connection::close()`
- Isso fechava a conexão para TODOS os processos (porque é singleton)
- **SOLUÇÃO:** Remover `Connection::close()` do publisher
- Cada processo PHP tem sua própria instância do singleton

**2. wait() precisa de timeout explícito**
- `$channel->wait()` sem parâmetros pode não bloquear corretamente
- Na versão 3.7.5 do php-amqplib, isso fazia o consumer "perder" mensagens
- **SOLUÇÃO:** Usar `$channel->wait(null, false, 5)` com timeout de 5 segundos
- Exception handling para `AMQPTimeoutException` garante que volta a esperar

**3. Consumer tag deve ser único**
- Não use string vazia `''`
- Use algo como `'simple-consumer-' . uniqid()`
- Permite múltiplos consumers registrados simultaneamente

**4. ACK (Acknowledgement) é essencial**
- `$msg->ack()` confirma que processou a mensagem
- Sem isso, a mensagem volta à fila (redelivery)
- Usar manualmente: `false` no parâmetro auto_ack de `basic_consume()`

---

## 📝 Próximas Fases (Em Desenvolvimento)

### Fase 2: Payment Service (Em breve)
- Criar `src/Services/PaymentService.php`
- Criar `src/Consumers/PaymentConsumer.php`
- Consumer que escuta `order.created`
- Simula processamento de pagamento
- Publica `payment.approved` ou `payment.failed`

### Fase 3: Notification Service (Em breve)
- Criar `src/Consumers/NotificationConsumer.php`
- Escuta `payment.approved` e `payment.failed`
- Envia "emails" (print no console por enquanto)

### Fase 4: Inventory Service (Em breve)
- Criar `src/Consumers/InventoryConsumer.php`
- Escuta `payment.approved`
- Atualiza estoque no banco

### Fase 5: Testes Integrados (Em breve)
- Enviar múltiplos pedidos
- Simular falhas e redelivery
- Monitorar fluxo via Management UI

---

## 🛠️ Comandos Úteis

### Entrar no container PHP interativo
```bash
docker-compose exec php bash
```

### Ver logs do RabbitMQ em tempo real
```bash
docker-compose logs rabbitmq -f
```

### Ver logs do MySQL em tempo real
```bash
docker-compose logs mysql -f
```

### Acessar MySQL direto
```bash
docker-compose exec mysql mysql -uroot -proot ecommerce
# Dentro do MySQL:
SELECT * FROM event_logs;
```

### Ver filas do RabbitMQ via CLI
```bash
docker-compose exec rabbitmq rabbitmqctl list_queues name messages consumers
```

### Ver consumers ativos
```bash
docker-compose exec rabbitmq rabbitmqctl list_consumers
```

### Limpar uma fila específica
```bash
docker-compose exec rabbitmq rabbitmqctl purge_queue payment-processor
```

### Parar containers (mas manter dados)
```bash
docker-compose down
```

### Resetar tudo (deletar volumes/dados)
```bash
docker-compose down -v
```

---

## 📚 Conceitos Principais

### Event-Driven Architecture
- **Producer** (test-order.php): Publica eventos quando algo acontece
- **Broker** (RabbitMQ): Recebe e distribui as mensagens
- **Consumer** (simple-consumer.php): Escuta e reage aos eventos
- **Vantagem:** Desacoplamento! Producer não sabe nem se importa com consumers

### AMQP (Advanced Message Queuing Protocol)
- Protocolo padrão usado por RabbitMQ
- **Exchange:** Recebe mensagens dos publishers
- **Queue:** Armazena mensagens até consumer processar
- **Binding:** Conecta exchange à queue com regra (routing key)
- **Routing Key:** Identificador do tipo de evento

### TOPIC Exchange
Usado neste projeto:
```
order.created       → payment-processor, notification-logs
payment.approved    → notification-logs, inventory-processor
payment.failed      → notification-logs
```

Com pattern matching:
- `order.*` captura todos os eventos de order
- `*.approved` captura todos os aprovados

### QoS (Quality of Service)
```php
$channel->basic_qos(null, 1, null);
```
Significa: "Não me mande mais de 1 mensagem por vez". Consumer processa uma, faz ACK, só depois recebe a próxima.

---

## 📞 Troubleshooting

### Consumer não recebe mensagens
1. Verifique se setup-rabbitmq.php foi executado
2. Verifique se a fila existe: `http://localhost:15672 → Queues`
3. Verifique se há bindings: `http://localhost:15672 → Exchanges → ecommerce`
4. Tente rodar test-order.php novamente
5. Verifique logs: `docker-compose logs rabbitmq`

### "Cannot start consumer without consumer_tag"
Não use string vazia no consumer_tag. Use algo dinâmico:
```php
$consumerTag = 'consumer-' . uniqid();
```

### "Connection timed out"
RabbitMQ talvez não iniciou. Aguarde 15-20 segundos:
```bash
docker-compose ps
docker logs rabbitmq-study
```

### MySQL connection refused
MySQL talvez não iniciou. Mesmo solução acima:
```bash
docker logs mysql-study
```

---

## 🎯 Resumo da Fase 1

✅ Entendeu como funciona o loop de escuta  
✅ Implementou um consumer simples e funcional  
✅ Testou com sucesso publicando e consumindo mensagens  
✅ Identificou e corrigiu problemas críticos  
✅ Aprendeu sobre ACK, QoS, consumer tags e timeout  

**Próximo passo:** Criar o Payment Service que reage ao evento `order.created` e publica seu próprio evento.

Bora lá! 🚀
