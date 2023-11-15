<?php

namespace Imdvlpr;

require_once('Constant.php');
require_once(__DIR__ . '/../vendor/autoload.php');

use PDO;
use PDOException;
use PHPMailer\PHPMailer\PHPMailer;
use Exception;

class Otp {
    private $connection;

    public function __construct() {
        try {
            $this->connection = new PDO("mysql:host=" . SERVER_NAME . ";dbname=" . DATABASE, USERNAME, PASSWORD);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->handleError("Connection failed: " . $e->getMessage());
        }
    }

    public function sendOtp($phone, $isResend) {
        try {
            $timestamp = time();
            $userkey = ZENZIVA_USERKEY;
            $passkey = ZENZIVA_PASSKEY;
            $otp_code = mt_rand(1000, 9999);
            $url = BASE_URL_ZENZIVA;
            $isResend = filter_var($isResend, FILTER_VALIDATE_BOOLEAN);

            $curlHandle = curl_init();
            curl_setopt($curlHandle, CURLOPT_URL, $url);
            curl_setopt($curlHandle, CURLOPT_HEADER, 0);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
            curl_setopt($curlHandle, CURLOPT_POST, 1);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, array(
                'userkey' => $userkey,
                'passkey' => $passkey,
                'to' => $phone,
                'brand' => 'Sobat Dompet',
                'otp' => $otp_code
            ));

            $results = json_decode(curl_exec($curlHandle), true);
            curl_close($curlHandle);

            if (!isset($results['messageId'])) {
                $this->handleError("Failed to send OTP. cURL response: " . json_encode($results));
            }

            $messageId = $results['messageId'];
            $sql = "INSERT INTO auth (message_id, otp_number, timestamp, status) VALUES ('$messageId', '$otp_code', '$timestamp', 'waiting')";

            if ($this->connection->query($sql)) {
                $response = array(
                    "success" => true,
                    "message" => "Kirim OTP berhasil",
                    "message_id" => $messageId,
                    "is_resend" => $isResend,
                    "time_in_second" => 60
                );

                $jsonResponse = json_encode($response);
                header("Content-Type: application/json");
                return $jsonResponse;
            } else {
                $this->handleError("Failed to insert OTP data into the database.");
            }
        } catch (Exception $e) {
            $this->handleError("Error: " . $e->getMessage());
        }
    }

    public function verifyOtp($messageId, $otpCode) {
        try {
            $sql = "SELECT * FROM auth WHERE message_id = '$messageId' AND otp_number = '$otpCode' AND status = 'waiting'";
            $stmt = $this->connection->query($sql);
    
            if ($stmt !== false) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
                if (count($rows) > 0) {
                    $currentTimestamp = time();
                    $timestamp120secAhead = $rows[0]['timestamp'] + 120;
    
                    if ($currentTimestamp < $timestamp120secAhead) {
                        $response = array(
                            "success" => true,
                            "message" => "Autentikasi berhasil",
                            "message_id" => $rows[0]['message_id']
                        );
    
                        $updateSuccess = "UPDATE auth SET status = 'success' WHERE message_id = '$messageId' AND otp_number = '$otpCode'";
                        $this->connection->query($updateSuccess);
                    } else {
                        $response = array(
                            "success" => false,
                            "message" => "Kode OTP nya udah kadaluarsa nih, perbaharui dulu yuk",
                            "message_id" => $rows[0]['message_id']
                        );
    
                        $updateExpired = "UPDATE auth SET status = 'expired' WHERE message_id = '$messageId' AND otp_number = '$otpCode'";
                        $this->connection->query($updateExpired);
                    }
                } else {
                    $response = array(
                        "success" => false,
                        "message" => "Kode OTP nya salah, coba input ulang lagi dengan benar ya :)",
                        "message_id" => $messageId
                    );
    
                    $updateInvalid = "UPDATE auth SET status = 'invalid' WHERE message_id = '$messageId' AND otp_number = '$otpCode'";
                    $this->connection->query($updateInvalid);
                }
                $jsonResponse = json_encode($response);
                header("Content-Type: application/json");
                return $jsonResponse;
            } else {
                $this->handleError("Internal Server Error.");
            }
        } catch (PDOException $e) {
            $this->handleError("Database error: " . $e->getMessage());
        }
    }

    public function sendOtpEmail($email, $isResend) {
        $mail = new PHPMailer(true);
        $timestamp = time();
        $otp_code = mt_rand(1000, 9999);
        $isResend = filter_var($isResend, FILTER_VALIDATE_BOOLEAN);

        try {
            $mail->isSMTP();
            $mail->Host = HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = 'ssl';
            $mail->Port = SMTP_PORT;    
            $mail->setFrom(SMTP_USERNAME, 'Admin');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Account Recovery OTP';
            $mail->Body = "Your OTP is: $otp_code";

            if ($mail->send()) {
                $sqlRow = "SELECT COUNT(*) as row_count FROM auth";
                $resultRow = $this->connection->query($sqlRow);
                $row = $resultRow->fetch(PDO::FETCH_ASSOC);
              	$rowCount = $row['row_count'];

                $sql = "INSERT INTO auth (message_id, otp_number, timestamp, status) VALUES ('$rowCount', '$otp_code', '$timestamp', 'waiting')";
                if ($this->connection->query($sql)) {
                    $response = array(
                        "success" => true,
                        "message" => "Kode OTP berhasil dikirim ke alamat email, harap cek folder spam bila email tidak diterima",
                        "message_id" => $rowCount,
                        "is_resend" => $isResend,
                        "time_in_second" => 60
                    );

                    $jsonResponse = json_encode($response);
                    header("Content-Type: application/json");
                    return $jsonResponse;
                } else {
                    $this->handleError("Failed to insert OTP data into the database.");
                }
            }
        } catch (Exception $e) {
            $this->handleError("SMTP-Error: " . $e->getMessage());
        }
    }

    private function handleError($message) {
        $response = array(
            "success" => false,
            "message" => $message
        );

        $jsonResponse = json_encode($response);
        header("Content-Type: application/json");
        echo $jsonResponse;
        exit;
    }
}
?>
