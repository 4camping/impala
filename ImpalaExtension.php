<?php

namespace Impala;

use Exception,
    Nette\DI\CompilerExtension,
    Nette\PhpGenerator\ClassType;

final class ImpalaExtension extends CompilerExtension {

    private $defaults = ['assets' => 'assets/impala',
        'feeds' => 'feeds',
        'format' => ['date' => ['edit' => 'd.m.Y', 'query'=> 'Y-m-d', 'select' => 'GET_FORMAT(DATE,"EUR")'],
                    'time' => ['edit' => 'Y-m-d H:i:s', 'query' => 'Y-m-d', 'select' => 'GET_FORMAT(DATE,"EUR")']],
        'help' => 'help',
        'npm' => 'bower',
        'keywords' => 'keywords',
        'log' => 'log',
        'pagination' => 20,
        'settings' => 'settings',
        'speed' => 50,
        'spice' => 'spice',
        'upload' => 10,
        'write' => 'write'];

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
        $manifest = (array) json_decode(file_get_contents($parameters['wwwDir'] . '/' . $parameters['impala']['assets'] . '/js/manifest.json'));
        $builder->addDefinition($this->prefix('Builder'))
                ->setFactory('Impala\Builder', [$parameters['impala']]);
        $builder->addDefinition($this->prefix('impalaExtension'))
                ->setFactory('Impala\ImpalaExtension', []);
        $builder->addDefinition($this->prefix('contentForm'))
                ->setFactory('Impala\ContentForm', [$manifest['ContentForm.js']]);
        $builder->addDefinition($this->prefix('exportService'))
                ->setFactory('Impala\ExportService', [$builder->parameters['tempDir']]);
        $builder->addDefinition($this->prefix('grid'))
                ->setFactory('Impala\Grid', [$parameters['appDir'], $manifest['Grid.js'], $parameters['impala']]);
        $builder->addDefinition($this->prefix('filterForm'))
                ->setFactory('Impala\FilterForm', ['']);
        $builder->addDefinition($this->prefix('importForm'))
                ->setFactory('Impala\ImportForm', [$manifest['ImportForm.js']]);
        $builder->addDefinition($this->prefix('helpRepository'))
                ->setFactory('Impala\HelpRepository', [$parameters['impala']['database'], $parameters['impala']['helps']]);
        $builder->addDefinition($this->prefix('keywordsRepository'))
                ->setFactory('Impala\KeywordsRepository', [$parameters['impala']['database'], $parameters['impala']['keywords']]);
        $builder->addDefinition($this->prefix('impala'))
                ->setFactory('Impala\Impala', [$parameters['impala']]);
        $builder->addDefinition($this->prefix('mockRepository'))
                ->setFactory('Impala\MockRepository', [$parameters['impala']['database'], 'test']);
        $builder->addDefinition($this->prefix('mockService'))
                ->setFactory('Impala\MockService');
        $builder->addDefinition($this->prefix('rowForm'))
                ->setFactory('Impala\RowForm', [$manifest['RowForm.js']]);
        $builder->addDefinition($this->prefix('writeRepository'))
                ->setFactory('Impala\WriteRepository', [$parameters['impala']['database'], $parameters['impala']['write']]);
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
