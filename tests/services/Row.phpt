<?php

namespace Test;

use Impala\IMock,
    Impala\IRow,
    Nette\Database\Table\ActiveRow,
    Nette\DI\Container,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class RowTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var IRow */
    private $class;

    /** @var IMock */
    private $mockRepository;

    /** @var ActiveRow */
    private $row;
    
    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        Assert::true(is_object($this->class = $this->container->getByType('Impala\IRow')), 'IRow is not set.');
        Assert::true(is_object($this->mockRepository = $this->container->getByType('Impala\IMock')), 'MockModel is not set.');
        Assert::true(is_object($grid = $this->container->getByType('Impala\IBuilder')), 'IBuilder is not set.');
        Assert::true(is_object($extension = $this->container->getByType('Impala\ImpalaExtension')), 'ImpalaExtension is not set');
        Assert::false(empty($collection = $this->container->parameters['masala']['users']), 'Table of users in config is not set.');
        Assert::false(empty($credentials = $this->container->parameters['mockService']['testUser']), 'Table of users in config is not set.');
        unset($credentials['password']);
        unset($credentials['username']);
        Assert::same($this->class, $this->class->collection($collection), 'Table setter failed.');
        foreach($credentials as $column => $value) {
            Assert::true(is_object($this->class->where($column, $value)), 'IRow:where does not return class itself.');
        }
        Assert::true(is_array($this->row = $this->class->check()), 'Test row is not set for source ' . $collection . '.');
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testSubmit() {
        $after = [];
        $before = [];
        Assert::false(empty($row = $this->class->getData()), 'Test row is empty.');
        foreach($row as $column => $value) {
            if('_id' != $column && null != $value) {
                Assert::false(empty($before[$column] = 'test'), 'Assign value failed.');
                Assert::false(empty($after[$column] = $value), 'Assign value failed.');
                break;
            } else if(is_numeric($value)) {

            }
        }
        $clauses['_id'] = $this->class->_id->__toString();
        $after['_id'][$column] = $this->class->_id;
        $before['_id'][$column] = $this->class->_id;
        Assert::false(empty($this->mockRepository->getTestRow($this->class->getCollection(), $before['_id'])), 'Concated data keys should exist in test table.');
        Assert::true(is_array($this->mockRepository->getTestRow($this->class->getCollection(), $clauses)), 'Concated data keys should not exist in test table.');
        Assert::true(isset($after['_id']), 'Primary keys are not set.');
        Assert::true(isset($before['_id']), 'Primary keys are not set.');
    }

}

id(new RowTest($container))->run();
