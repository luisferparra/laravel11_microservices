<?php

return [
    "consumers_topics" => [
        env("KAFKA_TOPIC_AUTH_CREATE_USER_RESULT"),
        env("KAFKA_TOPIC_AUTH_DEFAULT_ERROR"),

    ],
    "producers_topics" => [
        "topic_auth_create_access" => env("KAFKA_TOPIC_AUTH_CREATE_USER")
    ]
];
