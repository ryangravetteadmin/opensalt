<?php

namespace App\Handler\Email;

use App\Command\Email\AbstractSendEmailCommand;

class SendSignupReceivedEmailHandler extends AbstractEmailHandler
{
    protected function configureMessage(\Swift_Message $email, AbstractSendEmailCommand $command): void
    {
        $email
            ->setSubject('Your OpenSALT Account has been created')
            ->setBody('Thank you! Your account has been created and you will be contacted in 2 business days when it is active.')
        ;
    }
}
