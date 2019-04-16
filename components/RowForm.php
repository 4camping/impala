<?php

namespace Impala;

use Nette\ComponentModel\IComponent,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class RowForm extends ReactForm implements IRowFormFactory {

    public function __construct(string $css, string $js, IRequest $request) {
        parent::__construct($css, $js, $request);
        $this->monitor(IPresenter::class, [$this, 'attached']);
    }

    public function attached(IComponent $presenter): void {
    }

    public function create(): ReactForm {
        return $this;
    }

}

interface IRowFormFactory {

    public function create(): ReactForm;
}
