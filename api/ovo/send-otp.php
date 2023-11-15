<?php
require('../../src/Ovo.php');

use Namdevel\Ovo;
$app = new Ovo();
echo $app->sendOtp($_POST['phone']);