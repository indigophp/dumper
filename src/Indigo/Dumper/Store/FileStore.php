<?php
/*
 * This file is part of the Indigo Dumper package.
 *
 * (c) IndigoPHP Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indigo\Dumper\Store;

/**
 * File Store
 *
 * Store file without compression
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class FileStore extends AbstractStore
{
    /**
     * File path
     *
     * @var string
     */
    protected $file;

    /**
     * File handle
     *
     * @var resource
     */
    protected $handle;

    public function __construct($file = null)
    {
        $this->file = $this->makeFile($file);
        $this->handle = fopen($this->file, 'w+');
    }

    public function __destruct()
    {
        @fclose($this->handle);
    }

    /**
     * Get file
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get or create file
     *
     * @param  string $name
     * @return string
     */
    protected function makeFile($name)
    {
        if ($path = dirname($name) and $path !== '.') {
            $path = realpath($path);
        } else {
            $path = sys_get_temp_dir();
        }

        if ($name = basename($name)) {
            return $path  . '/' . $name;
        } else {
            return tempnam($path, 'dump_');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($data)
    {
        return fwrite($this->handle, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRead()
    {
        rewind($this->handle);

        return stream_get_contents($this->handle);
    }
}
