<?php

namespace Impala;

use Exception,
    Nette\DI\CompilerExtension,
    Nette\PhpGenerator\ClassType;

/** @author Lubomir Andrisek */
final class ImpalaExtension extends CompilerExtension {

    private $defaults = ['assets' => 'node_modules/npm-impala',
        'feeds' => 'feeds',
        'format' => ['date' => ['build' => 'd.m.Y', 'query'=> 'Y-m-d', 'select' => 'GET_FORMAT(DATE,"EUR")'],
                    'time' => ['build' => 'Y-m-d H:i:s', 'query' => 'Y-m-d', 'select' => 'GET_FORMAT(DATE,"EUR")']],
        'help' => 'help',
        'npm' => 'node_modules',
        'log' => 'log',
        'pagination' => 20,
        'settings' => 'settings',
        'speed' => 50,
        'spice' => 'spice',
        'tests' => ['user' => ['id' => 1, 'password' => 'password', 'username' => 'username'],
                    'parameters' => ['date' => '2017-4-12', 'id' => 4574, 'limit' => 10]],
        'upload' => 10];

    public function getConfiguration(array $parameters) {
        foreach($this->defaults as $key => $parameter) {
            if(!isset($parameters['impala'][$key])) {
                $parameters['impala'][$key] = $parameter;
            }
        }
        return $parameters;
    }
    
    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();
        $parameters = $this->getConfiguration($builder->parameters);
        $manifest = (array) json_decode(file_get_contents($parameters['wwwDir'] . '/' . $parameters['impala']['assets'] . '/manifest.json'));
        $builder->addDefinition($this->prefix('builder'))
                ->setFactory('Impala\Builder', [$parameters['impala']]);
        $builder->addDefinition($this->prefix('impalaExtension'))
                ->setFactory('Impala\ImpalaExtension', []);
        $builder->addDefinition($this->prefix('exportFacade'))
                ->setFactory('Impala\ExportFacade', [$builder->parameters['tempDir']]);
        $builder->addDefinition($this->prefix('emptyRow'))
                ->setFactory('Impala\EmptyRow');
        $builder->addDefinition($this->prefix('grid'))
                ->setFactory('Impala\Grid', [$parameters['appDir'], $manifest['Grid.js'], $parameters['impala']]);
        $builder->addDefinition($this->prefix('filterForm'))
                ->setFactory('Impala\FilterForm', ['']);
        $builder->addDefinition($this->prefix('helpRepository'))
                ->setFactory('Impala\HelpRepository', [$parameters['impala']['help']]);
        $builder->addDefinition($this->prefix('impala'))
                ->setFactory('Impala\Impala', [$parameters['impala']]);
        $builder->addDefinition($this->prefix('mockRepository'))
                ->setFactory('Impala\MockRepository');
        $builder->addDefinition($this->prefix('mockFacade'))
                ->setFactory('Impala\MockFacade');
        $builder->addDefinition($this->prefix('rowForm'))
                ->setFactory('Impala\RowForm', [$manifest['RowForm.js']]);
    }

    public function beforeCompile() {
        if(!class_exists('Nette\Application\Application')) {
            throw new MissingDependencyException('Please install and enable https://github.com/nette/nette.');
        }
        parent::beforeCompile();
    }

    public function afterCompile(ClassType $class) {
    }

}

class MissingDependencyException extends Exception { }
