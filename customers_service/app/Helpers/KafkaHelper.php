<?php // Code within app\Helpers\Helper.php
/**
 * Not sure if this is necessary, but here's a placeholder for a KafkaHelper class
 */

namespace App\Helpers;

use App\Kafka\KafkaProducerService;

use Illuminate\Support\Facades\Log;


use App\Models\Customers;


class KafkaHelper
{

    protected $responseMessage = "";

    protected $responseCodeId = 200;

    protected $responseKafkaStructure = ['success' => true, 'success_code' => 201, 'message' => 'OK'];


    /**
     * Set a new message at Kafka
     * @param mixed $topic
     * @param mixed $body
     * @param mixed $headers
     * @param mixed $id
     * @return void
     */
    public static function setKafkaMessage($topic, $body, $headers = [], $id = null)
    {
        $kafka = new KafkaProducerService();
        $kafka->setTopic($topic);
        $body = (is_array($body) || is_object($body)) ? json_encode($body) : $body;
        $kafka->send(json_encode($body), $id, $headers);
    }

    /**
     * Function that will process the recieved data of an auth created Customer
     *
     * @param array $body
     * @param array $headers
     * @return void
     */
    private function processCustomer($body, $headers = [])
    {
        $auth_id = $body['id'];
        $company_id = $body['user_type_id'];
        Log::info("Auth Id: $auth_id Company Id: $company_id");
        $customer = Customers::findOrFail($company_id);
        $customer->auth_created = 1;
        $customer->auth_id = $auth_id;
        $customer->auth_comment = $body['message'];
        $customer->auth_at = now();
        $customer->save();
    }

    /**
     *
     * Function that receives a Kafka message and processes it. Processing will depends on the field topic
     * @param mixed $message Kafka message with structure ['topic' => 'your_topic', 'body' => 'your_body', 'headers' => ['your_header' => 'your_value'],'id'=>id]
     * @return boolean
     */
    public function getKafkaMessageAndProcess($message)
    {
        /*$newTopic = env("KAFKA_TOPIC_AUTH_CREATE_COMPANY_RESPONSE");
        echo "New Topic: $newTopic";
        $kafka = new KafkaProducerService();
        $kafka->setTopic($newTopic);
        $kafka->send(json_encode(["RESULT TOPIC"=>"new topic","test"=>"blablabla"]), null, ["header"=>"bliblibli"]);

        return;
        */
        $topic = $message->topic;
        $body = $message->body;
        $headers = (array)$message->headers;
        Log::info("Topic: $topic");
        switch ($topic) {
            case env("KAFKA_TOPIC_AUTH_CREATE_USER_RESULT"):
                Log::info("estamos dentro del topic");
                $body = json_decode($body, true);
                $success = $body['success'];
                $success_code = (bool)$body['success_code'];
                if ($success) {

                    $user_type = $body['user_type'];
                    Log::info("userTupe: $user_type");
                    switch ($user_type) {
                        case 'COMPANY':
                            $this->processCustomer($body, $headers);
                            break;
                        default:
                            break;
                    }
                }
                break;
            default:
                break;
        }
    }

    /**
     * Function that will create a Kafka Response
     * @param string $topic
     * @param mixed $body
     * @param array $headers
     * @param mixed $id
     * @return void
     */
    private function responseKafka($topic, $body, $headers = [], $id = null)
    {
        $kafka = new KafkaProducerService();
        $body = (is_array($body) || is_object($body)) ? json_encode($body) : $body;
        $kafka->setTopic($topic);
        $kafka->send(json_encode($body), $id, $headers);
    }

    public function getResponseMessage()
    {
        return $this->responseMessage;
    }
    public function getResponseCodeId()
    {
        return $this->responseCodeId;
    }
}
