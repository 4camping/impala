<?php

namespace Impala;

use MongoDB\UpdateResult;

interface IUser {

    public function updateUser(float $score, array $data): UpdateResult;

}
