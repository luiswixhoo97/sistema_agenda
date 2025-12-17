<?php

return [
    'webhook_secret' => env('WASENDERAPI_WEBHOOK_SECRET', ''),
    'webhook_route' => env('WASENDERAPI_WEBHOOK_ROUTE', '/wasender/webhook'),
    'webhook_signature_header' => env('WASENDERAPI_WEBHOOK_SIGNATURE_HEADER', 'x-webhook-signature'),
    'api_key' => env('WASENDERAPI_API_KEY', 'ae02434e1d46b604bbb3190cc9aed71f1c960b6ca26f4fd898800a4cb929e45b'),
    'personal_access_token' => env('WASENDERAPI_PERSONAL_ACCESS_TOKEN', ''),
]; 

