<?php
/*
 * This file is part of the Indigo Dump package.
 *
 * (c) IndigoPHP Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indigo\Dumper\Store;

class FileStore extends AbstractStore
{
    /**
     * File handler
     *
     * @var resource
     */
    protected $file;

    public function __construct($file = null)
    {
        is_null($file) and $file = tempnam(sys_get_temp_dir(), 'dump_');
        $this->file = fopen($file, 'w');
    }

    public function __destruct()
    {
        fclose($this->file);
    }

    /**
     * {@inheritdoc}
     */
    public function write($data)
    {
        parent::write($data);
        return fwrite($this->file, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        parent::read();
        rewind($this->file);
        return stream_get_contents($this->file);
    }
}
