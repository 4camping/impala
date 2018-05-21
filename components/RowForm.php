<?php

namespace Impala;

use Nette\ComponentModel\IComponent,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class RowForm extends ReactForm implements IRowFormFactory {

    public function __construct(string $js, string $css, IRequest $request) {
        parent::__construct($js, $css, $request);
    }

    public function create(): IReactFormFactory {
        return $this;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
    }

}

interface IRowFormFactory {

    public function create(): IReactFormFactory;
}
