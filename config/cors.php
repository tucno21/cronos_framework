<?php

return [
    'allowed_origins' => ['*'],

    'allowed_methods' => ['*'],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'], //Response: encabezados que se pueden exponer para javascript en el cliente

    'supports_credentials' => false, //Response: si se permite el envio de credenciales

    'allowed_origins_patterns' => ['http://127.0.0.1:8090'], //Response: dominio permitidos para el envio de credenciales y registrar cookies
    // 'max_age' => 0,
];
