<?php

return [
    '403' => [
        'title'   => 'Forbidden',
        'message' => "Sorry, you don't have the necessary permissions to access this page."
    ],
    '404' => [
        "title"   => "Not Found",
        "message" => "Sorry, the page you were trying to view does not exist."
    ],
    "405" => [
        "title"   => "Method Not Allowed",
        "message" => "This method is not allowed for the current route."
    ],
    '500' => [
    	"title"   => "Internal Server Error",
    	"message" => "The server encountered an internal error and was unable to complete your request."
    ]
];
