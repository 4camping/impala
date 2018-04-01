<?php

namespace Impala;

use Nette\Http\IRequest,
    Nette\Localization\ITranslator;

final class ExportService implements IProcess {

    /** @var string */
    private $link;

    /** @var array */
    private $setting;

    /** @var string */
    private $tempDir;

    /** @var ITranslator */
    private $translatorRepository;

    public function __construct($tempDir, IRequest $request, ITranslator $translatorRepository) {
        $this->tempDir = $tempDir;
        $url = $request->getUrl();
        $this->link = $url->scheme . '://' . $url->host . $url->scriptPath;
        $this->translatorRepository = $translatorRepository;
    }

    public function attached(IReactFormFactory $form): IReactFormFactory { 
        return $form;
    }

    public function done(array $response, IImpalaFactory $impala): array {
        return ['label' => $this->translatorRepository->translate('Click here to download your file.'), 'href' => $this->link . 'temp/' . $response['_file']];
    }

    public function getFolder(): string {
        return $this->tempDir;
    }

    public function getSetting(): array {
        return $this->setting;
    }

    public function prepare(array $response, IImpalaFactory $impala): array {
        return $response;
    }

    public function run(array $response, IImpalaFactory $impala): array {
        return $response;
    }

    public function setSetting(array $setting): IProcess {
        $this->setting = $setting;
        return $this;
    }

    public function speed(int $speed): int {
        return $speed;
    }
}
