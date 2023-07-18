<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use kamermans\OAuth2\GrantType\ClientCredentials;
use kamermans\OAuth2\OAuth2Middleware;
use GuzzleHttp\HandlerStack;
use kamermans\OAuth2\Persistence\Laravel5CacheTokenPersistence;

class GuzzleOAuth2Service
{

    public static function getGuzzleOAuth2Client($oAuth2BaseUri, $reAuthConfig, $isRefreshToken = false) : Client {
        // need to cache this client for improving performance, in future
        $client = self::buildClient($oAuth2BaseUri, $reAuthConfig, $isRefreshToken);
        return $client;
    }

    private static function buildClient($oAuth2BaseUri, $reAuthConfig, $isRefreshToken) : Client
    {
        $reAuthClient = new Client([
            'base_uri' => $oAuth2BaseUri,
        ]);

        // this grant type is used to get access token
        $grantType = new ClientCredentials($reAuthClient, $reAuthConfig);

        // This grant type is used to get a new Access Token and Refresh Token when
        //  only a valid Refresh Token is available
        if ($isRefreshToken) {
            $refreshGrantType = new RefreshToken($reAuthClient, $reAuthConfig);
            $oauth = new OAuth2Middleware($grantType, $refreshGrantType);
        }
        else {
            $oauth = new OAuth2Middleware($grantType);
        }
        // use persistence to save OAuth2 tokens
        $oauth->setTokenPersistence(new Laravel5CacheTokenPersistence(Cache::store('file')), $oAuth2BaseUri);

        $stack = HandlerStack::create();
        $stack->push($oauth);

        // This is the normal Guzzle client that you use in your application
        return new Client([
            'handler' => $stack,
            'auth' => 'oauth',
        ]);
    }

}
