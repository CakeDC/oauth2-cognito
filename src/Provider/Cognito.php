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

    const BASE_COGNITO_URL = 'https://%s.auth.%s.amazoncognito.com%s';
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
     */
    protected $hostedDomain;
    
    /**
     * @var string If set, it will be added to AWS Cognito urls.
     */
    protected $cognitoDomain;

    /**
     * @var string If set, it will be added to AWS Cognito urls.
     */
    protected $region;

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
        } elseif (!empty($options['cognitoDomain']) && !empty($options['region'])) {
            $this->cognitoDomain = $options['cognitoDomain'];
            $this->region = $options['region'];
        } else {
            throw new \InvalidArgumentException(
                'Neither "cognitoDomain" and "region" nor "hostedDomain" options are set. Please set one of them.'
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
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
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
            sprintf(self::BASE_COGNITO_URL, $this->cognitoDomain, $this->region, $action);
    }

    /**
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->getCognitoUrl('/authorize');
    }

    /**
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getCognitoUrl('/token');
    }

    /**
     * @param AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getCognitoUrl('/oauth2/userInfo');
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getAuthorizationParameters(array $options)
    {
        $scopes = array_merge($this->getDefaultScopes(), $this->scopes);

        if (!empty($options['scope'])) {
            $scopes = array_merge($scopes, $options['scope']);
        }

        $options['scope'] = array_unique($scopes);

        return parent::getAuthorizationParameters($options);
    }

    /**
     * @return array
     */
    protected function getDefaultScopes()
    {
        return ['openid', 'email'];
    }

    /**
     * @return string
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    /**
     * @param ResponseInterface $response
     * @param array|string $data
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (empty($data['error'])) {
            return;
        }
        
        $code = 0;
        $error = $data['error'];
        
        throw new IdentityProviderException($error, $code, $data);
    }

    /**
     * @param array $response
     * @param AccessToken $token
     * @return CognitoUser|\League\OAuth2\Client\Provider\ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        $user = new CognitoUser($response);

        return $user;
    }
}
