<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\UploadedFileInterface;
use Jasny\HttpMessage\Stream;

/**
 * Value object representing a file uploaded through an HTTP request.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 */
class UploadedFile implements UploadedFileInterface
{
    const ERROR_DESCRIPTIONS = [
        UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
        UPLOAD_ERR_FORM_SIZE =>
            "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
        UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
        UPLOAD_ERR_NO_FILE => "No file was uploaded",
        UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
        UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload",
        -1 => "An unknown error occured"
    ];
    
    /**
     * The original name of the file on the client machine.
     * @var string
     */
    protected $name;
    
    /**
     * The mime type of the file, if the browser provided this information. An example would be "image/gif".
     * This mime type is however not checked on the PHP side and therefore don't take its value for granted.
     * 
     * @var string
     */
    protected $type;
    
    /**
     * The size, in bytes, of the uploaded file.
     * @var int
     */
    protected $size;
    
    /**
     * The temporary filename of the file in which the uploaded file was stored on the server.
     * @var string
     */
    protected $tmpName;
    
    /**
     * The error code associated with this file upload.
     * @var int
     */
    protected $error = UPLOAD_ERR_OK;
    
    
    /**
     * Post parameter key
     * @var string
     */
    protected $key;
    
    /**
     * Assert that the file is actually uploaded.
     * @var boolean
     */
    protected $assertIsUploadedFile = false;
    
    
    /**
     * Class constructor
     * 
     * @param array   $info
     * @param string  $parameterKey
     * @param boolean $assertIsUploadedFile  Assert that the file is actually uploaded
     */
    public function __construct(array $info, $parameterKey = null, $assertIsUploadedFile = false)
    {
        foreach ($info as $key => $value) {
            $prop = \Jasny\camelcase($key);
                
            if (property_exists($this, $prop)) {
                $this->$prop = $value;
            }
        }
        
        $this->key = $parameterKey;
        $this->assertIsUploadedFile = $assertIsUploadedFile;
    }
    
    /**
     * Get the parameter key
     * Note: This method is not part of PSR-7.
     * 
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }
    
    /**
     * Get a description to use for errors, includes the parameter key.
     * 
     * @return string
     */
    protected function getDesc()
    {
        $key = $this->getKey();
        return "uploaded file" . (isset($key) ? " '$key'" : '');
    }
    
    
    /**
     * Assert that the temp file exists and is an uploaded file
     * 
     * @throws \RuntimeException if the file doesn't exist or is not an uploaded file.
     */
    protected function assertTmpFile()
    {
        if (empty($this->tmpName)) {
            throw new \RuntimeException("There is no tmp_file for " . $this->getDesc()
                . ": " . $this->getErrorDescription());
        }
        
        if (!file_exists($this->tmpName)) {
            throw new \RuntimeException("The " . $this->getDesc() . " no longer exists or is already moved");
        }
        
        if ($this->assertIsUploadedFile && !$this->isUploadedFile($this->tmpName)) {
            throw new \RuntimeException("The specified tmp_name for " . $this->getDesc() . " doesn't appear"
                . " to be uploaded via HTTP POST");
        }
    }
    
    /**
     * Tells whether the file was uploaded via HTTP POST
     * @link http://php.net/manual/en/function.is-uploaded-file.php
     * 
     * This method can be mocked for (unit) testing.
     * 
     * @param string $filename
     * @return boolean
     */
    protected function isUploadedFile($filename)
    {
        return is_uploaded_file($filename);
    }
    
    /**
     * Moves an uploaded file to a new location
     * @link http://php.net/manual/en/function.move-uploaded-file.php
     * 
     * This method can be mocked for (unit) testing.
     * 
     * @param string $filename
     * @param string $destination
     * @return boolean
     */
    protected function moveUploadedFile($filename, $destination)
    {
        return move_uploaded_file($filename, $destination);
    }
    
    /**
     * Renames a file or directory
     * @link http://php.net/manual/en/function.rename.php
     * 
     * This method can be mocked for (unit) testing.
     * 
     * @param string $filename
     * @param string $destination
     * @return boolean
     */
    protected function rename($filename, $destination)
    {
        return move_uploaded_file($filename, $destination);
    }
    
    
    /**
     * Check if the path is valid
     * 
     * @param string $path
     * @return boolean
     */
    protected function isValidPath($path)
    {
        return !preg_match('/[\*\?\"\<\>\|\t\n]/', $path);
    }
    
    /**
     * Retrieve a stream representing the uploaded file.
     *
     * This method returns a StreamInterface instance, representing the
     * uploaded file. The purpose of this method is to allow utilizing native PHP
     * stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in a
     * native PHP stream wrapper to work with such functions).
     *
     * If the moveTo() method has been called previously, this method raises
     * an exception.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     *     created.
     */
    public function getStream()
    {
        $this->assertTmpFile();
        return Stream::open($this->tmpName, 'r');
    }

    /**
     * Move the uploaded file to a new location.
     *
     * Use this method as an alternative to move_uploaded_file(). This method is
     * guaranteed to work in both SAPI and non-SAPI environments.
     * Implementations must determine which environment they are in, and use the
     * appropriate method (move_uploaded_file(), rename(), or a stream
     * operation) to perform the operation.
     *
     * $targetPath may be an absolute path, or a relative path. If it is a
     * relative path, resolution should be the same as used by PHP's rename()
     * function.
     *
     * The original file or stream is removed on completion.
     *
     * When used in an SAPI environment where $_FILES is populated, when writing
     * files via moveTo(), is_uploaded_file() and move_uploaded_file() is used to
     * ensure permissions and upload status are verified correctly.
     *
     * If you wish to move to a stream, use getStream(), as SAPI operations
     * cannot guarantee writing to stream destinations.
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $targetPath specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($targetPath)
    {
        $this->assertTmpFile();
        
        if (!$this->isValidPath($targetPath)) {
            throw new \InvalidArgumentException("Unable to move " . $this->getDesc() . ": "
                . "'$targetPath' is not a valid path");
        }

        $fn = [$this, $this->assertIsUploadedFile ? 'moveUploadedFile' : 'rename'];
        $ret = @call_user_func($fn, $this->tmpName, $targetPath);
        
        if (!$ret) {
            $err = error_get_last();
            throw new \RuntimeException("Failed to move " . $this->getDesc() . " to '$targetPath'"
                . ($err ? ': ' . $err['message'] : null));
        }
    }
    
    /**
     * Retrieve the file size.
     *
     * Typically returns the value stored in the "size" key of the file in
     * the $_FILES array, as PHP calculates this based on the actual size
     * transmitted.
     * 
     * The size is never calculated by opening the file. If that is required
     * use getStream().
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }
    
    /**
     * Retrieve the error associated with the uploaded file.
     *
     * The return value is one of PHP's UPLOAD_ERR_XXX constants.
     *
     * If the file was uploaded successfully, this method returns
     * UPLOAD_ERR_OK.
     *
     * Typically returns the value stored in the "error" key of the file in
     * the $_FILES array.
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }
    
    /**
     * Retrieve the description of the error associated with the uploaded file.
     *
     * If the file was uploaded successfully, this method returns null.
     * 
     * This method is not part of PSR-7.
     * 
     * @return string|null
     */
    public function getErrorDescription()
    {
        if ($this->error === UPLOAD_ERR_OK) {
            return null;
        }
        
        $descs = static::ERROR_DESCRIPTIONS;
        return isset($descs[$this->error]) ? $descs[$this->error] : $descs[-1];
    }
    
    /**
     * Retrieve the filename sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious filename with the intention to corrupt or hack your
     * application.
     *
     * Typically returns the value stored in the "name" key of the file in
     * the $_FILES array.
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename()
    {
        return $this->name;
    }
    
    /**
     * Retrieve the media type sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious media type with the intention to corrupt or hack your
     * application.
     *
     * Typically returns the value stored in the "type" key of the file in
     * the $_FILES array.
     *
     * @return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function getClientMediaType()
    {
        return $this->type;
    }
}
