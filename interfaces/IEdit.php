<?php

namespace Impala;

interface IEdit {

    public function after(IReactFormFactory $form): void;

    public function submit(array $primary, array $response): array;

}
