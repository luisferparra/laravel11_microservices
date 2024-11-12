<?php

namespace App\Kafka;

/**
 *
 * Documentation followed
 *
 * For Producers:
 *  https://github.com/anam-hossain/laravel-kafka-pub-example
 *
 * For Subscribers:
 *  https://github.com/anam-hossain/laravel-kafka-sub-example
 */

use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use Exception;
//use Junges\Kafka\Producers\Producer as ProducersProducer;

class KafkaConsumerService
{


    protected $consumer;

    protected $message;

    protected $messageReturn;


    public function __construct() {
        $this->consumer = new KafkaConsumer($this->getConfig());

    }



    /**
     * Function that will subscribe to a 1..n topics
     * @param string|array topic or topics (array)
     * @return
     */
    public function subscribeToTopics($topics) {
        $topic = (is_array($topics)) ? $topics : [$topics];
        $this->consumer->subscribe($topic);
    }

    public function getMessage() {
        return $this->message;
    }

    public function getMessageReturn() {
        return $this->messageReturn;
    }

    public function consume($args) {
        $this->message = $this->consumer->consume($args);
        switch ($this->message->err) {
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                return $this->message;
                break;
            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                $this->messageReturn = "No more messages; will wait for more";
                return false;
                break;
            case RD_KAFKA_RESP_ERR__TIMED_OUT:
                $this->messageReturn = "Timed out";

                return false;
                break;
            default:
                throw new Exception($this->message->errstr(), $this->message->err);
                break;
        }
    }
    /**
     *  Will generate a commit on the processed message
     * @param string $message
     * @return mixed true|string true if OK, string if error
     */
    public function messageCommit($message) {
        try {
            $this->consumer->commitAsync($message);
            return true;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Decode kafka message
     *
     * @param \RdKafka\Message $kafkaMessage
     * @return object
     */
    public function decodeKafkaMessage(Message $kafkaMessage)
    {
        $message = json_decode($kafkaMessage->payload);

        if (!empty($message->body) && is_string($message->body)) {
            $message->body = json_decode($message->body);
        } else return false;
        $message->topic = $kafkaMessage->topic_name;
        $message->key = $kafkaMessage->key;
        //$message->headers = $kafkaMessage->headers;
        return $message;
    }


    /**
     * Get kafka config
     *
     * @return \RdKafka\Conf
     */
    protected function getConfig()
    {
        $conf = new Conf();

        // Configure the group.id. All consumer with the same group.id will consume
        // different partitions.
        $conf->set('group.id', 'CUSTOMERS');

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
        $conf->set('auto.offset.reset', 'smallest');

        // Automatically and periodically commit offsets in the background
        $conf->set('enable.auto.commit', 'false');

        return $conf;
    }
}
