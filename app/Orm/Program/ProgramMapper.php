<?php

namespace App\Orm;

use Nextras\Dbal\Platforms\Data\Fqn;
use Nextras\Orm\Mapper\Dbal\DbalMapper;

class ProgramMapper extends DbalMapper
{
    protected $tableName = 'program';
}
