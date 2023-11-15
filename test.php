<?php

$servername = "localhost";
$username = "imdvlprm_imdvlpr";
$password = "Imdvlpr0699_";
$database = "imdvlprm_expense";

try {
    $connection = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = $_POST['action'];
    if ($action === 'request-otp') {
        if (isset($_POST['phone']) && isset($_POST['is_resend'])) {
            $timestamp = time();
            $userkey = '64707c333434';
            $passkey = 'be5c4a928db531eac41dea01';
            $telepon = $_POST['phone'];
      	    $isResend;
            $otp_code = mt_rand(1000, 9999);;
            $url = 'https://console.zenziva.net/waofficial/api/sendWAOfficial/';
          	if ($_POST['is_resend'] == "true" || $_POST['is_resend'] == true) {
              $isResend = true;
            } else {
              $isResend = false;
            }
    
            $curlHandle = curl_init();
            curl_setopt($curlHandle, CURLOPT_URL, $url);
            curl_setopt($curlHandle, CURLOPT_HEADER, 0);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curlHandle, CURLOPT_TIMEOUT,30);
            curl_setopt($curlHandle, CURLOPT_POST, 1);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, array(
                'userkey' => $userkey,
                'passkey' => $passkey,
                'to' => $telepon,
                'brand' => 'ExpenseTracker',
                'otp' => $otp_code
            ));
    
            $results = json_decode(curl_exec($curlHandle), true);
            curl_close($curlHandle);
      	    $messageId = $results['messageId'];
      
      	    $sql = "INSERT INTO auth (message_id, otp_number, timestamp, status) VALUES ('$messageId', '$otp_code', '$timestamp', 'waiting')";
    
            if ($connection->query($sql)) {
                $response = array(
                    "success" => true,
              	    "message" => "Kirim OTP berhasil",
                    "message_id" => $messageId,
              	    "is_resend" => $isResend,
                    "time_in_second" => 60
                );

                $jsonResponse = json_encode($response);
                header("Content-Type: application/json");
                echo $jsonResponse;
            }
        } else {
            $response = array(
                "success" => false,
                "message" => "Invalid Parameter"
            );

            $jsonResponse = json_encode($response);
            header("Content-Type: application/json");
            echo $jsonResponse;
        }
    } else if ($action === 'verify-otp') {
        if (isset($_POST['message_id']) && isset($_POST['otp_number'])) {
            $messageId = $_POST['message_id'];
            $otp_code = $_POST['otp_number'];

            $sql = "SELECT * FROM auth WHERE message_id = '$messageId' AND otp_number = '$otp_code' AND status = 'waiting'";
            $stmt = $connection->query($sql);

            if ($stmt !== false) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
                if (count($rows) > 0) {
                    $currentTimestamp = time();
                    $timestamp60secAhead = $rows[0]['timestamp'] + 60;

                    if ($currentTimestamp < $timestamp60secAhead ) {
                        $response = array(
                            "success" => true,
                            "message" => "Autentikasi berhasil",
                            "message_id" => $rows[0]['message_id']
                        ); 

                        $updateSuccess = "UPDATE auth SET status = 'success' WHERE message_id = '$messageId' AND otp_number = '$otp_code'";
                        $connection->query($updateSuccess);
                    } else {
                        $response = array(
                            "success" => false,
                            "message" => "Kode OTP nya udah kadaluarsa nih, perbaharui dulu yuk",
                            "message_id" => $rows[0]['message_id']
                        );

                        $updateExpired = "UPDATE auth SET status = 'expired' WHERE message_id = '$messageId' AND otp_number = '$otp_code'";
                        $connection->query($updateExpired);
                    }
                } else {
                    $response = array(
                        "success" => false,
                        "message" => "Kode OTP nya salah, coba input ulang lagi dengan benar ya :)",
                        "message_id" => $rows[0]['message_id']
                    );
                    
                    $updateInvalid = "UPDATE auth SET status = 'invalid' WHERE message_id = '$messageId' AND otp_number = '$otp_code'";
                    $result = $connection->query($updateInvalid);
                }
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Internal Server Error"
                );
            }
        
            $jsonResponse = json_encode($response);
            header("Content-Type: application/json");
            echo $jsonResponse;
        } else {
            $response = array(
                "success" => false,
                "message" => "Invalid Parameter"
            );
            
            $jsonResponse = json_encode($response);
            header("Content-Type: application/json");
            echo $jsonResponse;
        }
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>