<?php

namespace Jasny\HttpMessage\Uri;

/**
 * Uri user info methods.
 */
trait UserInfo
{
    /**
     * @var string
     */
    protected $user;
    
    /**
     * @var string
     */
    protected $pass;
    

    /**
     * Check if username is valid according to RFC 3986
     * 
     * @param string $user
     * @return boolean
     */
    protected function isValidUsername($user)
    {
        return !\Jasny\str_contains($user, ':');
    }
    
    
    /**
     * Retrieve the user information component of the URI.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        if (!isset($this->user)) {
            return '';
        }
        
        return $this->user . ($this->pass ? ':' . $this->pass : '');
    }

    /**
     * Set the username
     * 
     * @param string $user
     * @throws \InvalidArgumentException for invalid username.
     */
    protected function setUser($user)
    {
        if (!$this->isValidUsername($user)) {
            throw new \InvalidArgumentException("Invalid username '$user': double colon not allowed");
        }
        
        $this->user = (string)$user;
    }
    
    /**
     * Set the password
     * 
     * @param string $password
     */
    protected function setPass($password)
    {
        $this->pass = (string)$password;
    }
    
    /**
     * Return an instance with the specified user information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return static A new instance with the specified user information.
     * @throws \InvalidArgumentException for invalid username.
     */
    public function withUserInfo($user, $password = null)
    {
        $uri = clone $this;
        $uri->setUser($user);
        $uri->setPass($password);
        
        return $uri;
    }
}
