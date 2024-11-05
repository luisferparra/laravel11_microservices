<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;


use App\Helpers\KafkaHelper;
use App\Kafka\KafkaProducerService;

use App\Models\Customers;

class CustomersController extends Controller
{
    /**
     * Function that will return the list of Acdtive Customers
     */
    public function actionCustomersList()
    {
        $data = Customers::where('status', 1)->orderBy("name")->get();
        return response()->json($data, 200);
    }

    /**
     * function that will create a Customer
     */
    public function actionCreateCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'legal_name' => "string|max:510",
            'email' => 'required|string|email|max:255|unique:customers,email',
            'nif' => 'required|string|unique:customers,nif',
            'create_access' => 'required|boolean', // Asegúrate de que sea 0 o 1
            'password' => 'required_if:create_access,1|string|min:6', // Requerido solo si create_access es 1
            'role' => 'required_if:create_access,1|string|in:SuperAdmin,User,CompanyAdmin', // Requerido solo si create_access es 1, y debe ser uno de los valores definidos
            'permissions' => 'required_if:create_access,1|array', // Requerido solo si create_access es 1
            'permissions.*' => 'string|in:read,write,delete,execute'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), Response::HTTP_BAD_REQUEST);
        }

        $customer = Customers::create([
            "name" => ucwords(strtolower($request->get('name'))),
            'legal_name' => (!empty($request->get("legal_name")) ? ucwords(strtolower($request->get("legal_name"))) : ""),
            'email' => strtolower($request->get('email')),
            'nif' => strtoupper($request->get('nif'))
        ]);
        $id = $customer->id;

        /*$res= Kafka::publish('broker')->onTopic("my-new-topic")->withMessage($message);
       $res->send();
       */




       $kafka = new KafkaProducerService();
        $kafka->setTopic(env("KAFKA_TOPIC_AUTH_CREATE_USER"));

        $kafkaBody = json_decode(json_encode($customer), true);
        unset($kafkaBody['legal_name']);
        $kafkaBody['user_type'] = "COMPANY";
        $kafkaBody['password'] = $request->get('password');
        $kafkaBody['role'] = $request->get('role');
        $kafkaBody['permissions'] = $request->get('permissions');
        $kafkaHeader = ['id' => $id, 'return_to_producer' => true];
        $kafka->send(json_encode($kafkaBody), $id, $kafkaHeader);
           //KafkaHelper::setKafkaMessage(env("KAFKA_TOPIC_AUTH_CREATE_USER"), $kafkaBody, $kafkaHeader, $id);
        return response()->json($customer, Response::HTTP_CREATED);
    }
}
