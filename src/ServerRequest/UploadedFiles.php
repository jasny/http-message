<?php

namespace Jasny\HttpMessage\ServerRequest;

/**
 * ServerRequest parsed body methods
 */
trait UploadedFiles
{
    /**
     * An array tree of UploadedFileInterface instances.
     * This is typically derived from $_FILES
     * 
     * @var array
     */
    protected $uploadedFiles;
    
    
    /**
     * Create an UploadedFile instance.
     * 
     * @param array $info
     * @return UploadedFile
     */
    protected function createUploadedFile(array $info)
    {
        // TODO
    }
    
    /**
     * Group data as provided by $_FILES
     * 
     * @param array $array
     * @return array An array tree of UploadedFileInterface instances
     */
    protected function groupUploadedFiles(array $array)
    {
        if (!is_array(reset($array))) {
           return $this->createUploadedFile($array);
        }

        $rearranged = [];
        foreach ($array as $property => $values) {
            foreach ($values as $key => $value) {
                $rearranged[$key][$property] = $value;
            }
        }

        foreach ($rearranged as &$value){
           $value = $this->groupUploadedFiles($value);
        }

        return $rearranged;
    }
    
    /**
     * Set uploaded files
     * 
     * @param array $files
     */
    protected function setUploadedFiles(array $files)
    {
        $this->uploadedFiles = !isEmpty($array) ? $this->groupUploadedFiles($files) : [];
    }
    
    
    /**
     * Assert that each leaf is an UploadedFileInterface
     * 
     * @param array $uploadedFiles
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    protected function assertUploadedFilesStructure(array $uploadedFiles)
    {
        // TODO
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
        
        $request = clone $this;
        $request->uploadedFiles = $uploadedFiles;
        
        return $request;
    }
}
