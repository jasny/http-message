<?php

namespace Jasny\HttpMessage;

use Jasny\HttpMessage\Stream;
use Jasny\HttpMessage\Wrap\OutputControl;

/**
 * An instance wraps a PHP stream and can be used for a PSR-7 implementation.
 * This interface provides a wrapper around the most common operations, including
 * serialization of the entire stream to a string.
 */
class OutputBufferStream extends Stream
{
    use OutputControl;
    
    /**
     * Assert that output buffering is enabled.
     *
     * @return \RuntimeException
     */
    protected function assertOutputBuffering()
    {
        if ($this->obGetLevel() === 0) {
            throw new \RuntimeException("Output buffering is not enabled");
        }
    }

    /**
     * After change Response body work with global enviroment this function are copy
     * all content from previous Stream body (like php://temp) and copy it into
     * current php://output stream.
     * 
     * @return $this
     * @throws \RuntimeException
     */
    public function useGlobally()
    {
        if (!isset($this->handle)) {
            throw new \RuntimeException("The stream is closed");
        }
        
        if ($this->isGlobal()) {
            return $this;
        }
        
        $this->assertOutputBuffering();
        
        $output = $this->createOutputStream();
        if ($output === false) {
            throw new \RuntimeException("Failed to create temp stream");
        }
        
        $this->obClean();
        $this->rewind();
        stream_copy_to_stream($this->handle, $output);
        
        parent::close();
        $this->handle = $output;
        
        return $this;
    }

    /**
     * Get this stream in a local scope.
     * If the stream is global, it will create a temp stream and copy the contents.
     * 
     * @return OutputBufferStream
     * @throws \RuntimeException
     */
    public function withLocalScope()
    {
        if ($this->isClosed()) {
            throw new \RuntimeException("The stream is closed");
        }
        
        if (!$this->isGlobal()) {
            return $this;
        }
        
        $stream = clone $this;
        
        fwrite($stream->handle, (string)$this);
        
        return $stream;
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
        if ($this->isGlobal()) {
            $this->assertOutputBuffering();
            $this->obFlush();
        }
        
        parent::close();
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
        if (!$this->isGlobal()) {
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
        
        throw new \RuntimeException("Unable to partially read the output buffer, instead cast the stream to a string");
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
        
        throw new \RuntimeException("Unable to partially read the output buffer, instead cast the stream to a string");
    }
    
    /**
     * Reads all data from the output buffer into a string.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        if (!$this->isGlobal()) {
            return parent::__toString();
        }
        
        try {
            $this->assertOutputBuffering();
            $contents = $this->obGetContents();
        } catch (\Exception $e) {
            $contents = $e->getMessage();
        }
        
        return $contents;
    }
    
    /**
     * Clone a stream
     */
    public function __clone()
    {
        if (!$this->isGlobal()) {
            parent::__clone();
            return;
        }
            
        $this->handle = $this->createTempStream();
        
        if (!$this->handle) {
            throw new \RuntimeException("Failed to create temp stream");
        }
    }
    
    
    /**
     * Create php://output stream
     * 
     * @return resource
     */
    protected function createOutputStream()
    {
        return fopen('php://output', 'a+');
    }
}
