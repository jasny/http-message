<?php

namespace Jasny\HttpMessage\ServerRequest;

use Psr\Http\Message\UploadedFileInterface;
use Jasny\HttpMessage\UploadedFile;

/**
 * ServerRequest uploaded files methods
 */
trait UploadedFiles
{
    /**
     * Possible link to $_FILES
     * 
     * @var array
     */
    protected $files;
    
    /**
     * An array tree of UploadedFileInterface instances.
     * This is typically derived from $_FILES
     * 
     * @var array
     */
    protected $uploadedFiles = [];

    
    /**
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    abstract protected function copy();

    
    /**
     * Create an UploadedFile instance.
     * 
     * @param array   $info
     * @param string  $key                   Parameter key
     * @param boolean $assertIsUploadedFile  Assert that the file is actually uploaded
     * @return UploadedFile
     */
    protected function createUploadedFile(array $info, $key, $assertIsUploadedFile)
    {
        return new UploadedFile($info, $key, $assertIsUploadedFile);
    }
    
    /**
     * Group data as provided by $_FILES
     * 
     * @param array   $array
     * @param string  $groupKey
     * @param boolean $assertIsUploadedFile  Assert that the file is actually uploaded
     * @return array An array tree of UploadedFileInterface instances
     */
    protected function groupUploadedFiles(array $array, $groupKey, $assertIsUploadedFile)
    {
        $files = [];
        
        foreach ($array as $key => $values) {
            $parameterKey = isset($groupKey) ? "{$groupKey}[{$key}]" : $key;
            
            if (!is_array($values['error'])) {
                $files[$key] = $this->createUploadedFile($values, $parameterKey, $assertIsUploadedFile);
                continue;
            }
            
            $rearranged = [];
            foreach ($values as $property => $propertyValues) {
                foreach ($propertyValues as $subkey => $value) {
                    $rearranged[$subkey][$property] = $value;
                }
            }
            
            $files[$key] = $this->groupUploadedFiles($rearranged, $parameterKey, $assertIsUploadedFile);
        }
        
        return $files;
    }
    
    /**
     * Set uploaded files
     * @global array $_FILES
     * 
     * @param array $files
     */
    protected function setUploadedFiles(array &$files)
    {
        $isUploadedFiles = ($files === $_FILES);
        
        $this->files =& $files;
        $this->uploadedFiles = $this->groupUploadedFiles($files, null, $isUploadedFiles);
    }
    
    /**
     * Ungroup uploaded files and set $_FILES
     * 
     * @param array $uploadedFiles
     */
    protected function ungroupUploadedFiles(array $uploadedFiles)
    {
        foreach (array_keys($this->files) as $key) {
            unset($this->files[$key]);
        }

        foreach ($uploadedFiles as $key => $item) {
            if (is_array($item)) {
                $group = array_fill_keys(['name', 'type', 'size', 'tmp_name', 'error'], []);
                $this->ungroupUploadedFilesDeep($item, $group['name'], $group['type'], $group['size'],
                    $group['tmp_name'], $group['error']);
                
                $this->files[$key] = $group;
            } elseif ($item instanceof UploadedFileInterface) {
                try {
                    $stream = $item->getStream();
                } catch (\RuntimeException $e) {
                    $stream = null;
                }
                
                $this->files[$key] = [
                    'name' => $item->getClientFilename(),
                    'type' => $item->getClientMediaType(),
                    'size' => $item->getSize(),
                    'tmp_name' => $stream ? $stream->getMetadata('uri') : null,
                    'error' => $item->getError()
                ];
            }
        }
    }
    
    /**
     * Ungroup uploaded files and set a child of $_FILES
     * 
     * @param array $uploadedFiles
     * @param array $name
     * @param array $type
     * @param array $size
     * @param array $tmpName
     * @param array $error
     */
    protected function ungroupUploadedFilesDeep(array $uploadedFiles, &$name, &$type, &$size, &$tmpName, &$error)
    {
        foreach ($uploadedFiles as $key => $item) {
            if (is_array($item)) {
                $name[$key] = [];
                $type[$key] = [];
                $size[$key] = [];
                $tmpName[$key] = [];
                $error[$key] = [];
                
                $this->ungroupUploadedFilesDeep($item, $name[$key], $type[$key], $size[$key], $tmpName[$key],
                    $error[$key]);
            } elseif ($item instanceof UploadedFileInterface) {
                try {
                    $stream = $item->getStream();
                } catch (\RuntimeException $e) {
                    $stream = null;
                }
                
                $name[$key] = $item->getClientFilename();
                $type[$key] = $item->getClientMediaType();
                $size[$key] = $item->getSize();
                $tmpName[$key] = $stream ? $stream->getMetadata('uri') : null;
                $error[$key] = $item->getError();
            }
        }
    }
    
    
    /**
     * Assert that each leaf is an UploadedFileInterface
     * 
     * @param array  $uploadedFiles
     * @param string $groupKey
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    protected function assertUploadedFilesStructure(array $uploadedFiles, $groupKey = null)
    {
        foreach ($uploadedFiles as $key => $item) {
            $parameterKey = isset($groupKey) ? "{$groupKey}[{$key}]" : $key;
            
            if (is_array($item)) {
                $this->assertUploadedFilesStructure($item, $parameterKey);
            } elseif (!$item instanceof UploadedFileInterface) {
                throw new \InvalidArgumentException("'$parameterKey' is not an UploadedFileInterface object, but a "
                    . (is_object($item) ? get_class($item) . ' ' : '') . gettype($item));
            }
        }
    }
    
    
    /**
     * Retrieve normalized file upload data.
     * This is typically derived from the superglobal $_FILES.
     *
     * @return array An array tree of UploadedFileInterface instances
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return static
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $this->assertUploadedFilesStructure($uploadedFiles);
        
        $request = $this->copy();
        $request->uploadedFiles = $uploadedFiles;
        
        if (isset($this->files)) {
            $this->ungroupUploadedFiles($uploadedFiles);
        }
        
        return $request;
    }
}
