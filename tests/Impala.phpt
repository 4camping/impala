<?php

namespace Test;

use Impala\IGridFactory;
use Impala\Impala,
    Impala\MockService,
    Nette\DI\Container,
    Nette\Reflection\Method,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../bootstrap.php';

/** @author Lubomir Andrisek */
final class ImpalaTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var Impala */
    private $class;

    /** @var MockService */
    private $mockService;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        $this->mockService = $this->container->getByType('Impala\MockService');
        $this->class = $this->container->getByType('Impala\Impala');
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
        stream_wrapper_restore('php');
    }

    public function testAttached() {
        Assert::true(file_exists($path = $this->container->parameters['appDir'] . '/Impala/'), 'Impala folder does not exist in default folder. Please modify test.');
        $columns = scandir($path);
        foreach ($columns as $column) {
            if (0 < substr_count($column, 'column') and 'column.latte' != $column) {
                Assert::same(0, preg_match('/{\*|\*}/', file_get_contents($path . $column)), 'There is comment mark {* or *} in latte ' . $column . ' Impala.');
                Assert::true(1 < count($presenter = explode('.', $column)), 'In column name is not enough arguments to assign presenter.');
                Assert::true(class_exists($class = 'App\\' . ucfirst($presenter[0]) . 'Module\\' . ucfirst($presenter[1]) . 'Presenter'), $class . ' does not exist.');
                if (isset($presenter[2]) and 'column' != $presenter[2]) {
                    Assert::false(empty($method = 'action' . ucfirst($presenter[2])), 'Assigned method is empty string.');
                    Assert::true(is_object($object = new $class()), 'Instatiation of ' . $class . ' failed.');
                    Assert::true(method_exists($object, $method), $class . ' must have method ' . $method . '.');
                    Assert::true(is_object($reflection = new Method(get_class($object), $method)), 'Reflection failed.');
                    Assert::notSame(0, count($parameters = $reflection->getParameters()), 'Method ' . $method . ' of class ' . $class . ' should have one parameter at least. Do you wish to modify test?');
                }
            }
        }
        Assert::true(is_object($extension = $this->container->getByType('Impala\ImpalaExtension')));
        Assert::false(empty($config = $extension->getConfiguration($this->container->parameters)));
        Assert::true(is_object($settings = $this->mockService->getUser()->getIdentity()->getData()[$config['masala']['settings']]), 'Test user does not have settings.');
        Assert::false(empty($settings = (array) $settings), 'User setting is not set.');
        Assert::false(empty($setting = reset($settings)), 'User setting is not set');
        $_POST['test'] = true;
        Assert::false(empty($setting = (array) $setting), 'User setting is empty.');
        Assert::false(empty($_POST), 'Post data cannot be empty so IPresenter:sendResponse would be not mocked.');
        $presenters = $this->mockService->getPresenters('IImpalaFactory');
        $_POST = [];
        foreach ($presenters as $class => $presenter) {
            if(isset($this->container->parameters['mockService']['presenters'][$class])) {
                $testParameters = $this->container->parameters['mockService']['presenters'][$class];
            } else if(isset($this->container->parameters['mockService']['testParameters'])) {
                $testParameters = $this->container->parameters['mockService']['testParameters'];
            } else {
                $testParameters = [];
            }
            echo 'testing ' . $presenter . "\n";
            Assert::true(is_array($parameters = $presenter->request->getParameters('action')), 'Parameters have not been set in ' . $class . '.');
            Assert::notSame(6, strlen($method = 'action' . ucfirst(array_shift($parameters))), 'Action method of ' . $class . ' is not set.');
            Assert::true(is_object($reflection = new Method($class, $method)));
            $arguments = [];
            foreach ($reflection->getParameters() as $parameter) {
                Assert::true(isset($testParameters[$parameter->getName()]), 'There is no test parameters for ' . $parameter->getName() . ' in ' . $class . '.');
                $arguments[$parameter->getName()] = $testParameters[$parameter->getName()];
            }
            Assert::true(method_exists($class, $method), 'According to latte file should exist method ' . $method . ' in ' . $class . '.');
            Assert::true(is_string($source = $presenter->grid->getCollection()), 'Source set in method ' . $method . ' of ' . $class . ' is not set.');
            Assert::true(is_object($presenter->grid->where('_id', null)), 'Grid setter method does not return class itself.');
            $this->class->setGrid($presenter->grid);
            $presenter->addComponent($this->class, 'IImpalaFactory');
            Assert::true(is_object($grid = $presenter->grid), 'Grid IBuilder is not set.');
            Assert::same($source, $presenter->grid->getCollection(), 'Source ' . $source . ' for Impala IBuilder was not set.');
            Assert::false(isset($presenter->grid->select), 'Select in IBuilder should be private.');
            Assert::false(isset($presenter->grid->join), 'Join in IBuilder should be private.');
            Assert::false(isset($presenter->grid->leftJoin), 'Left join in IBuilder should be private.');
            Assert::false(isset($presenter->grid->innerJoin), 'Inner join in IBuilder should be private.');
            Assert::true(is_array($filters = (null != $presenter->grid->getFilters()) ? $presenter->grid->getFilters() : []), 'Filters in Impala IBuilder are not set.');
            Assert::same($presenter->grid, $presenter->grid->collection($source), 'Set table of VO does not return class itself.');
            Assert::true(is_array($columns = $presenter->grid->getColumns()), 'Collection columns are not defined.');
            Assert::true(is_object($grid = $this->mockService->getPrivateProperty($this->class, 1)), 'Impala builder is not set.');
            Assert::true(is_array($renderColumns = $presenter->grid->getColumns()), 'No columns was rendered.');
            foreach($renderColumns as $column => $annotation) { break; }
            Assert::true(empty($_POST), 'Post data have to be empty so IGridFactory:handleSetting would not mismatched settings.');
            $_POST[$column] = 'true';
            Assert::same(sizeof($_POST), 1, 'More test columns than expected.');
            Assert::false(empty($_POST), 'No column to annotate is set.');
            Assert::true(is_object($gridFactory = $this->mockService->getPrivateProperty($this->class, 2)), 'IGridFactory is not set.');
            Assert::true($gridFactory instanceof IGridFactory, 'GridFactory has wrong instation.');
            Assert::same($gridFactory, $gridFactory->setGrid($grid), 'GridFactory::setGrid does not return class itself.');
            Assert::same($presenter, $presenter->addComponent($gridFactory, 'gridFactory'), 'IPresenter::addComponent does not return class itself.');
            Assert::true(is_object($filterForm = $this->mockService->getPrivateProperty($gridFactory, 3)), 'FilterForm is not set.');
            //Assert::false(empty($filterForm->getData()), 'No data for filterForm.');
            Assert::true(isset($_POST[$column]), 'Test $_POST data were unexpected overwrited.');
            $this->mockService->setPost($_POST);
            Assert::same(null, $gridFactory->handleSetting(), 'Grid::handleSetting failed.');
            $_POST[$column] = 'false';
            Assert::same(null, $gridFactory->handleSetting(), 'Grid::handleSetting failed.');
            $notShow = [];
            $overload = $presenter->grid->getColumns();
            echo '@todo: solved overloading of hidden annotation';
            dump($overload);
            foreach ($columns as $column) {
                if (isset($overload[$column['name']]) && 0 == substr_count($column['vendor']['Comment'], '@hidden')) {
                } else if (0 < substr_count($column['vendor']['Comment'], '@hidden')) {
                    $notShow[$column['name']] = $column['name'];
                }
            }
            /*Assert::false(empty($this->class->getGrid()->getColumns()),'Columns are not set.');
            foreach ($renderColumns as $key => $renderColumn) {
                if (isset($notShow[$key])) {
                    Assert::true(is_object($reflector = new \ReflectionClass($class)), 'Reflection is not set.');
                    Assert::false(empty($file = $reflector->getFileName()), 'File of ' . $class . ' is not set.');
                    Assert::false(is_object($handle = fopen($file, 'r+')), 'Open tested controller failed.');
                    echo $file . "\n";
                    $read = false;
                    while (!feof($handle)) {
                        $line = fgets($handle);
                        if (preg_match('/' . $method . '/', $line)) {
                            $read = true;
                        } elseif (true == $read and preg_match('/\}/', $line)) {
                            break;
                        } elseif (true == $read and preg_match('/' . $key . '/', $line)) {
                            echo $line;
                            Assert::same(0, preg_match('/@hidden/', $line), 'Discovered @hidden annotation in rendered ' . $source . '.' . $key . ' in ' . $class . ':' . $method);
                        }
                    }
                }
            }*/
            $this->setUp();
        }
    }

    public function testHandleExport() {
        Assert::same(null, $this->mockService->setPost(['offset'=>1]), 'MockService:setPost succeed but it does return something. Do you wish to modify test?');
        Assert::true(is_object($this->class->setGrid($this->container->getByType('Impala\IBuilder')->export(true))));
    }

    public function testHandleRun() {
        $row = $this->container->getByType('Impala\IRow');
        Assert::false(empty($tables = $this->container->parameters['collections']), 'Test source was not set.');
        Assert::false(empty($excluded = $this->container->parameters['mockService']['excluded']), 'No tables to excluded. Do you wish to modify test?');
        foreach($excluded as $exclude) {
            unset($tables[$exclude]);
        }
        Assert::false(empty($key = array_rand($tables)), 'Test source was not set.');
        Assert::false(empty($source = $this->container->parameters['collections'][$key]), 'Test source was not set.');
        Assert::true(empty($columns = $row->collection($source)->getData()), 'Data of collection ' . $source . ' was not set.');
        Assert::false(empty($source = $this->container->parameters['collections']['helps']), 'Test source for collection helps was not set.');
        Assert::true(empty($columns = $row->collection($source)->getData()), 'Data of collection ' . $source . ' was not set.');
        Assert::false(isset($columns[1]), 'Json column was set');
        /*Assert::same('json', $columns[1]['name'], 'Json column was not set');
        Assert::false(empty($comment = $columns[1]['vendor']['Comment']), 'Json column comment should be not empty');
        Assert::same(1, substr_count($comment, '@hidden'), $source . '.json should have disabled comment via annotation @hidden.');*/
    }

    public function testRender() {
        $latte = $this->container->parameters['appDir'] . '/Impala/templates/grid.latte';
        Assert::true(is_file($latte), 'Latte file for grid is not set.');
        Assert::false(empty($grid = file_get_contents($latte)), 'Latte file is empty.');
        Assert::true(0 < substr_count($grid, '$(this).datetimepicker({ format: $(this).attr(\'data\'), locale: {$locale} })'), 'It seems that datatimepicker in javascript has unintended format. Did you manipulated just with space?');
        Assert::true(0 < substr_count($grid, '<script src="{$js}"></script>'), 'It seems that react component is not included.');
    }

}

id(new ImpalaTest($container))->run();
