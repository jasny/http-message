<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\StreamInterface;

/**
 * An instance wraps a PHP stream and can be used for a PSR-7 implementation.
 * This interface provides a wrapper around the most common operations, including
 * serialization of the entire stream to a string.
 */
class Stream implements StreamInterface
{
    /**
     * @var resource 
     */
    protected $handle;
    
    /**
     * Class constructor
     * 
     * @param resource $handle
     */
    public function __construct($handle = null)
    {
        if (!isset($handle)) {
            $handle = $this->createTempStream();
            
            if (!is_resource($handle) || get_resource_type($handle) !== 'stream') {
                throw new \RuntimeException("Failed to open 'php://temp' stream");
            }
        } elseif (!is_resource($handle) || get_resource_type($handle) !== 'stream') {
            throw new \InvalidArgumentException('Argument must be a PHP stream resource');
        }
        
        $this->handle = $handle;
    }
    
    
    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            
            return $this->getContents();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     */
    public function close()
    {
        if (!$this->isClosed()) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    /**
     * Check if the stream is closed
     * 
     * @return boolean
     */
    protected function isClosed()
    {
        return !isset($this->handle) || get_resource_type($this->handle) !== 'stream';
    }
    
    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $handle = $this->handle;
        $this->handle = null;
        
        return $handle;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if (!$this->isClosed()) {
            $stat = fstat($this->handle);
        }
        
        return isset($stat['size']) ? $stat['size'] : null;
    }

    /**
     * Returns the current position of the file read/write handle
     *
     * @return int Position of the file handle
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        if (!$this->isClosed()) {
            $pos = ftell($this->handle);
        }
        
        if (!isset($pos) || $pos === false) {
            throw new \RuntimeException("Failed to get the position of the stream");
        }
        
        return $pos;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return $this->isClosed() || feof($this->handle);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->getMetadata('seekable') ?: false;
    }

    /**
     * Seek to a position in the stream.
     *
     * @see http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException("Stream isn't seekable");
        }
        
        $ret = fseek($this->handle, $offset, $whence);
        
        if ($ret === -1) {
            throw new \RuntimeException("Failed to get the seek to postion in the stream");
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @see http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        $this->seek(0, SEEK_SET);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        $mode = $this->getMetadata('mode');
        return isset($mode) && $mode[0] !== 'r';
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException("Stream isn't writable");
        }
        
        $ret = fwrite($this->handle, $string);
        
        if ($ret === false) {
            throw new \RuntimeException("Failed to write to stream");
        }
        
        return $ret;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        $mode = $this->getMetadata('mode');
        return isset($mode) && ($mode[0] === 'r' || strpos($mode, '+') !== false);
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException("Stream isn't readable");
        }
        
        $contents = fread($this->handle, $length);
        
        if ($contents === false) {
            throw new \RuntimeException("Failed to read from stream");
        }
        
        return $contents;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read.
     * @throws \RuntimeException if error occurs while reading.
     */
    public function getContents()
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException("Stream isn't readable");
        }
        
        $contents = '';
        
        while (!$this->eof()) {
            $contents .= $this->read(512000);
        }
        
        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @see http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        $meta = !$this->isClosed() ? stream_get_meta_data($this->handle) : null;
        
        if (isset($key)) {
            $meta = isset($meta[$key]) ? $meta[$key] : null;
        }
        
        return $meta;
    }
    
    /**
     * Event when cloning a stream
     */
    public function __clone()
    {
        if (!$this->isClosed()) {
            $uri = $this->getMetadata('uri');
            $mode = $this->getMetadata('mode');
            
            $this->handle = fopen($uri, $mode);
        }
    }
    
    
    /**
     * Create php://temp stream
     * 
     * @return resource
     */
    protected function createTempStream()
    {
        return fopen('php://temp', 'a+');
    }
    
    
    /**
     * Open a stream.
     * @see http://php.net/manual/en/function.fopen.php
     * 
     * @param string $uri   URI or filename
     * @param string $mode
     * @return static
     * @throws \RuntimeException
     */
    public static function open($uri, $mode)
    {
        $fp = fopen($uri, $mode);
        
        if (!$fp) {
            throw new \RuntimeException("Failed to open the '$uri' stream in '$mode' mode");
        }
        
        return new static($fp);
    }
}
