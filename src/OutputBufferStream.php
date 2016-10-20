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
     *
     * @param resource $handle
     */
    public function __construct()
    {
        $handle = fopen('php://temp', 'a+');
        if (!is_resource($handle) || get_resource_type($handle) !== 'stream') {
            throw new \InvalidArgumentException('Argument must be a PHP stream resource');
        }
        
        $this->handle = $handle;
    }

    /**
     * After change Response body work with global enviroment this function are copy
     * all content from previous Stream body (like php://temp) and copy it into
     * current php://output stream.
     * 
     * @codeCoverageIgnore
     */
    public function useGlobally()
    {
        $content = $this->getContents();
        
        $handle = fopen('php://output', 'a+');
        if ($handle === false) {
            throw new \RuntimeException("Failed to open 'php://output' stream");
        }
        if (ob_get_level() === 0) {
            throw new \RuntimeException("Failed change output buffer stream.");
        }
        ob_clean();
        parent::close();
        
        $this->handle = $handle;
        $this->write($content);
    }

    /**
     * After change Response body without Global Environment this function 
     * copy all data from php://output if this possible to the php://temp.
     * Also its a close php://output hanlde.
     * 
     * @codeCoverageIgnore
     */
    public function useLocally()
    {
        $content = $this->getContents();
        
        $handle = fopen('php://temp', 'a+');
        if ($handle === false) {
            throw new \RuntimeException("Failed to open 'php://temp' stream");
        }
        ob_clean();
        parent::close();
        
        $this->handle = $handle;
        $this->write($content);
    }

    /**
     * Check if current stream are php://output. 
     * 
     * @return boolean
     */
    public function isGlobal()
    {
        return ($this->getMetadata('stream_type') === 'Output' && $this->getMetadata('wrapper_type') === 'PHP');
    }

    /**
     * Closes the stream and any underlying resources.
     * On default php output strem also try flash content
     */
    public function close()
    {
        if ($this->isGlobal()) {
            if (ob_get_contents() === false)
                throw new \RuntimeException("Can not clean output buffer stream.");
            ob_flush();
        }
        
        if (!$this->isClosed()) {
            fclose($this->handle);
        }
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if ($this->isGlobal()) {
            return fstat($this->hanlde)['size'];
        }
        return parent::getSize();
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
        if ($this->isGlobal())
            return fstat($this->hanlde)['size'];
        
        return parent::tell();
    }

    /**
     * Returns true if the stream is at the end of the stream.
     * In php://output all time return true
     *
     * @return bool
     */
    public function eof()
    {
        if ($this->isGlobal())
            return true;
        
        return parent::eof();
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        if ($this->isGlobal())
            return false;
        
        return parent::isSeekable();
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
        if ($this->isGlobal())
            throw new \RuntimeException("Stream isn't seekable");
        
        return parent::seek($offset, $whence);
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
        throw new \RuntimeException("Stream isn't seekable");
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        if ($this->isGlobal())
            return true;
        
        return parent::isWritable();
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        if ($this->isGlobal())
            return false;
        
        return parent::isReadable();
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
        if ($this->isGlobal())
            throw new \RuntimeException("Stream isn't readable");
        
        return parent::read($length);
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
        if ($this->isGlobal()) {
            if (ob_get_level === 0)
                throw new \RuntimeException("Failed read from output buffer.");
            return ob_get_contents();
        }
        return parent::getContents();
    }
}
