<?php
    /**
    * o------------------------------------------------------------------------------o
    * | This package is licensed under the Phpguru license. A quick summary is       |
    * | that for commercial use, there is a small one-time licensing fee to pay. For |
    * | registered charities and educational institutes there is a reduced license   |
    * | fee available. You can read more  at:                                        |
    * |                                                                              |
    * |                  http://www.phpguru.org/static/license.html                  |
    * o------------------------------------------------------------------------------o
    *
    * © Copyright 2008,2009 Richard Heyes
    */

    /**
    * This example shows you how to create an email with another email attached
    */

    require_once('Rmail.php');
    
    /**
    * Create the attached email
    */
    $attachment = new Rmail();
    $attachment->setFrom('Bob <bob@example.com>');
    $attachment->setText('This email is attached.');
    $attachment->setSubject('This email is attached.');
    $body = $attachment->getRFC822(array('bob@dole.com'));

    /**
    * Now create the email it will be attached to
    */
    $mail = new Rmail();
    $mail->addAttachment(new StringAttachment($body, 'Attached message', 'message/rfc822', new SevenBitEncoding()));
    $mail->addAttachment(new FileAttachment('example.zip'));
    $mail->setFrom('Richard <richard@example.com>');
    $mail->setSubject('Test email');
    $mail->setText('Sample text');
    $result  = $mail->send(array('foo@goo.com'));
?>

Message has been sent