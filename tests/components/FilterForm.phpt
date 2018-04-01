<?php

namespace Test;

use Impala\IFilterFormFactory,
    Impala\IReactFormFactory,
    Impala\Impala,
    Impala\MockService,
    MongoDB\BSON\UTCDateTime,
    Nette\DI\Container,
    Nette\Reflection\Method,
    Tester\Assert,
    Tester\TestCase;


$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class FilterFormTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var MockService */
    private $mockService;

    /** @var IFilterFormFactory */
    private $class;

    /** @var array */
    private $presenters;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        $this->mockService = $this->container->getByType('Impala\MockService');
        $this->class = $this->container->getByType('Impala\IFilterFormFactory');
        $this->presenters = ['App\DemoPresenter' => $this->container->parameters['appDir'] . '/Impala/demo/default.latte'];
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testAttached() {
        Assert::true(is_array($this->presenters), 'No presenter to test on import was set.');
        Assert::false(empty($this->presenters), 'There is no feed for testing.');
        Assert::true(100 > count($this->presenters), 'There is more than 100 available feeds for testing which could process long time. Consider modify test.');
        $row = $this->container->getByType('Impala\IRow');
        foreach($this->container->parameters['collections'] as $collection) {
            $row->collection($collection)->check();
            foreach($row->getData() as $column => $value) {
                if('_id' != $column && $value instanceof UTCDateTime) {
                    Assert::false(empty($date = $collection . '.' . $column), 'Datetime column is not set.');
                    break;
                }
            }
        }
        Assert::true(isset($date), 'No datetime column to test for');
        foreach ($this->presenters as $class => $latte) {
            $presenter = $this->mockService->getPresenter($class, $latte);
            Assert::true(is_object($presenter), 'Presenter was not set.');
            Assert::true(is_object($presenter->grid = $this->container->getByType('Impala\IBuilder')), 'Presenter grid was not set.');
            Assert::true(is_object($presenter->grid->collection($collection)
                                            ->select(['id' => '_id'])
                                            ->where($date . ' >', date('Y-m-d', strtotime('-3 month')))
                                            ->where($date . ' <',date('Y-m-d', strtotime('now')))
                                            ), 'Table of grid was not set.');
            Assert::true(is_object($masala = $this->container->getByType('Impala\Impala')), 'Impala is not set.');
            Assert::true(is_object($masala->setGrid($presenter->grid)), 'Impala:setGrid does not return class itself.');
            Assert::true(is_object($presenter->addComponent($masala, 'masala')), 'Attached Impala failed.');
            Assert::true(is_object($masala = $presenter->getComponent('masala')), 'Impala is not set');
            Assert::true(is_object($grid = $this->container->getByType('Impala\IGridFactory')), 'IGridFactory is not set.');
            Assert::same($grid, $grid->setGrid($presenter->grid), 'Impala\IGridFactory:setGrid does not return class itself.');
            Assert::true(is_object($presenter->addComponent($grid, 'grid')), 'Attaching grid to presenter failed.');
            Assert::same(null, $grid->attached($presenter), 'Grid:attached succeeded but method does return something. Do you wish to modify test?');
            Assert::true($masala instanceof Impala, 'Impala has wrong instation.');
            Assert::false(empty($presenter->grid->getColumns()), 'Columns are not set in ' . $class . '.');
            Assert::true(is_object($filterForm = $this->mockService->getPrivateProperty($grid, 3)), 'Extraction filterForm from Grid failed.');
            Assert::true($filterForm instanceof IReactFormFactory, 'Form filter has wrong instation.');
            Assert::true(is_array($parameters = $presenter->request->getParameters('action')), 'Parameters have not been set in ' . $class . '.');
            Assert::notSame(6, strlen($method = 'action' . ucfirst(array_shift($parameters))), 'Action method of ' . $class . ' is not set.');
            Assert::true(is_object($reflection = new Method($class, $method)));
            $arguments = [];
            foreach ($reflection->getParameters() as $parameter) {
                Assert::true(isset($testParameters[$parameter->getName()]), 'There is no test parameters for ' . $parameter->getName() . ' in ' . $class . '.');
                $arguments[$parameter->getName()] = $testParameters[$parameter->getName()];
            }
            Assert::true(is_object($presenter), 'Presenter is not class.');
            Assert::true(in_array('addComponent', get_class_methods($presenter)), 'Presenter has no method addComponent.');
            Assert::true(is_object($this->class), 'FilterForm was not set.');
            Assert::true($this->class instanceof IReactFormFactory, 'FilterForm has wrong instantion.');
            Assert::false(empty($data = $filterForm->getData()), 'Form values are not set in class ' . $class . ' for source ' . $collection);
            Assert::true(is_array($data), 'FilterForm values are not set.');
            Assert::true(property_exists($this->class, 'translatorRepository'), 'Translator repository was not set');
            $masala->attached($presenter);
            Assert::false(empty($methods = get_class_methods($filterForm)), 'Impala\IFilterFormFactory does not have any method.');
            Assert::false(isset($methods['succeeded']) or isset($methods['formSucceeded']) or isset($methods['submit']), 
                    'Impala\IFilterForm:succeed is redundant as submit is provided by react component of Grid.');
            $presenter->removeComponent($masala);
            $this->setUp();
        }
    }

}

id(new FilterFormTest($container))->run();
