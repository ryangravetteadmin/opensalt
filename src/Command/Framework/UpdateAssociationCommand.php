<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsAssociation;

class UpdateAssociationCommand extends BaseCommand
{
    /**
     * @var LsAssociation
     */
    private $association;

    public function __construct(LsAssociation $association)
    {
        $this->association = $association;
    }

    public function getAssociation(): LsAssociation
    {
        return $this->association;
    }
}
