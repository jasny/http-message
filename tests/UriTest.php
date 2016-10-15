<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use PHPUnit_Framework_TestCase;

use Jasny\HttpMessage\Uri;

/**
 * @covers \Jasny\HttpMessage\Uri
 * @covers \Jasny\HttpMessage\Uri\Scheme
 * @covers \Jasny\HttpMessage\Uri\Authority
 * @covers \Jasny\HttpMessage\Uri\UserInfo
 * @covers \Jasny\HttpMessage\Uri\Host
 * @covers \Jasny\HttpMessage\Uri\Port
 * @covers \Jasny\HttpMessage\Uri\Path
 * @covers \Jasny\HttpMessage\Uri\Query
 * @covers \Jasny\HttpMessage\Uri\Fragment
 */
class UriTest extends PHPUnit_Framework_TestCase
{
    public function testContruct()
    {
        $uri = new Uri();
        
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getAuthority());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertSame('', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
        
        $this->assertSame('', (string)$uri);
    }
    
    public function testConstructFromParts()
    {
        $uri = new Uri([
            'scheme' => 'https',
            'user' => 'someuser',
            'pass' => 'secretpassword',
            'host' => 'www.example.com',
            'port' => 3000,
            'path' => '/pages/foo',
            'query' => 'color=red&shape=round',
            'fragment' => 'more',
            'foo' => 'bar' // should be ignored
        ]);
        
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('someuser:secretpassword@www.example.com:3000', $uri->getAuthority());
        $this->assertSame('someuser:secretpassword', $uri->getUserInfo());
        $this->assertSame('www.example.com', $uri->getHost());
        $this->assertSame(3000, $uri->getPort());
        $this->assertSame('/pages/foo', $uri->getPath());
        $this->assertSame('color=red&shape=round', $uri->getQuery());
        $this->assertSame('more', $uri->getFragment());
        
        $this->assertSame(
            'https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more',
            (string)$uri
        );
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructInvalidArgument()
    {
        new Uri(2);
    }

    public function testConstructFromPartsRelativePath()
    {
        $uri = new Uri([
            'host' => 'www.example.com',
            'path' => 'pages/foo'
        ]);
        
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('www.example.com', $uri->getAuthority());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('www.example.com', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertSame('pages/foo', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
        
        $this->assertSame('//www.example.com/pages/foo', (string)$uri);
    }
    
    
    public function testConstructFromStringFull()
    {
        $uri = new Uri('https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more');
        
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('someuser:secretpassword@www.example.com:3000', $uri->getAuthority());
        $this->assertSame('someuser:secretpassword', $uri->getUserInfo());
        $this->assertSame('www.example.com', $uri->getHost());
        $this->assertSame(3000, $uri->getPort());
        $this->assertSame('/pages/foo', $uri->getPath());
        $this->assertSame('color=red&shape=round', $uri->getQuery());
        $this->assertSame('more', $uri->getFragment());
        
        $this->assertSame(
            'https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more',
            (string)$uri
        );
    }
    
    public function testConstructFromStringSchemeDomain()
    {
        $uri = new Uri('http://www.example.com');
        
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('www.example.com', $uri->getAuthority());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('www.example.com', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertSame('', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
        
        $this->assertSame('http://www.example.com', (string)$uri);
    }
    
    public function testConstructFromStringIpv4()
    {
        $uri = new Uri('http://192.168.0.1');
        
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('192.168.0.1', $uri->getHost());
        $this->assertNull($uri->getPort());
        
        $this->assertSame('http://192.168.0.1', (string)$uri);
    }
    
    public function testConstructFromStringIpv6()
    {
        $uri = new Uri('http://[3ffe:1900:4545:3:200:f8ff:fe21:67cf]');
        
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('[3ffe:1900:4545:3:200:f8ff:fe21:67cf]', $uri->getHost());
        
        $this->assertSame('http://[3ffe:1900:4545:3:200:f8ff:fe21:67cf]', (string)$uri);
    }
    
    public function testConstructFromStringSchemeDomainPath()
    {
        $uri = new Uri('http://www.example.com/pages/foo');
        
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('www.example.com', $uri->getAuthority());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('www.example.com', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertSame('/pages/foo', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
        
        $this->assertSame('http://www.example.com/pages/foo', (string)$uri);
    }
    
    public function testConstructFromAbsolutePath()
    {
        $uri = new Uri('/pages/foo?color=red&shape=round#more');
        
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getAuthority());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertSame('/pages/foo', $uri->getPath());
        $this->assertSame('color=red&shape=round', $uri->getQuery());
        $this->assertSame('more', $uri->getFragment());
        
        $this->assertSame('/pages/foo?color=red&shape=round#more', (string)$uri);
    }
    
    public function testConstructFromRootPathOnly()
    {
        $uri = new Uri('/');
        
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getAuthority());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertSame('/', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
        
        $this->assertSame('/', (string)$uri);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid URI
     */
    public function testConstructInvalidUri()
    {
        new Uri('http:///example.com');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid URI
     */
    public function testConstructInvalidUriInt()
    {
        new Uri(20);
    }
    
    
    public function testNormalizeScheme()
    {
        $uri = new Uri('HtTpS://www.example.com');
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('https://www.example.com', (string)$uri);
    }
    
    public function testNormalizeHost()
    {
        $uri = new Uri('https://WwW.eXaMpLe.CoM');
        $this->assertSame('www.example.com', $uri->getHost());
        $this->assertSame('https://www.example.com', (string)$uri);
    }
    
    public function testDefaultPort()
    {
        $uriHttp = new Uri('http://www.example.com:80/');
        $this->assertNull($uriHttp->getPort());

        $uriHttpAlt = new Uri('http://www.example.com:8080/');
        $this->assertSame(8080, $uriHttpAlt->getPort());
        
        $uriHttps = new Uri('https://www.example.com:443/');
        $this->assertNull($uriHttps->getPort());
        
        $uriHttpsAlt = new Uri('https://www.example.com:8443/');
        $this->assertSame(8443, $uriHttpsAlt->getPort());
    }
    
    
    public function testWithScheme()
    {
        $uri = new Uri('https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more');
        $modifiedUri = $uri->withScheme('HTTP'); // Also test normalization

        $this->assertNotSame($uri, $modifiedUri);
        $this->assertSame('http', $modifiedUri->getScheme());
        $this->assertSame(
            'http://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more',
            (string)$modifiedUri
        );
    }
    
    public function testWithUserInfoWithUsernameOnly()
    {
        $uri = new Uri('https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more');
        $modifiedUri = $uri->withUserInfo('john.doe');

        $this->assertNotSame($uri, $modifiedUri);
        $this->assertSame('john.doe', $modifiedUri->getUserInfo());
        $this->assertSame('john.doe@www.example.com:3000', $modifiedUri->getAuthority());
        $this->assertSame(
            'https://john.doe@www.example.com:3000/pages/foo?color=red&shape=round#more',
            (string)$modifiedUri
        );
    }
    
    public function testWithUserInfoWithUsernameAndPassword()
    {
        $uri = new Uri('https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more');
        $modifiedUri = $uri->withUserInfo('john.doe', 'god123');

        $this->assertNotSame($uri, $modifiedUri);
        $this->assertSame('john.doe:god123', $modifiedUri->getUserInfo());
        $this->assertSame('john.doe:god123@www.example.com:3000', $modifiedUri->getAuthority());
        $this->assertSame(
            'https://john.doe:god123@www.example.com:3000/pages/foo?color=red&shape=round#more',
            (string)$modifiedUri
        );
    }
    
    public function testWithHost()
    {
        $uri = new Uri('https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more');
        $modifiedUri = $uri->withHost('Example.org'); // Also test normalization

        $this->assertNotSame($uri, $modifiedUri);
        $this->assertSame('example.org', $modifiedUri->getHost());
        $this->assertSame(
            'https://someuser:secretpassword@example.org:3000/pages/foo?color=red&shape=round#more',
            (string)$modifiedUri
        );
    }
    
    public function testWithPort()
    {
        $uri = new Uri('https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more');
        $modifiedUri = $uri->withPort(8443);

        $this->assertNotSame($uri, $modifiedUri);
        $this->assertSame(8443, $modifiedUri->getPort());
        $this->assertSame(
            'https://someuser:secretpassword@www.example.com:8443/pages/foo?color=red&shape=round#more',
            (string)$modifiedUri
        );
    }
    
    public function testWithAbsolutePath()
    {
        $uri = new Uri('https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more');
        $modifiedUri = $uri->withPath('/bar');

        $this->assertNotSame($uri, $modifiedUri);
        $this->assertSame('/bar', $modifiedUri->getPath());
        $this->assertSame(
            'https://someuser:secretpassword@www.example.com:3000/bar?color=red&shape=round#more',
            (string)$modifiedUri
        );
    }
    
    public function testWithQuery()
    {
        $uri = new Uri('https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more');
        $modifiedUri = $uri->withQuery('color=blue');

        $this->assertNotSame($uri, $modifiedUri);
        $this->assertSame('color=blue', $modifiedUri->getQuery());
        $this->assertSame(
            'https://someuser:secretpassword@www.example.com:3000/pages/foo?color=blue#more',
            (string)$modifiedUri
        );
    }
    
    public function testWithQueryAsAssocArray()
    {
        $uri = new Uri('https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more');
        $modifiedUri = $uri->withQuery(['color' => 'blue', 'shape' => 'square']);

        $this->assertNotSame($uri, $modifiedUri);
        $this->assertSame('color=blue&shape=square', $modifiedUri->getQuery());
        $this->assertSame(
            'https://someuser:secretpassword@www.example.com:3000/pages/foo?color=blue&shape=square#more',
            (string)$modifiedUri
        );
    }
    
    public function testWithFragment()
    {
        $uri = new Uri('https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more');
        $modifiedUri = $uri->withFragment('less');

        $this->assertNotSame($uri, $modifiedUri);
        $this->assertSame('less', $modifiedUri->getFragment());
        $this->assertSame(
            'https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#less',
            (string)$modifiedUri
        );
    }
    
    public function testClearEverything()
    {
        $uri = new Uri('https://someuser:secretpassword@www.example.com:3000/pages/foo?color=red&shape=round#more');
        $modifiedUri = $uri
            ->withScheme('')
            ->withUserInfo('', null)
            ->withHost('')
            ->withPort(null)
            ->withPath('')
            ->withQuery('')
            ->withFragment('');
        
        $this->assertSame('', (string)$modifiedUri);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid or unsupported scheme '$$$'
     */
    public function testInvalidScheme()
    {
        new Uri(['scheme' => '$$$']);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid or unsupported scheme 'ftp'
     */
    public function testUnsupportedScheme()
    {
        new Uri(['scheme' => 'ftp']);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid username 'x:y': double colon not allowed
     */
    public function testInvalidUser()
    {
        new Uri(['user' => 'x:y']);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid hostname '$ab$'
     */
    public function testInvalidHost()
    {
        new Uri(['host' => '$ab$']);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid hostname '900.800.700.600'
     */
    public function testInvalidHostIpv4()
    {
        new Uri(['host' => '900.800.700.600']);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid hostname '3ffe:1900:4545:3:200:f8ff:fe21:67cf'
     */
    public function testInvalidHostIpv6()
    {
        new Uri(['host' => '3ffe:1900:4545:3:200:f8ff:fe21:67cf']); // No brackets
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid port '1000000'
     */
    public function testInvalidPort()
    {
        new Uri(['port' => 1000000]);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid path '`foo`'
     */
    public function testInvalidPath()
    {
        new Uri(['path' => '`foo`']);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid path '//'
     */
    public function testInvalidPathDoubleRoot()
    {
        new Uri(['path' => '//']);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid query '`foo`'
     */
    public function testInvalidQuery()
    {
        new Uri(['query' => '`foo`']);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid fragment '`foo`'
     */
    public function testInvalidFragment()
    {
        new Uri(['fragment' => '`foo`']);
    }
}
