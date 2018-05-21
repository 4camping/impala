<?php

namespace Impala;

use Nette\Application\UI\Presenter,
    Nette\ComponentModel\IComponent,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class ImportForm extends ReactForm implements IImportFormFactory {

    /** @var IProcess */
    private $service;

    /** @var ITranslator */
    private $translatorRepository;

    public function __construct(string $js, string $css, IRequest $request, ITranslator $translatorRepository) {
        parent::__construct($js, $css, $request);
        $this->translatorRepository = $translatorRepository;
    }

    public function create(): IReactFormFactory {
        return $this;
    }

    public function setService(IProcess $service) {
        $this->service = $service;
        return $this;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
        if ($presenter instanceof Presenter and false == $this->isSignalled()) {
            $this->addProgressBar('_prepare');
            $this->service->attached($this);
            $this->addUpload('_file',
                $this->translatorRepository->translate(1146.0),
                [], ['required' => $this->translatorRepository->translate(1147.0), 'text' => $this->translatorRepository->translate(1148.0)]);
            $this->addSubmit('_submit', ucfirst($label = $this->translatorRepository->translate(1149.0)), ['className' => 'btn btn-success', 'onClick'=>'submit']);
            $this->addSubmit('_prepare', ucfirst($this->translatorRepository->translate(1150.0)), ['className' => 'btn btn-success', 'onClick' => 'prepare', 'style' => ['display' => 'none']]);
            $this->addMessage('_done', $this->translatorRepository->translate(1151.0));
        }
    }

}

interface IImportFormFactory {

    public function create(): IReactFormFactory;
}
