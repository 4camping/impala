<?php

namespace Test;

use Impala\ImportForm,
    Impala\IImportFormFactory,
    Impala\IProcess,
    Impala\MockService,
    Nette\Database\Table\ActiveRow,
    Nette\DI\Container,
    Nette\Reflection\Method,
    Nette\Utils\Strings,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class ImportFormTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var array */
    private $presenters;

    /** @var MockService */
    private $mockService;

    /** @var IImportFormFactory */
    private $class;

    /** @var IProcess */
    private $service;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp(): void {
        Assert::true(is_object($this->mockService = $this->container->getByType('Impala\MockService')), 'MockService is not set.');
        Assert::false(empty($processes = $this->container->findByType('Impala\IProcess')), 'No Impala\IProcess found.');
        Assert::false(empty($service = $processes[rand(0, sizeof($processes) -1)]));
        Assert::true(is_object($this->service = $this->container->getService($service)), 'Get IProcess failed.');
        Assert::false(empty($assets = isset($this->container->parameters['Impala']['assets']) ? $this->container->parameters['Impala']['assets'] : 'assets'));
        Assert::false(empty($manifest = (array) json_decode(file_get_contents($this->container->parameters['wwwDir'] . '/' . $assets . '/Impala/js/manifest.json'))));
        Assert::true(is_object($this->class = new ImportForm($this->container->parameters['wwwDir'] . '/' . $assets . '/Impala/css', 
                                                            $manifest['ImportForm.js'], 
                                                            $this->container->getByType('Nette\Http\IRequest'), 
                                                            $this->container->getByType('Nette\Localization\ITranslator'))), 'IImportFormFactory is not set.');
        $this->presenters = (isset($this->container->parameters['mockService']['import'])) ? $this->container->parameters['mockService']['import'] : [];
    }
    
    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testSetService(): void {
        $mockRepository = $this->container->getByType('Impala\IMock');
        foreach ($this->presenters as $class => $latte) {
            $presenter = $this->mockService->getPresenter($class, $this->presenters[$class]);
            Assert::true(is_object($impala = $presenter->context->getByType('Impala\Impala')), 'Impala is not set.');
            Assert::true(is_object($impala->setGrid($presenter->grid)), 'Impala:setGrid does not return class itself.');
            Assert::true(is_object($impala->setRow($presenter->grid->copy())), 'Impala:setGrid does not return class itself.');
            Assert::true(is_object($presenter->addComponent($this->container->getByType('Impala\Impala'), 'Impala')), 'Attached Impala failed.');
            Assert::true(is_object($impala = $presenter->getComponent('Impala')), 'Impala is not set');
            Assert::same(null, $impala->attached($presenter), 'Impala:attached succeed but method return something. Do you wish to modify test?');
            Assert::true(is_object($presenter), 'Presenter was not set.');
            $service = $presenter->grid->getImport();
            Assert::true(is_object($this->class->setService($service)), 'ImportForm:setService does not return class itself.');
            Assert::same($this->class, $this->class->setService($service), 'ImportForm:setService does not return class itself.');
            Assert::true(is_object($setting = $mockRepository->getTestRow($this->container->parameters['Impala']['feeds'], 
                    ['type' => 'import', 'source' => $presenter->getName() . ':' . $presenter->getAction()])), 'Setting is not set.');
            Assert::true($setting instanceof ActiveRow, 'Import setting is not set in ' . $class . '.');            
            Assert::notSame(null, $setting->mapper, 'Following tests require existing active row for source ' . $setting->feed . '.');
            Assert::false(empty($setting->mapper), 'Mapper for source ' . $setting->feed . ' is not set.');
            Assert::true(is_string($setting->feed), 'Name of feed was not set.');
            $presenter->removeComponent($impala);
        }
    }
    
    public function testAttached(): void {
        Assert::true(is_array($this->presenters), 'No presenter to test on import was set.');
        Assert::false(empty($this->presenters), 'There is no feed for testing.');
        Assert::true(100 > count($this->presenters), 'There is more than 100 available feeds for testing which could process long time. Consider modify test.');
        foreach ($this->presenters as $class => $latte) {
            $presenter = $this->mockService->getPresenter($class, $this->presenters[$class]);
            Assert::true(is_object($presenter), 'Presenter was not set.');
            $this->setUp();
        }
    }

    public function testSucceed(): void {
        $testParameters = ['id' => 1, 'page' => 1];
        foreach ($this->presenters as $class => $latte) {
            $presenter = $this->mockService->getPresenter($class, $latte);
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
            Assert::true(is_object($this->class), 'ImportForm was not set.');
            Assert::true($this->class instanceof IImportFormFactory, 'ImportForm has wrong instantion.');
            Assert::true(empty($this->class->getData()), 'Data has been attached too early.');
            Assert::true(property_exists($this->class, 'translatorRepository'), 'Translator repository was not set');
            Assert::true(is_object($this->class->setService($this->service)), 'IProcess was not set.');
            $csv = __DIR__ . '/' . Strings::webalize(preg_replace('/App|Module|Presenter|action/', '', $class . '-' . $method)) . '.csv';
            Assert::true(is_file($csv), 'Test upload file ' . $csv . ' is not set.');
            $presenter->addComponent($this->class, 'importForm');
            Assert::false(empty($data = $this->class->getData()), 'Data are empty.');
            Assert::true(isset($data['_prepare-progress']), 'Prepare button is missing.');
            $presenter->removeComponent($this->class);
            $this->setUp();
        }
    }
}

id(new ImportFormTest($container))->run();
