<?php

namespace App\Services;

namespace App\Services;

use Illuminate\Support\Facades\Log;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\ExpiredException;
use phpseclib3\Crypt\Crypt_RSA;
use phpseclib3\Math\BigInteger;
use phpseclib3\Crypt\PublicKeyLoader;

use App\Models\AwsCognitoPublicKey;

class CognitoSSOService
{
    public static function verifyToken(string $jwt): ?object
    {
        $publicKey = null;
        $kid = static::getKid($jwt);
        if ($kid) {
            $row = AwsCognitoPublicKey::find($kid);
            if ($row) {
                $publicKey = $row->public_key;
            } else {
                $publicKey = static::getPublicKey($kid);
                $row = AwsCognitoPublicKey::create(['kid' => $kid, 'public_key' => $publicKey]);
            }
        }
        Log::debug('Public key: ', array($publicKey));
        if ($publicKey) {
            try {
                $decodedTokenArray = JWT::decode($jwt, new Key($publicKey, 'RS256'));
                Log::debug('$decodedTokenArray: ', array($decodedTokenArray));
                return static::parseCognitoToken($decodedTokenArray);
            } catch (SignatureInvalidException $sie) {
                return (object)[
                    "email" => null,
                    "status" => "Invalid Token",
                    "isVerified" => false
                ];
            } catch (ExpiredException $ee) {
                return (object)[
                    "email" => null,
                    "status" => "Expired Token",
                    "isVerified" => false
                ];
            }
        }
        return (object)[
            "email" => null,
            "status" => "Expired Token",
            "isVerified" => false
        ];
    }

    private static function parseCognitoToken($decodedToken)
    {
        Log::debug('Decoded Token: ', array($decodedToken));
        //we pick the first token
        if (!isset($decodedToken)) {
            Log::debug('token not found: ', array($decodedToken));
            return (object)[
                "email" => null,
                "status" => "Invalid Token",
                "isVerified" => false
            ];
        }
        $email = $decodedToken->email;
        return (object)[
            "email" => $email,
            "status" => "Success",
            "isVerified" => true
        ];
    }

    private function extractKidFromToken(string $jwt): ?string
    {
        $tks = explode('.', $jwt);
        if (count($tks) === 3) {
            $header = JWT::jsonDecode(JWT::urlsafeB64Decode($tks[0]));
            if (isset($header->kid)) {
                return $header->kid;
            }
        }
        return null;
    }

    private static function getPublicKey(string $kid): ?string
    {
        //Get jwks from URL, because the jwks may change.
        $jwksUrl = sprintf(
            'https://cognito-idp.%s.amazonaws.com/%s/.well-known/jwks.json',
            env("AWS_COGNITO_REGION"),
            env("AWS_COGNITO_USER_POOL_ID")
        );
        $ch = curl_init($jwksUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
        ]);
        $jwks = curl_exec($ch);
        if ($jwks) {
            $json = json_decode($jwks, false);
            if ($json && isset($json->keys) && is_array($json->keys)) {
                foreach ($json->keys as $jwk) {
                    if ($jwk->kid === $kid) {
                        return static::jwkToPem($jwk);
                    }
                }
            }
        }
        return null;
    }

    private static function jwkToPem(object $jwk): ?string
    {
        if (isset($jwk->e, $jwk->n)) {
            return PublicKeyLoader::load([
                'e' => new BigInteger(JWT::urlsafeB64Decode($jwk->e), 256),
                'n' => new BigInteger(JWT::urlsafeB64Decode($jwk->n), 256)
            ]);
        }
        return null;
    }

    private static function getKid(string $jwt): ?string
    {
        $tks = explode('.', $jwt);
        if (count($tks) === 3) {
            $header = JWT::jsonDecode(JWT::urlsafeB64Decode($tks[0]));
            if (isset($header->kid)) {
                return $header->kid;
            }
        }
        return null;
    }
}

/*** Decoded Token is of form ***********
 *
 *[
{
"stdClass":
{
"at_hash": "EAUnVcKK01D88c3oAwD_YQ",
"sub": "57e690ce-7be3-4782-b73d-9ec506a2547a",
"cognito:groups":
[
"us-west-2_OO6ySVQQu_Dynafios"
],
"email_verified": false,
"iss": "https://cognito-idp.us-west-2.amazonaws.com/us-west-2_OO6ySVQQu",
"cognito:username": "dynafios_alberto.santos@dynafios.com",
"nonce": "MXa89BtnUtTJQ2bdKsMgYsxUr8Lf89qAVBiLBbDrFTc",
"origin_jti": "7167674c-13f6-414b-a7ac-ed848aa936d7",
"aud": "7mfk8nsqqv2gqrapo1hpoouhg1",
"identities":
[
{
"userId": "Alberto.Santos@dynafios.com",
"providerName": "Dynafios",
"providerType": "SAML",
"issuer": "https://sts.windows.net/b96613bd-a157-4fd5-8de4-069b78553dcc/",
"primary": "true",
"dateCreated": "1657913914004"
}
],
"token_use": "id",
"auth_time": 1680696548,
"exp": 1680700148,
"iat": 1680696548,
"jti": "6d79cc2d-626e-4be3-aeb0-e058f06b1d98",
"email": "Alberto.Santos@dynafios.com"
}
}
]
 ***************/
