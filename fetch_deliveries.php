<?php
header('Content-Type: application/json; charset=UTF-8');
// Подключаем настройки
$data = json_decode(file_get_contents('php://input'), true);
$login = $data['login'] ?? '';
$from  = $data['from']  ?? '';
$to    = $data['to']    ?? '';
if (!$login || !$from || !$to) {
    echo json_encode(['error'=>'Недостаточно параметров']);
    exit;
}

// Функции для API
function requestApi($url, $payload = null, $token = null) {
    $curl = curl_init($url);
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    if ($payload !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    $resp = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($code < 200 || $code >= 300) {
        throw new Exception("HTTP $code for $url");
    }
    return json_decode($resp, true);
}

try {
    // 1. Получаем токен
    $tokenData = requestApi('https://api-eu.syrve.live/api/1/access_token', ['apiLogin'=>$login]);
    $token = $tokenData['token'];

    // 2. Список организаций
    $orgsData = requestApi('https://api-eu.syrve.live/api/1/organizations', null, $token);
    $orgs = $orgsData['organizations'] ?? [];

    // 3. Сбор доставок по дням для каждой организации
    $orders = [];
    $start = DateTime::createFromFormat('Y-m-d H:i:s.u', str_replace(' ', ' ', $from));
    $end   = DateTime::createFromFormat('Y-m-d H:i:s.u', str_replace(' ', ' ', $to));
    $now   = new DateTime();
    if ($end > $now) $end = $now;
    foreach ($orgs as $org) {
        $cursor = clone $start;
        while ($cursor < $end) {
            $chunkEnd = min((clone $cursor)->modify('+1 day'), $end);
            $payload = [
                'organizationIds'=>[$org['id']],
                'deliveryDateFrom'=>$cursor->format('Y-m-d H:i:s.v'),
                'deliveryDateTo'  =>$chunkEnd->format('Y-m-d H:i:s.v'),
                'statuses'=>['Closed']
            ];
            try {
                $d = requestApi('https://api-eu.syrve.live/api/1/deliveries/by_delivery_date_and_status', $payload, $token);
                foreach ($d['ordersByOrganizations'] as $batch) {
                    foreach ($batch['orders'] as $o) {
                        $orders[] = $o;
                    }
                }
            } catch (Exception $e) {
                // пропускаем ошибки отдельных чанков
            }
            $cursor = $chunkEnd;
        }
    }

    // 4. Подсчёт метрик
    $phones = [];
    $invalid = [];
    $noClient = $noPhone = $zero = 0;
    foreach ($orders as $o) {
        $ord = $o['order'] ?? $o;
        if (empty($ord['customer'])) { $noClient++; continue; }
        $ph = $ord['phone'] ?? '';
        if (!$ph) { $noPhone++; continue; }
        $phones[] = $ph;
        if (!preg_match('/^\+380(39|50|63|66|67|68|73|91|92|93|95|96|97|98|99)(?!0000000|1111111|2222222|3333333|4444444|5555555|6666666|7777777|8888888|9999999)\d{7}$/', $ph)) {
            $invalid[] = $ph;
        }
        if (($ord['sum'] ?? 0) == 0) $zero++;
    }
    $counts = array_count_values($phones);
    $unique = count($counts);
    $dup    = array_filter($counts, function($c){return $c>1;});

    // 5. Формируем текст отчёта
    $report = "Всего доставок: " . count($orders) . "\n";
    $report .= "Без клиента: " . $noClient . "\n";
    $report .= "Без телефона: " . $noPhone . "\n";
    $report .= "Нулевых сумм: " . $zero . "\n";
    $report .= "Уникальных телефонов: " . $unique . "\n";
    $report .= "Неверных номеров: " . count($invalid) . "\n";
    foreach (array_unique($invalid) as $ph) {
        $report .= "  ! $ph\n";
    }
    $report .= "Дубликатов (>1): " . count($dup) . "\n";
    foreach ($dup as $ph => $c) {
        $report .= "  * $ph: $c\n";
    }

    echo json_encode(['report'=>$report], JSON_UNESCAPED_UNICODE);

} catch (Exception $ex) {
    echo json_encode(['error'=>$ex->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>