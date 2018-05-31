<?php

namespace Impala;

use Nette\ComponentModel\IComponent,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class RowForm extends ReactForm implements IRowFormFactory {

    public function __construct(string $css, string $js, IRequest $request) {
        parent::__construct($css, $js, $request);
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
