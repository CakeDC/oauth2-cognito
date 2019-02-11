<?php
/**
 * Copyright 2009 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009 - 2019, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\OAuth2\Client\Provider;

use League\OAuth2\Client\Exception\HostedDomainException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Cognito extends AbstractProvider
{
    use BearerAuthorizationTrait;

    const BASE_COGNITO_URL = 'https://%s.auth.us-west-2.amazoncognito.com%s';
    /**
     * @var array List of scopes that will be used for authentication.
     *
     * Valid scopes: phone, email, openid, aws.cognito.signin.user.admin, profile
     * Defaults to email, openid
     *
     */
    protected $scopes = [];

    /**
     * @var string If set, it will replace default AWS Cognito urls.
     * @link https://developers.google.com/identity/protocols/OpenIDConnect#authenticationuriparameters
     */
    protected $hostedDomain;
    
    /**
     * @var string If set, it will be added to AWS Cognito urls.
     * @link https://developers.google.com/identity/protocols/OpenIDConnect#authenticationuriparameters
     */
    protected $cognitoDomain;

    /**
     * @param array $options
     * @param array $collaborators
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);
        
        if (!empty($options['hostedDomain'])) {
            $this->hostedDomain = $options['hostedDomain'];
        } elseif (!empty($options['cognitoDomain'])) {
            $this->cognitoDomain = $options['cognitoDomain'];
        } else {
            throw new \InvalidArgumentException(
                'Neither "cognitoDomain" nor "hostedDomain" options are set. Please set one of them.'
            );
        }

        if (!empty($options['scope'])) {
            $this->scopes = explode($this->getScopeSeparator(), $options['scope']);
        }
    }

    /**
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @return string
     */
    public function getHostedDomain()
    {
        return $this->hostedDomain;
    }

    /**
     * @param string $hostedDomain
     */
    public function setHostedDomain($hostedDomain)
    {
        $this->hostedDomain = $hostedDomain;
    }

    /**
     * @return string
     */
    public function getCognitoDomain()
    {
        return $this->cognitoDomain;
    }

    /**
     * @param string $cognitoDomain
     */
    public function setCognitoDomain($cognitoDomain)
    {
        $this->cognitoDomain = $cognitoDomain;
    }


    /**
     * Returns the url for given action
     *
     * @param $action
     * @return string
     */
    private function getCognitoUrl($action)
    {
        return !empty($this->hostedDomain) ? $this->hostedDomain . $action :
            sprintf(self::BASE_COGNITO_URL, $this->cognitoDomain, $action);
    }


    public function getBaseAuthorizationUrl()
    {
        return $this->getCognitoUrl('/authorize');
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getCognitoUrl('/token');
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getCognitoUrl('/oauth2/userInfo');
    }

    protected function getAuthorizationParameters(array $options)
    {
        $scopes = array_merge($this->getDefaultScopes(), $this->scopes);

        if (!empty($options['scope'])) {
            $scopes = array_merge($scopes, $options['scope']);
        }

        $options['scope'] = array_unique($scopes);

        return parent::getAuthorizationParameters($options);
    }

    protected function getDefaultScopes()
    {
        return ['openid', 'email'];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (empty($data['error'])) {
            return;
        }
        
        $code = 0;
        $error = $data['error'];
        
        throw new IdentityProviderException($error, $code, $data);
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        $user = new CognitoUser($response);

        return $user;
    }
}
