<?php

namespace Jasny\HttpMessage\ServerRequest;

use Jasny\HttpMessage\DerivedAttribute;
use Jasny\HttpMessage\DerivedAttributeInterface;

/**
 * ServerRequest attributes methods
 */
trait Attributes
{
    /**
     * @var array
     */
    protected $attributes;

    
    /**
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    abstract protected function copy();

    
    /**
     * Create derived attribute objects
     */
    protected function createDerivedAttributes()
    {
        $this->attributes = [
            'client_ip' => new DerivedAttribute\ClientIp(),
            'is_xhr' => new DerivedAttribute\IsXhr(),
            'local_referer' => new DerivedAttribute\LocalReferer()
        ];
    }
    
    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc.
     * 
     * Attribute names are automatically turned into snake_case.
     *
     * @return mixed[] Attributes derived from the request.
     */
    public function getAttributes()
    {
        $attributes = [];
        
        foreach ($this->attributes as $name => $attr) {
            $value = $attr instanceof \Closure || $attr instanceof DerivedAttributeInterface ? $attr($this) : $attr;
            $attributes[$name] = $value;
        }
        
        return $attributes;
    }
    
    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     * 
     * The attribute name is automatically turned into snake_case.
     *
     * @see getAttributes()
     * @param string $name     The attribute name.
     * @param mixed  $default  Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        $key = \Jasny\snakecase($name);
        
        $attr = isset($this->attributes[$key]) ? $this->attributes[$key] : null;
        $value = $attr instanceof \Closure || $attr instanceof DerivedAttributeInterface ? $attr($this) : $attr;
        
        return isset($value) ? $value : $default;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * The attribute name is automatically turned into snake_case.
     *
     * @see getAttributes()
     * @param string $name   The attribute name.
     * @param mixed  $value  The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value)
    {
        $request = $this->copy();
        
        $key = \Jasny\snakecase($name);
        $request->attributes[$key] = $value;
        
        return $request;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute($name)
    {
        $request = $this->copy();
        
        $key = \Jasny\snakecase($name);
        unset($request->attributes[$key]);
        
        return $request;
    }
}
