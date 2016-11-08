<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\StreamInterface;

/**
 * An instance wraps a PHP stream and can be used for a PSR-7 implementation.
 * This interface provides a wrapper around the most common operations, including
 * serialization of the entire stream to a string.
 */
class OutputBufferStream extends Stream implements StreamInterface
{
    /**
     * @var resource 
     */
    protected $handle;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $handle = $this->createTempStream();
        
        if (!is_resource($handle) || get_resource_type($handle) !== 'stream') {
            throw new \RuntimeException('Failed to open php://temp stream');
        }
        
        $this->handle = $handle;
    }

    /**
     * Assert that output buffering is enabled.
     *
     * @return \RuntimeException
     */
    protected function assertOutputBuffering()
    {
        if (!$this->obGetStatus()) {
            throw new RuntimeException("Output buffering is disabled");
        }
    }

    /**
     * After change Response body work with global enviroment this function are copy
     * all content from previous Stream body (like php://temp) and copy it into
     * current php://output stream.
     * 
     * @return object current object
     */
    public function useGlobally()
    {
        if ($this->isGlobal()) {
            return $this;
        }
        
        $this->assertOutputBuffering();
        
        $this->rewind();
        $content = $this->getContents();
        
        $handle = $this->createOutputStream();
        if ($handle === false) {
            throw new \RuntimeException("Failed to open 'php://output' stream");
        }
        
        $this->obClean();
        parent::close();
        
        $this->handle = $handle;
        $this->write($content);
        return $this;
    }

    /**
     * After change Response body without Global Environment this function 
     * copy all data from php://output if this possible to the php://temp.
     * Also its a close php://output hanlde.
     * 
     * @return object current object
     */
    public function useLocally()
    {
        if (!$this->isGlobal()) {
            return $this;
        }
        
        $content = $this->getContents();
        
        $this->obClean();
        parent::close();
        
        $handle = fopen('php://temp', 'a+');
        if ($handle === false) {
            throw new \RuntimeException("Failed to open 'php://temp' stream");
        }
        
        $this->handle = $handle;
        $this->rewind();
        $this->write($content);
        
        return $content;
    }

    /**
     * Check if current stream are php://output. 
     * 
     * @return boolean
     */
    public function isGlobal()
    {
        return $this->getMetadata('uri') === 'php://output';
    }

    /**
     * Closes the stream and any underlying resources.
     * On default php output strem also try flash content
     */
    public function close()
    {
        if (!$this->isGlobal()) {
            parent::close();
            return;
        }
        
        $this->assertOutputBuffering();
        
        $this->obFlush();
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if (!$this->isGlobal()) {
            return parent::getSize();
        }
        
        $this->assertOutputBuffering();
        
        return $this->obGetLength();
    }

    /**
     * Returns the current position of the file read/write handle
     * If in stream are opened php://output - then return last pos
     *
     * @return int Position of the file handle
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        if (!$this->isGlobal()){
            return parent::tell();
        }
        
        $this->assertOutputBuffering();
        
        return $this->obGetLength();
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        if (!$this->isGlobal()) {
            return parent::eof();
        }
        
        return true;
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        if (!$this->isGlobal()) {
            return parent::isSeekable();
        }
        
        return false;
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
        if (!$this->isGlobal()) {
            return parent::seek($offset, $whence);
        }
        
        throw new \RuntimeException("Stream isn't seekable");
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
        if (!$this->isGlobal()) {
            return parent::rewind();
        }
        
        throw new \RuntimeException("Stream isn't seekable");
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        if (!$this->isGlobal()) {
            return parent::isWritable();
        }
        
        return true;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        if (!$this->isGlobal()) {
            return parent::isReadable();
        }
        
        return false;
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
        if (!$this->isGlobal()) {
            return parent::read($length);
        }
        
        throw new \RuntimeException("Stream isn't readable");
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
        if (!$this->isGlobal()) {
            return parent::getContents();
        }
        
        $this->assertOutputBuffering();
        
        return $this->obGetContents();
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
     * Create php://output stream
     * @codeCoverageIgnore
     * 
     * @return resource
     */
    protected function createOutputStream()
    {
        return fopen('php://output', 'a+');
    }
    
    /**
     * Wrapper around ob_get_status()
     * @codeCoverageIgnore
     * 
     * @return boolean
     */
    protected function obGetStatus()
    {
        return ob_get_status();
    }
    
    /**
     * Wrapper around ob_get_contents()
     * @codeCoverageIgnore
     * 
     * @return string
     */
    protected function obGetContents()
    {
        return ob_get_contents();
    }
    
    /**
     * Wrapper around ob_clean()
     * @codeCoverageIgnore
     * 
     * @return string
     */
    protected function obClean()
    {
        return ob_clean();
    }
    
    /**
     * Wrapper around ob_flush()
     * @codeCoverageIgnore
     */
    protected function obFlush()
    {
        ob_flush();
    }
    
    /**
     * Wrapper around ob_get_length()
     * @codeCoverageIgnore
     */
    protected function obGetLength()
    {
        return ob_get_length();
    }
}
