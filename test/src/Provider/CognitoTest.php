<?php

namespace CakeDC\OAuth2\Client\Test\Provider;

use CakeDC\OAuth2\Client\Provider\Cognito;
use CakeDC\OAuth2\Client\Provider\CognitoUser;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use Mockery;
use PHPUnit\Framework\TestCase;

class CognitoTest extends TestCase
{
    use QueryBuilderTrait;

    /**
     * @var Cognito
     */
    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new \CakeDC\OAuth2\Client\Provider\Cognito([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'cognitoDomain' => 'mock_cognito_domain',
            'region' => 'mock_region'
        ]);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testNoHostedDomainNorCognitoDomainNorRegion()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage(
            'Neither "cognitoDomain" and "region" nor "hostedDomain" options are set. Please set one of them.'
        );
        $provider = new \CakeDC\OAuth2\Client\Provider\Cognito([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function testNoHostedDomainNorCognitoDomainButRegion()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage(
            'Neither "cognitoDomain" and "region" nor "hostedDomain" options are set. Please set one of them.'
        );
        $provider = new \CakeDC\OAuth2\Client\Provider\Cognito([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'region' => 'mock_region'
        ]);
    }

    public function testNoHostedDomainNorRegionButCognitoDomain()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage(
            'Neither "cognitoDomain" and "region" nor "hostedDomain" options are set. Please set one of them.'
        );
        $provider = new \CakeDC\OAuth2\Client\Provider\Cognito([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'cognitoDomain' => 'mock_cognito_domain'
        ]);
    }

    public function testSetScopeInConfig()
    {
        $provider = new \CakeDC\OAuth2\Client\Provider\Cognito([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'hostedDomain' => 'mock_hosted_domain',
            'scope' => 'test-scope test-scope-2'
        ]);
        $this->assertEquals(['test-scope', 'test-scope-2'], $provider->getScopes());
    }

    public function testSetHostedDomainInConfig()
    {
        $host = uniqid();

        $provider = new \CakeDC\OAuth2\Client\Provider\Cognito([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'hostedDomain' => $host
        ]);

        $this->assertEquals($host, $provider->getHostedDomain());
    }

    public function testSetHostedDomainAfterConfig()
    {
        $host = uniqid();

        $this->provider->setHostedDomain($host);

        $this->assertEquals($host, $this->provider->getHostedDomain());
    }

    public function testSetCognitoDomainAfterConfig()
    {
        $host = uniqid();

        $this->provider->setCognitoDomain($host);

        $this->assertEquals($host, $this->provider->getCognitoDomain());
    }

    public function testSetRegionAfterConfig()
    {
        $region = uniqid();

        $this->provider->setRegion($region);

        $this->assertEquals($region, $this->provider->getRegion());
    }

    public function testSetCognitoDomainAndRegionInConfig()
    {
        $host = uniqid();
        $region = uniqid();

        $provider = new \CakeDC\OAuth2\Client\Provider\Cognito([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'cognitoDomain' => $host,
            'region' => $region
        ]);

        $this->assertEquals($host, $provider->getCognitoDomain());
        $this->assertEquals($region, $provider->getRegion());
    }

    public function testScopes()
    {
        $scopeSeparator = ' ';
        $options = ['scope' => [uniqid(), uniqid()]];
        $query = ['scope' => implode($scopeSeparator, $options['scope'])];
        $url = $this->provider->getAuthorizationUrl($options);
        $query['scope'] = 'openid email ' . $query['scope'];
        $encodedScope = $this->buildQueryString($query);
        $this->assertStringContainsString($encodedScope, $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response
            ->shouldReceive('getBody')
            ->andReturn(
                '{"access_token":"mock_access_token","id_token": "mock_access_token", "token_type": "Bearer"}'
            );
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $userId = rand(1000, 9999);
        $zoneinfo = uniqid();
        $website = uniqid();
        $address = uniqid();
        $birthdate = uniqid();
        $gender = uniqid();
        $profile = uniqid();
        $preferredUsername = uniqid();
        $locale = uniqid();
        $givenName = uniqid();
        $middleName = uniqid();
        $picture = uniqid();
        $name = uniqid();
        $nickname = uniqid();
        $phoneNumber = uniqid();
        $familyName = uniqid();
        $email = uniqid();
        $username = uniqid();


        $postResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $postResponse
            ->shouldReceive('getBody')
            ->andReturn(
                '{"access_token":"mock_access_token","id_token": "mock_access_token", "token_type": "Bearer"}'
            );
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $userResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $userResponse
            ->shouldReceive('getBody')
            ->andReturn(
                '{"sub":"' . $userId . '","zoneinfo":"' . $zoneinfo . '","website":"' . $website . '",
                "address":"' . $address . '","birthdate":"' . $birthdate . '","email_verified":"true",
                "gender":"' . $gender . '","profile":"' . $profile . '","phone_number_verified":"true",
                "preferred_username":"' . $preferredUsername . '","locale":"' . $locale . '",
                "given_name":"' . $givenName . '","middle_name":"' . $middleName . '","picture":"' . $picture . '",
                "name":"' . $name . '","nickname":"' . $nickname . '","phone_number":"' . $phoneNumber . '",
                "family_name":"' . $familyName . '","email":"' . $email . '","username":"' . $username . '"}'
            );
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        /**
         * @var CognitoUser $user
         */
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($userId, $user->toArray()['sub']);
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($name, $user->toArray()['name']);
        $this->assertEquals($zoneinfo, $user->getZoneinfo());
        $this->assertEquals($zoneinfo, $user->toArray()['zoneinfo']);
        $this->assertEquals($website, $user->getWebsite());
        $this->assertEquals($website, $user->toArray()['website']);
        $this->assertEquals($address, $user->getAddress());
        $this->assertEquals($address, $user->toArray()['address']);
        $this->assertEquals($birthdate, $user->getBirthdate());
        $this->assertEquals($birthdate, $user->toArray()['birthdate']);
        $this->assertEquals($gender, $user->getGender());
        $this->assertEquals($gender, $user->toArray()['gender']);
        $this->assertEquals($profile, $user->getProfile());
        $this->assertEquals($profile, $user->toArray()['profile']);
        $this->assertEquals($preferredUsername, $user->getPreferredUsername());
        $this->assertEquals($preferredUsername, $user->toArray()['preferred_username']);
        $this->assertEquals($locale, $user->getLocale());
        $this->assertEquals($locale, $user->toArray()['locale']);
        $this->assertEquals($givenName, $user->getGivenName());
        $this->assertEquals($givenName, $user->toArray()['given_name']);
        $this->assertEquals($middleName, $user->getMiddleName());
        $this->assertEquals($middleName, $user->toArray()['middle_name']);
        $this->assertEquals($picture, $user->getPicture());
        $this->assertEquals($picture, $user->toArray()['picture']);
        $this->assertEquals($nickname, $user->getNickname());
        $this->assertEquals($nickname, $user->toArray()['nickname']);
        $this->assertEquals($phoneNumber, $user->getPhoneNumber());
        $this->assertEquals($phoneNumber, $user->toArray()['phone_number']);
        $this->assertEquals($familyName, $user->getFamilyName());
        $this->assertEquals($familyName, $user->toArray()['family_name']);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->toArray()['email']);
        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals($username, $user->toArray()['username']);
        $this->assertEquals("true", $user->getPhoneNumberVerified());
        $this->assertEquals("true", $user->toArray()['phone_number_verified']);
        $this->assertEquals("true", $user->getEmailVerified());
        $this->assertEquals("true", $user->toArray()['email_verified']);
    }

    public function testExceptionThrownWhenError()
    {
        $message = uniqid();
        $this->expectException('League\OAuth2\Client\Provider\Exception\IdentityProviderException');
        $this->expectExceptionMessage($message);
        $status = rand(400, 600);
        $postResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"error":"'. $message .'"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getReasonPhrase');
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    public function testGetAuthenticatedRequest()
    {
        $method = 'GET';
        $url = 'https://test.auth.us-west-2.amazoncognito.com/oauth2/userInfo';

        $accessTokenResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $accessTokenResponse
            ->shouldReceive('getBody')
            ->andReturn(
                '{"access_token": "mock_access_token","user": {"sub": "1234","username": "test","name": "Test User"}}'
            );
        $accessTokenResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($accessTokenResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $authenticatedRequest = $this->provider->getAuthenticatedRequest($method, $url, $token);

        $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $authenticatedRequest);
        $this->assertEquals($method, $authenticatedRequest->getMethod());
        $this->assertStringContainsString(
            'Bearer mock_access_token',
            $authenticatedRequest->getHeaders()['Authorization'][0]
        );
    }
}
