<?php
require('../../src/Otp.php');

use Imdvlpr\Otp;

$app = new Otp();
echo $app->sendOtpEmail($_POST['email'], $_POST['is_resend']);