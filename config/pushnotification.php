<?php
/**
 * @see https://github.com/Edujugon/PushNotification
 */

return [
    'gcm' => [
        'priority' => 'normal',
        'dry_run' => false,
        'apiKey' => 'My_ApiKey',
    ],
    'fcm' => [
        'priority' => 'normal',
        'dry_run' => false,
        'apiKey' => 'AIzaSyCQMPgP2mXZvOrvUt7rXu5-_yOIemo64LA',
    ],
    'apn' => [
        'certificate' => __DIR__ . '/iosCertificates/certificate.pem',
        /*'passPhrase' => 'Trace',*/ //Optional
        'dry_run' => true,
    ],
];
