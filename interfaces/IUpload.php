<?php

namespace Impala;

interface IUpload {

    public function save(string $id, string $file): void;

}
