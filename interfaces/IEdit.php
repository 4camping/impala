<?php

namespace Impala;

interface IEdit {

    public function after(IReactFormFactory $form): IReactFormFactory;

    public function crop(array $image, array $row): array;
    
    public function delete(array $image, array $row): array;

    public function move(array $image, array $row): void;

    public function submit(array $primary, array $response): array;

}
