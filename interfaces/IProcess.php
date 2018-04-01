<?php

namespace Impala;

interface IProcess {

    public function attached(IReactFormFactory $form): IReactFormFactory;

    public function done(array $data, IImpalaFactory $impala): array;

    public function getFolder(): string;

    public function getSetting(): array;

    public function prepare(array $response, IImpalaFactory $impala): array;

    public function run(array $response, IImpalaFactory $impala): array;

    public function setSetting(array $setting): IProcess;

    public function speed(int $speed): int;

}