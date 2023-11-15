<?php

require_once 'vendor/autoload.php';

use Decoderid\GojekApi;

$gojek = new GojekApi();

/** SET UUID */
$uuid = $gojek->generateUuid();
$gojek->setUuid($uuid);

/** LOGIN */
$phone = $_POST['phone'];
$pin = '[PIN]';

$login = $gojek->login($phone);
print_r(json_encode($login));
