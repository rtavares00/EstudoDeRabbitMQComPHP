<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;

class Connection
{
    private static ?AMQPStreamConnection $connection = null;
    private static ?AMQPChannel $channel = null;

    public static function getConnection(): AMQPStreamConnection
    {
        if (self::$connection === null) {
            self::$connection = new AMQPStreamConnection(
                RABBITMQ_HOST,
                RABBITMQ_PORT,
                RABBITMQ_USER,
                RABBITMQ_PASSWORD,
                RABBITMQ_VHOST
            );
        }

        return self::$connection;
    }

    public static function getChannel(): AMQPChannel
    {
        if (self::$channel === null) {
            self::$channel = self::getConnection()->channel();
        }

        return self::$channel;
    }

    public static function close(): void
    {
        if (self::$channel !== null) {
            self::$channel->close();
            self::$channel = null;
        }

        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}
