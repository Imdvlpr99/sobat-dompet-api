<?php
require('../../src/Otp.php');

use Imdvlpr\Otp;

$app = new Otp();
echo $app->verifyOtp($_POST['message_id'], $_POST['otp_number']);