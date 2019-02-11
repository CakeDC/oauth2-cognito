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

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class CognitoUser implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->data = $response;
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->getField('sub');
    }

    /**
     * Get address.
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->getField('address');
    }
    
    /**
     * Get username.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->getField('username');
    }

    /**
     * Get email address.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getField('email');
    }

    /**
     * Get email verified.
     *
     * @return string|null
     */
    public function getEmailVerified()
    {
        return $this->getField('email_verified');
    }

    /**
     * Get phone number.
     *
     * @return string|null
     */
    public function getPhoneNumber()
    {
        return $this->getField('phone_number');
    }

    /**
     * Get phone number verified.
     *
     * @return string|null
     */
    public function getPhoneNumberVerified()
    {
        return $this->getField('phone_number_verified');
    }

    /**
     * Get birthdate.
     *
     * @return string|null
     */
    public function getBirthdate()
    {
        return $this->getField('birthdate');
    }

    /**
     * Get profile.
     *
     * @return string|null
     */
    public function getProfile()
    {
        return $this->getField('profile');
    }

    /**
     * Get gender.
     *
     * @return string|null
     */
    public function getGender()
    {
        return $this->getField('gender');
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getField('name');
    }
    
    /**
     * Get given name.
     *
     * @return string|null
     */
    public function getGivenName()
    {
        return $this->getField('given_name');
    }

    /**
     * Get middle name.
     *
     * @return string|null
     */
    public function getMiddleName()
    {
        return $this->getField('middle_name');
    }

    /**
     * Get family name.
     *
     * @return string|null
     */
    public function getFamilyName()
    {
        return $this->getField('family_name');
    }

    /**
     * Get locale.
     *
     * @return string|null
     */
    public function getLocale()
    {
        return $this->getField('locale');
    }
    
    /**
     * Get zone info.
     *
     * @return string|null
     */
    public function getZoneinfo()
    {
        return $this->getField('zoneinfo');
    }

    /**
     * Get preferred username.
     *
     * @return string|null
     */
    public function getPreferredUsername()
    {
        return $this->getField('preferred_username');
    }

    /**
     * Get nickname.
     *
     * @return string|null
     */
    public function getNickname()
    {
        return $this->getField('nickname');
    }

    /**
     * Get website.
     *
     * @return string|null
     */
    public function getWebsite()
    {
        return $this->getField('website');
    }

    /**
     * Get picture.
     *
     * @return string|null
     */
    public function getPicture()
    {
        return $this->getField('picture');
    }
    
    /**
     * Get user data as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }
    
    /**
     * Returns a field from the Graph node data.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    private function getField($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}
