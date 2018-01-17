<?php

namespace App\Model;

use Nette\Database;
use Nette\Utils\DateTime;
use Nette\Utils\Json;

class ConfereeManager
{

    const TABLE_NAME = 'conferee';

    /**
     * @var Database\Context
     */
    private $database;


    public function __construct(Database\Context $database)
    {
        $this->database = $database;
    }


    public function fromForm($values)
    {
        $data = [
            'name' => $values->name,
            'email' => $values->email,
            'bio' => $values->bio,
            'allow_mail' => $values->allow_mail,
            'consens' => $values->consens ? new DateTime() : null,
            'extended' => Json::encode([
                'organization' => $values->extendedOrganization,
                'address' => $values->extendedAddress,
            ]),
        ];

        $this->save($data);
    }


    public function save($data)
    {

        $data += [
            'created' => new DateTime()
        ];

        $this->database->table(self::TABLE_NAME)
            ->insert($data);
    }
}
