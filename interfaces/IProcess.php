<?php

namespace Impala;

use Nette\Database\Table\IRow;

interface IProcess {

    public function attached(IReactFormFactory $form): void;

    public function done(array $data, IImpalaFactory $impala): array;

    public function getFile(): string;

    public function getSetting(): IRow;

    public function prepare(array $response, IImpalaFactory $impala): array;

    public function run(array $response, IImpalaFactory $impala): array;

    public function setSetting(IRow $setting): IProcess;

    public function speed(int $speed): int;

}