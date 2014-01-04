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

use Flysystem\Filesystem;
use Indigo\Dumper\Exception\StoreNotWritableException;
use Indigo\Dumper\Exception\StoreNotReadableException;

/**
 * Flysystem Store
 *
 * Store data in Flysystem
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class FlysystemStore implements AbstractStore
{
    /**
     * Filesystem object
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * File name or path
     *
     * @var string
     */
    protected $name;

    public function __construct(Filesystem $filesystem, $name)
    {
        $this->filesystem = $filesystem;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function write($data)
    {
        if (!$this->writable) {
            throw new StoreNotWritableException('Store is not writable');
        }

        $this->filesystem->put($this->name, $data);
        return strlen($data);
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        if (!$this->readable) {
            throw new StoreNotReadableException('Store is not readable');
        }

        return $this->filesystem->read($this->name);
    }
}