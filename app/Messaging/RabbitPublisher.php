<?php

namespace App\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitPublisher
{
    public function publish(string $routingKey, array $data, string $messageId, string $type): void
    {
        $c = config('rabbitmq');

        // Satu koneksi per pesan: cukup untuk skala proyek ini.
        $koneksi = new AMQPStreamConnection($c['host'], $c['port'], $c['user'], $c['password'], $c['vhost']);
        $channel = $koneksi->channel();

        try {
            // durable = true: exchange bertahan walau RabbitMQ dinyalakan ulang.
            $channel->exchange_declare($c['exchange'], 'topic', false, true, false);

            $pesan = new AMQPMessage(json_encode($data, JSON_UNESCAPED_UNICODE), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id' => $messageId,
                'type' => $type,
                'timestamp' => time(),
            ]);

            $channel->basic_publish($pesan, $c['exchange'], $routingKey);
        } finally {
            $channel->close();
            $koneksi->close();
        }
    }
}
