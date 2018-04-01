<?php

namespace Test;

use Impala\IBuilder,
    Impala\IMock,
    Impala\MockService,
    Nette\Database\Table\ActiveRow,
    Nette\DI\Container,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class RowFormTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var array */
    private $tables;

    /** @var RowForm */
    private $class;

    /** @var IMock */
    private $mockRepository;

    /** @var MockService */
    private $mockService;

    /** @var IBuilder */
    private $row;

    function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp(): void {
        $this->mockRepository = $this->container->getByType('Impala\IMock');
        $this->mockService = $this->container->getByType('Impala\MockService');
        $this->class = $this->container->getByType('Impala\IRowFormFactory');
        $this->row = $this->container->getByType('Impala\IBuilder')->copy();
        $this->collections = $this->mockRepository->getTestCollections();
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    private function getRandomTable(): string {
        $this->setUp();
        Assert::false(empty($tables = $this->container->parameters['collections']), 'Test tables are not set.');
        Assert::false(empty($excluded = $this->container->parameters['mockService']['excluded']), 'No tables to excluded. Do you wish to modify test?');
        foreach($excluded as $exclude) {
            unset($tables[$exclude]);
        }
        Assert::false(empty($key = array_rand($tables)), 'Test source is not set.');
        Assert::false(empty($source = $this->container->parameters['collections'][$key]), 'Test table is not set.');
        return $source;
    }
    
    public function testIsSignalled() {
        Assert::false(empty($source = $this->getRandomTable()), 'Test table is not set.');
        Assert::false(empty($excluded = $this->container->parameters['mockService']['excluded']), 'No tables to excluded. Do you wish to modify test?');
        if(!in_array($source, $excluded)) {
            Assert::false(empty($setting = $this->mockRepository->getTestRow($source)), 'There is no test row for source ' . $source);
            $parameters = isset($setting['score']) ? ['score' => $setting['score']] : ['score' => 1];
            $presenter = $this->mockService->getPresenter('App\DemoPresenter', $this->container->parameters['appDir'] . '/Impala/demo/default.latte', $parameters);
            Assert::true(is_object($presenter), 'Presenter was not set.');
            Assert::true(is_bool($this->class->isSignalled()), 'Signalled method should return boolean value.');
        }
    }

    public function testAttached() {
        Assert::true(is_object($grid = $this->container->getByType('Impala\IBuilder')));
        $presenter = $this->mockService->getPresenter('App\DemoPresenter', $this->container->parameters['appDir'] . '/Impala/demo/edit.latte', []);
        Assert::true(is_object($presenter), 'Presenter was not set.');
        $presenter->addComponent($this->class, 'IRowFormFactory');
        Assert::true(is_array($serialize = (array) $this->class), 'Serialization of ' . $this->class->getName() . ' failed.');
        Assert::true(is_array($variables = array_slice($serialize, 3, 1)), 'Extract IBuilder for testing failed');
        Assert::true($presenter->row instanceof IBuilder, 'IBuilder is not set.');
        Assert::false(empty($source = $presenter->row->getCollection()), 'Source table is empty.');
        Assert::true(is_string($this->mockService->getPrivateProperty($this->class, 0)), 'Javascript directory is not set.');
        echo $source . "\n";
        $required = false;
        Assert::true(empty($columns = $presenter->row->getColumns()), 'There are columns to test.');
        foreach ($columns as $annotation => $value) {
            $key = preg_replace('/\@(.*)/', '', $annotation);
            if(!preg_match('/\_id/', $annotation) &&
                preg_match('/@required/', $annotation) &&
                0 === substr_count($annotation, '@unedit')) {
                $required = $key;
            } else if(preg_match('/\_id/', $annotation)) {
                $primary .= $key . ', ';
            }
            $notEdit = (preg_match('/\_id/','/\@unedit/')) ? $key : 'THISNAMECOMPONENTSHOULDNEVERBEUSED';
        }
        Assert::true(empty($data = $this->class->getData()), 'Some data was attached.');
        if (is_string($required)) {
            if(!isset($data[$required]['Validators']['required']) ) {
                echo json_encode($data[$required]);
            }
            Assert::true(isset($data[$required]['Validators']['required']), 'Component ' . $required . ' from table ' . $source . ' should be required as it is not nullable column.');
        }
        Assert::false(isset($this->class[$notEdit]), 'Component ' . $notEdit . 'has been render even if it has annotation @unedit');
        Assert::false(is_bool($required) && empty($primary), 'Table ' . $this->row->getCollection() . ' has all columns with default null. Are you sure it is not dangerous?');
        Assert::notSame(true, isset($data[$notEdit]));
    }

    public function testColumnComments() {
        $this->setUp();
        $tables = [];
        $excluded = (isset($this->container->parameters['mockService']['excluded'])) ? $this->container->parameters['mockService']['excluded'] : [];
        shuffle($this->collections);
        if(isset($this->container->parameters['mockService']['prefix'])) {
            Assert::false(empty($structure = 'Tables_in_' . preg_replace('/.*dbname\=/', '', $this->container->parameters['database']['dsn'])), 'Structure of DB is not set.');
            foreach ($this->collections as $table) {
                if ($this->container->parameters['mockService']['prefix'] != substr($table->$structure, 0, 7) and ! in_array($table->$structure, $excluded)) {
                    $tables[] = $table->$structure;
                }
            }
        }
        foreach ($tables as $name) {
            Assert::false(empty($name));
            Assert::true(is_integer($index = rand(0, count($this->collections) - 1)), 'Index for random table is not set.');
            Assert::true(($table = $this->collections[$index]) instanceof IRow, 'Table to test column comments is not set.');
            Assert::true(is_string($name), 'Table name is not defined.');
            Assert::true(is_array($columns = $this->row->getColumns($name)), 'Table columns are not defined.');
        }
        foreach ($this->container->parameters['masala'] as $key => $compulsory) {
            if(preg_match('/[A-Za-z]+\.[A-Za-z]+/', $key)) {
                foreach($this->container->parameters['masala'][$key] as $call) {
                    $parameters = (isset($call['parameters'])) ? $call['parameters'] : null; 
                    $defaults = $this->mockService->getCall($call['service'], $call['method'], $parameters, $this->class);
                    Assert::true(is_array($defaults), 'Call ' . $key . ' from masala config failed.');
                    $name = preg_replace('/\.(.*)/', '', $key);
                    $setting = $this->mockRepository->getTestRow($name);
                    $this->row->collection($name);
                    Assert::true($setting instanceof ActiveRow or in_array($name, $excluded), 'There is no row in ' . $name . '. Are you sure it is not useless?');
                    if ($setting instanceof ActiveRow) {
                        Assert::true(is_array($columns = $this->row->getColumns($name)), 'Table columns are not defined.');
                        $data = $this->class->getData();
                        foreach ($columns as $column) {
                            if (0 === substr_count($column['vendor']['Comment'], '@unedit') and 'PRI' !== $column['vendor']['Key']) {
                                Assert::true(isset($data[$column['name']]), 'Column ' . $column['name'] . ' in table ' . $name . ' was not draw as component in Impala\IRowFormFactory..');
                            } else {
                                Assert::false(isset($data[$column['name']]), 'Column ' . $column['name'] . ' in table ' . $name . ' was draw as component in Impala\IRowFormFactory even it should not.');
                            }
                        }
                        $this->setUp();
                    }
                }
            }
        }
    }


}

id(new RowFormTest($container))->run();
