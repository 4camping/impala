<?php

namespace Impala;

use Nette\Application\IPresenter,
    Nette\ComponentModel\IComponent,
    Nette\Localization\ITranslator,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class DemoForm extends ReactForm implements IDemoFormFactory {

    /** @var ITranslator */
    private $translatorRepository;

    public function __construct(string $js, IRequest $request, ITranslator $translatorRepository) {
        parent::__construct($js, $request);
        $this->translatorRepository = $translatorRepository;
    }

    public function create(): ISurveyFormFactory {
        return $this;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter) {
            $this->addTitle('title', ['value' => $this->translatorRepository->translate('If you have a minute, let us know why are you subscribing.'),
                                      'class' => 'unsubscribe'])
                    ->addRadioList('answer', ['data' => ['test'], 'onClick' => 'click()'])
                    ->addText('content', ['data' => ['delay' => 0],
                                            'onBlur' => 'change()',
                                            'onChange' => 'change()',
                                            'style' => ['display'=>'none'], 
                                            'placeholder' => $this->translatorRepository->translate('You can write your opinion here.'),
                                            'value' => ''], 
                                        ['required' => $this->translatorRepository->translate('Please fill your question.')])
                    ->addSubmit('submit');
        }
    }

}

interface IDemoFormFactory {

    public function create(): IDemoFormFactory;
}
