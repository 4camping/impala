<?php

namespace App;

use Impala\IBuilder,
    Impala\IImpalaFactory,
    Nette\Application\UI\Presenter,
    Nette\Localization\ITranslator;

class DemoPresenter extends Presenter {

    /** @var IBuilder @inject */
    public $grid;

    /** @var IImpalaFactory @inject */
    public $impalaFactory;

    /** @var IBuilder */
    public $row;

    /** @var string */
    private $collection;

    /** @var ITranslator @inject */
    public $translatorRepository;

    public function startup(): void {
        parent::startup();
        $this->collection = reset($this->context->parameters['collections']);
        $this->row = $this->grid->copy();
    }

    public function actionDefault(): void {
        $this->grid->collection($this->collection);
    }

    public function actionEdit(): void {
        $this->row->collection($this->collection);
    }

    protected function createComponentImpala(): IImpalaFactory {
        return $this->impalaFactory->create()
                    ->setGrid($this->grid)
                    ->setRow($this->row);
    }

}
