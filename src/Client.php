<?php

namespace Tagplus;

use GuzzleHttp;
use kamermans\OAuth2\Persistence\NullTokenPersistence;
use kamermans\OAuth2\Persistence\TokenPersistenceInterface;
use kamermans\OAuth2\OAuth2Subscriber;
use kamermans\OAuth2\GrantType\RefreshToken;
use kamermans\OAuth2\GrantType\AuthorizationCode;

class Client extends GuzzleHttp\Client
{
    const BASE_URL = 'https://api.tagplus.com.br';

    private $credentials;
    private $config;
    private $authClient;
    private $authMiddleware;
    private $tokenPersistence;

    public static function getAuthorizationUrl(
        $client_id, 
        array $scope, 
        $redirect_uri = ''
    ) {
        $url = sprintf('%s?response_type=code&client_id=%s&scope=%s',
            // Authorization URL
            'https://apidoc.tagplus.com.br/authorize',
            // Parameters (query string)
            $client_id,
            implode($scope, ' ')
        );

        // redirect_uri is optional
        if ($redirect_uri != '') {
            $url .= "&redirect_uri=$redirect_uri";
        }

        return $url;
    }

    public static function getAccessToken(
        $config, 
        $tokenPersistence = null,
        $authClient = null
    ) {
        if ($authClient === null) {
            $authClient = new GuzzleHttp\Client([
                'base_url' => self::BASE_URL . '/oauth2/token',
            ]);
        }

        if ($tokenPersistence === null) {
            $tokenPersistence = new NullTokenPersistence;
        }

        // grant type used to retrieve the access token
        $grant = new AuthorizationCode(
            $authClient, 
            $config + ['code' => $_GET['code']]
        );

        $authMiddleware = new OAuth2Subscriber($grant);
        
        $authMiddleware->setTokenPersistence($tokenPersistence);

        // acquire access token
        $authMiddleware->getAccessToken();

    }

    public function __construct(
        array $credentials = [], 
        array $config = [], 
        TokenPersistenceInterface $tokenPersistence = null,
        GuzzleHttp\ClientInterface $authClient = null
     ) {

        $this->credentials = $credentials;

        if ($authClient === null) {
            $this->authClient = new GuzzleHttp\Client([
                'base_url' => self::BASE_URL . '/oauth2/token',
            ]);
        } else {
            $this->authClient = $authClient;
        }

        if ($tokenPersistence === null) {
            $this->tokenPersistence = new NullTokenPersistence;
        } else {
            $this->tokenPersistence = $tokenPersistence;
        }
        
        $defaults = [
            'base_url' => self::BASE_URL,
            'defaults' => [
                'headers' => []
            ]
        ]; 
        $this->config = array_merge_recursive($defaults, $config);

        
        $this->validateAuth();
        $this->configureAuth();

        $this->config = array_merge_recursive(
            $this->config, 
            [
                'defaults' => [
                    // add headers required by API
                    'headers' => [
                            'x-api-version' => '2.0'
                    ]
                ]
            ]
        );

        if ($this->auth === 'oauth') {
            // enable oauth2 handler
            $this->config['defaults']['auth'] = 'oauth';
        }
        
        parent::__construct($this->config);
        
        if ($this->auth === 'oauth') {
            // add middleware that add the auth header AND reobtain token in case of expiration
            $this->getEmitter()->attach($this->authMiddleware);
        }

    }

    private function validateAuth()
    {
        if (!$this->credentials) {
            throw new \InvalidArgumentException("You must send an apikey or client_id/client_secret", 1);
        } elseif ( ! (
            isset($this->credentials['apikey']) XOR 
                (
                    isset($this->credentials['client_id']) AND 
                    isset($this->credentials['client_secret'])
                )
            )
        ) {
            throw new \InvalidArgumentException("You must use or apikey or oauth2 (client_id/client_secret), but not both", 2);
        }

        if (isset($this->credentials['apikey'])) {
            $this->auth = 'apikey';
        } else {
            $this->auth = 'oauth';
        }
    }
        
    private function configureAuth()
    {
        if ($this->auth === 'apikey') {
            $this->config = array_merge_recursive(
                $this->config,
                [
                    'defaults' => [
                        'headers' => [
                            'apikey' => $this->credentials['apikey']
                        ]
                    ]
               ]       
            );
        
        } elseif ($this->auth === 'oauth') {
        
            if ($this->authClient === null) {
                $this->authClient = new GuzzleHttp\Client([
                    'base_url' => self::BASE_URL . '/oauth2/token',
                ]);
            }

            $grant = new RefreshToken($this->authClient, [
                'client_id' => $this->credentials['client_id'],
                'client_secret' => $this->credentials['client_secret'],
            ]);

            $this->authMiddleware = new OAuth2Subscriber(
                // WORKAROUND: this will never be used!
                $grant,
                // this will be used
                $grant
            );

            $this->authMiddleware->setTokenPersistence(
                $this->tokenPersistence
            );
            
        }
        
    }

}