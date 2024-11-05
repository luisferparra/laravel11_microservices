<?php

return [
    "consumers_topics" => [
        env("KAFKA_TOPIC_AUTH_CREATE_COMPANY_OK"),

    ],
    "producers_topics" => [
        "topic_auth_create_access" => env("KAFKA_TOPIC_AUTH_CREATE_USER")
    ]
];
