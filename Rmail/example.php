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

    require_once('Rmail.php');
    
    $mail = new Rmail();

    /**
    * Set the from address of the email
    */
    $mail->setFrom('Richard <richard@example.com>');
    
    /**
    * Set the subject of the email
    */
    $mail->setSubject('Test email');
    
    /**
    * Set high priority for the email. This can also be:
    * high/normal/low/1/3/5
    */
    $mail->setPriority('high');

    /**
    * Set the text of the Email
    */
    $mail->setText('Sample text');
    
    /**
    * Set the HTML of the email. Any embedded images will be automatically found as long as you have added them
    * using addEmbeddedImage() as below.
    */
    $mail->setHTML('<b>Sample HTML</b> <img src="background.gif">');
    
    /**
    * Set the delivery receipt of the email. This should be an email address that the receipt should be sent to.
    * You are NOT guaranteed to receive this receipt - it is dependent on the receiver.
    */
    $mail->setReceipt('test@test.com');
    
    /**
    * Add an embedded image. The path is the file path to the image.
    */
    $mail->addEmbeddedImage(new fileEmbeddedImage('background.gif'));
    
    /**
    * Add an attachment to the email.
    */
    $mail->addAttachment(new fileAttachment('example.zip'));

    /**
    * Send the email. Pass the method an array of recipients.
    */
    $address = 'fruity@licious.com';
    $result  = $mail->send(array($address));
?>

Email has been sent to <?=$address?>. Result: <?var_dump($result)?>