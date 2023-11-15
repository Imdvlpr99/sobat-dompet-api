<?php
require('../../src/Otp.php');

use Imdvlpr\Otp;

$app = new Otp();
echo $app->sendOtp($_POST['phone'], $_POST['is_resend']);