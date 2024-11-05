<?php // Code within app\Helpers\Helper.php
/**
 * Not sure if this is necessary, but here's a placeholder for a KafkaHelper class
 */

namespace App\Helpers;

use App\Kafka\KafkaProducerService;


class KafkaHelper
{
    /**
     * Set a new message at Kafka
     * nOTE: NOT SURE why this is not working from CustomersController, so right now, it is just being ignored
     * @param mixed $topic
     * @param mixed $body
     * @param mixed $headers
     * @param mixed $id
     * @return void
     */
    public static function setKafkaMessage($topic, $body,$headers = [],$id=null) {
        $kafka = new KafkaProducerService();
        $kafka = $kafka->setTopic($topic);
        $body = (is_array($body))? json_encode($body) : $body;

        $kafka->send($body, $id, $headers);
    }
}
