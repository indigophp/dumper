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
 * File Store Test
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class FileStoreTest extends StoreTest
{
    public function setUp()
    {
        $this->store = new FileStore;
    }

    public function testFile()
    {
        $this->assertFileExists($this->store->getFile());
    }

    public function tearDown()
    {
        unset($this->store);
    }

    public function testFilePath()
    {
        $test = '/tmp/test.file';
        $store = new FileStore($test);
        $this->assertEquals($test, $store->getFile());
    }
}
