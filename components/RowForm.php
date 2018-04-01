<?php

namespace Impala;

use Nette\ComponentModel\IComponent,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class RowForm extends ReactForm implements IRowFormFactory {

    /** @var string */
    private $jsDir;

    public function __construct(string $jsDir, IRequest $request, ITranslator $translatorRepository) {
        parent::__construct($jsDir, $request, $translatorRepository);
        $this->jsDir = $jsDir;
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
