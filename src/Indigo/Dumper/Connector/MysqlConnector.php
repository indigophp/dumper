<?php
/*
 * This file is part of the Indigo Dumper package.
 *
 * (c) IndigoPHP Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indigo\Dumper\Connector;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use PDO;

/**
 * MySQL Connector
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class MysqlConnector extends AbstractConnector
{
    /**
     * MySQL Connector constructor
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $options = $this->resolveOptions($options);

        $this->pdo = new PDO(
            $this->createDSN(),
            $options['username'],
            $options['password'],
            $options['pdo_options']
        );

        // This is needed on some PHP versions
        $this->pdo->exec("SET NAMES utf8");
    }

    /**
     * Create DSN for PDO
     * @return string DSN
     */
    protected function createDSN()
    {
        $dsn = 'mysql:';

        if (empty($this->options['unix_socket'])) {
            $dsn .= 'host=' . $this->options['host'] . ';port=' . $this->options['port'];
        } else {
            $dsn .= 'unix_socket=' . $this->options['unix_socket'];
        }

        $dsn .= ';dbname=' . $this->options['database'];

        return $dsn;
    }

    /**
     * Set default MySQL connection and dump details
     *
     * @param OptionsResolverInterface $resolver
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array(
            'host'          => 'localhost',
            'port'          => 3306,
            'drop_database' => false,
            'use_lock'      => false,
            'lock_table'    => true,
            'pdo_options'   => array(
                PDO::ATTR_PERSISTENT         => true,
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
            ),
        ));

        $resolver->setOptional(array('unix_socket'));

        $resolver->setRequired(array('username', 'password'));

        $resolver->setAllowedTypes(array(
            'host'          => 'string',
            'port'          => 'integer',
            'unix_socket'   => 'string',
            'username'      => 'string',
            'password'      => 'string',
            'drop_database' => 'bool',
            'use_lock'      => 'bool',
            'lock_table'    => 'bool',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function dumpHeader()
    {
        $header = parent::dumpHeader();

        if ($this->options['drop_database']) {
            $header .= $this->dumpAddDropDatabase();
        }

        return $header;
    }

    /**
     * {@inheritdoc}
     */
    protected function showObjects($view = false)
    {
        $query = $this->pdo->prepare('SHOW FULL TABLES WHERE `Table_type` LIKE :type');
        $query->execute(array(':type' => $view ? 'VIEW' : 'BASE TABLE'));

        return $query->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    protected function dumpDisableForeignKeysCheck()
    {
        return "-- Ignore checking of foreign keys\n" .
            "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    }

    /**
     * {@inheritdoc}
     */
    protected function dumpEnableForeignKeysCheck()
    {
        return "\n-- Unignore checking of foreign keys\n" .
            "SET FOREIGN_KEY_CHECKS = 1;\n\n";
    }

    /**
     * Dump DROP DATABASE
     *
     * @return string Dump
     */
    protected function dumpAddDropDatabase()
    {
        $charset = $this->pdo->query("SHOW VARIABLES LIKE 'character_set_database';")->fetchColumn(1);
        $collation = $this->pdo->query("SHOW VARIABLES LIKE 'collation_database';")->fetchColumn(1);

        return "/*!40000 DROP DATABASE IF EXISTS `" . $this->options['database'] . "`*/;\n".
            "CREATE DATABASE /*!32312 IF NOT EXISTS*/ `" . $this->options['database'] .
            "` /*!40100 DEFAULT CHARACTER SET " . $charset .
            " COLLATE " . $collation . "*/;\n" .
            "USE `" . $this->options['database'] . "`;\n\n";
    }

    /**
     * {@inheritdoc}
     */
    public function dumpTableSchema($table)
    {
        $dump = parent::dumpTableSchema($table);

        $dump .= $this->pdo->query("SHOW CREATE TABLE `$table`")->fetchColumn(1) . ";\n\n";

        return $dump;
    }

    /**
     * {@inheritdoc}
     */
    public function dumpViewSchema($view)
    {
        $dump = parent::dumpViewSchema($view);

        $dump .= $this->pdo->query("SHOW CREATE VIEW `$view`")->fetchColumn(1) . ";\n\n";

        return $dump;
    }

    /**
     * {@inheritdoc}
     */
    protected function startTransaction()
    {
        $this->pdo->exec("SET GLOBAL TRANSACTION ISOLATION LEVEL REPEATABLE READ; START TRANSACTION");
    }

    /**
     * {@inheritdoc}
     */
    protected function commitTransaction()
    {
        $this->pdo->exec('COMMIT');
    }

    /**
     * {@inheritdoc}
     */
    public function preDumpTableData($table)
    {
        $dump = parent::preDumpTableData($table);

        if ($this->options['use_lock']) {
            $this->pdo->exec("LOCK TABLES `$table` READ LOCAL");
        }

        if ($this->options['lock_table']) {
            $dump .= "LOCK TABLES `$table` WRITE;\n";
        }

        return $dump;
    }

    /**
     * {@inheritdoc}
     */
    public function postDumpTableData($table)
    {
        $dump = '';

        if ($this->options['use_lock']) {
            $this->pdo->exec('UNLOCK TABLES');
        }

        if ($this->options['lock_table']) {
            $dump .= "UNLOCK TABLES;\n";
        }

        $dump .= parent::postDumpTableData($table);

        return $dump;
    }
}
