<?php

/**
 * Helper of Kafka
 */

namespace App\Helpers;

use App\Kafka\KafkaProducerService;
use App\Helpers\UsersHelper;

class KafkaHelper
{

    protected $responseMessage = "";

    protected $responseCodeId = 200;

    protected $responseKafkaStructure = ['sucess' => true, 'success_code' => 201, 'message' => 'OK'];

    const _USER_ERROR_ALREADY_EXISTS = "USER already Exists";
    const _USER_CREATED_OK = "USER Created";
    const _USER_ERROR_INSERT = "ERROR in Inserting Data";
    const _USER_KAFKA_ERROR = "ERROR Creating kafka response";
    const _USER_KAFKA_TOPIC_NOT_FOUND = "ERROR TOPIC not FOUND";

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
        switch ($topic) {
            case env("KAFKA_TOPIC_AUTH_CREATE_USER"):
                $user = $this->setUserStructure($body);
                $newTopic = env("KAFKA_TOPIC_AUTH_CREATE_USER_RESULT");

                if ($user !== false) {
                    $idAuth = $user->id;
                    if (!empty($idAuth) && !empty($headers) && isset($headers['return_to_producer']) && $headers['return_to_producer']) {
                        //If we must create a new Kafka message with the result of the action
                        try {
                            $newMessage = array_merge($this->responseKafkaStructure, ['user_type' => $user->user_type, 'id' => $idAuth, 'user_type_id' => $user->user_type_id]);
                            $newHeader = ['return_to_producer' => false, 'id' => $idAuth];
                            $this->responseKafka($newTopic, $newMessage, $newHeader, $idAuth);
                            /*$kafka = new KafkaProducerService();
                            $kafka->setTopic($newTopic);
                            $kafka->send(json_encode($newMessage), $idAuth, $newHeader);
                            */
                            $this->responseMessage = self::_USER_CREATED_OK;
                            $this->responseCodeId = 201;
                            return true;
                        } catch (\Exception $e) {
                            $resp = $this->responseKafkaStructure;
                            $resp['success'] = false;
                            $resp['message'] =  self::_USER_KAFKA_ERROR . " " . $e->getMessage();
                            $resp['success_code'] = 501;
                            $resp['original_request'] = [
                                'body' => (array)$body,
                                'topic' => $topic,
                                'headers' => $headers
                            ];
                            $this->responseKafka($newTopic, $resp, ['return_to_producer' => false]);
                            $this->responseMessage = self::_USER_KAFKA_ERROR . " " . $e->getMessage();
                            $this->responseCodeId = 501;
                            return false;
                        }

                        //self::setKafkaMessage($newTopic, $newMessage, $newHeader);
                    } else {



                        $resp = $this->responseKafkaStructure;
                        $resp['success'] = false;
                        $resp['message'] =  self::_USER_ERROR_INSERT;
                        $resp['success_code'] = 522;
                        $resp['original_request'] = [
                            'body' => (array)$body,
                            'topic' => $topic,
                            'headers' => $headers
                        ];
                        $this->responseMessage = self::_USER_ERROR_INSERT;
                        $this->responseCodeId = 522;
                        if (!empty($headers) && isset($headers['return_to_producer']) && $headers['return_to_producer']) {
                            $this->responseKafka($newTopic, $resp, ['return_to_producer' => false]);
                        }
                        return false;
                    }
                } else {
                    $resp = $this->responseKafkaStructure;
                    $resp['success'] = false;
                    $resp['message'] =  self::_USER_KAFKA_ERROR . " " . $this->responseMessage;
                    $resp['success_code'] = 501;
                    $resp['original_request'] = [
                        'body' => (array)$body,
                        'topic' => $topic,
                        'headers' => $headers
                    ];
                    $this->responseKafka($newTopic, $resp, ['return_to_producer' => false]);

                    return false;
                }
                return true;
                break;
            default:
                $resp = $this->responseKafkaStructure;
                $resp['success'] = false;
                $resp['message'] =  self::_USER_ERROR_INSERT;
                $resp['success_code'] = 405;
                $resp['original_request'] = [
                    'body' => (array)$body,
                    'topic' => $topic,
                    'headers' => $headers
                ];
                $this->responseKafka(env('KAFKA_TOPIC_AUTH_DEFAULT_ERROR'), $resp, ['return_to_producer' => false]);
                $this->responseMessage = self::_USER_KAFKA_TOPIC_NOT_FOUND;
                $this->responseCodeId = 405;
                return false;
                break;
        }
    }

    public function getResponseMessage()
    {
        return $this->responseMessage;
    }
    public function getResponseCodeId()
    {
        return $this->responseCodeId;
    }

    /**
     *
     * Function that will receive a certain data structure and will prepare it as required for creating an user/authorization
     * @param mixed $data
     * @return boolean|\App\Models\User
     */
    private function setUserStructure($data)
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        } elseif (is_object($data)) {
            $data = (array)$data;
        }

        $data['email'] = $email = strtolower(trim($data['email']));
        /**
         * First at all, let's check if email already exists. If it exists, we can create the access
         */
        $userExists = UsersHelper::getUserExists($email);
        if ($userExists) {
            $this->responseMessage = self::_USER_ERROR_ALREADY_EXISTS;
            $this->responseCodeId = 422;
            return false;
        }

        $role = isset($data['role']) ? $data['role'] : null;
        $permissions = isset($data['permissions']) ? $data['permissions'] : [];
        $id = isset($data['id']) ? $data['id'] : null;

        unset($data['role']);
        unset($data['permissions']);
        unset($data['id']);
        unset($data['created_at']);
        unset($data['updated_at']);
        unset($data['nif']);

        //$data['password'] = UsersHelper::setPasswordEncrypt($data['password']);
        if (!empty($id)) {
            $data['user_type_id'] = $id;
        }




        $userResponse = UsersHelper::createUser($data, $role, $permissions);
        if (is_string($userResponse)) {

            $this->responseMessage = $userResponse;
            $this->responseCodeId = 502;
            return false;
        }
        return $userResponse;
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
}
