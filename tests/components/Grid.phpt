<?php

namespace Test;

use Impala\Grid,
    Impala\MockService,
    Nette\DI\Container,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class GridTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var Grid */
    private $class;

    /** @var MockService */
    private $mockService;

    function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        $this->class = $this->container->getByType('Impala\Grid');
        $this->mockService = $this->container->getByType('Impala\MockService');
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testAttached() {
        Assert::same('Impala\Grid', get_class($this->class), 'Namespace of ' . get_class($this->class) . ' must be exactly Impala as it is used as query parameter in /react/Grid.jsx:getSpice().');
    }

}

id(new GridTest($container))->run();
