<?php

namespace Impala;

use Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class FilterForm extends ReactForm implements IFilterFormFactory {

    public function __construct(string $js, string $css, IRequest $request) {
        parent::__construct($js, $css, $request);
    }

    public function create(): IReactFormFactory {
        return $this;
    }

}

interface IFilterFormFactory {

    public function create(): IReactFormFactory;
}
