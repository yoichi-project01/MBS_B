<?php
require_once(__DIR__ . '/../component/autoloader.php');

SessionManager::start();
CSRFProtection::validateRequest();

if (!isset($_FILES['csv_file']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    SessionManager::setUploadResult('error', ['error_message' => 'ファイルが正しくアップロードされませんでした。']);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$file = $_FILES['csv_file'];
$uploadHandler = new FileUploadHandler();

// ファイル検証
$fileErrors = $uploadHandler->validateFile($file);
if (!empty($fileErrors)) {
    SessionManager::setUploadResult('error', ['error_message' => implode(' ', $fileErrors)]);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

try {
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) throw new Exception('ファイルを開けませんでした。');

    $isFirstRow = true;
    $insertCount = $updateCount = $totalRows = 0;
    $errorRows = [];
    $processedCustomerNos = [];
    $validator = new Validator();

    $pdo->beginTransaction();

    while (($line = fgets($handle)) !== false) {
        $line = mb_convert_encoding($line, 'UTF-8', ['SJIS-win', 'UTF-8', 'auto']);

        if ($isFirstRow) {
            $isFirstRow = false;
            continue;
        }

        $data = str_getcsv($line);
        $currentRowNum = $totalRows + 2;

        if (count($data) < 9) {
            $errorRows[] = $currentRowNum;
            continue;
        }

        $data = array_map('trim', $data);
        $customerNo = $validator->validateCustomerData($data, $currentRowNum);

        if ($validator->hasErrors() || $customerNo === false) {
            $errorRows[] = $currentRowNum;
            $validator = new Validator();
            continue;
        }

        if (in_array($customerNo, $processedCustomerNos)) {
            $errorRows[] = $currentRowNum;
            continue;
        }
        $processedCustomerNos[] = $customerNo;

        [
            $customer_no_raw,
            $store_name,
            $customer_name,
            $manager_name,
            $address,
            $telephone_number,
            $delivery_conditions,
            $registration_date,
            $remarks
        ] = $data;

        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE customer_no = ?");
        $checkStmt->execute([$customerNo]);
        $exists = $checkStmt->fetchColumn() > 0;

        $stmt = $pdo->prepare("
            INSERT INTO customers (
                customer_no, store_name, customer_name, manager_name,
                address, telephone_number, delivery_conditions,
                registration_date, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                customer_name = VALUES(customer_name),
                store_name = VALUES(store_name),
                manager_name = VALUES(manager_name),
                address = VALUES(address),
                telephone_number = VALUES(telephone_number),
                delivery_conditions = VALUES(delivery_conditions),
                registration_date = VALUES(registration_date),
                remarks = VALUES(remarks)
        ");

        $stmt->execute([
            $customerNo,
            $store_name,
            $customer_name,
            !empty($manager_name) ? $manager_name : null,
            $address,
            $telephone_number,
            !empty($delivery_conditions) ? $delivery_conditions : null,
            $registration_date,
            !empty($remarks) ? $remarks : null
        ]);

        $exists ? $updateCount++ : $insertCount++;
        $totalRows++;
    }

    fclose($handle);

    if ($totalRows === 0) {
        $pdo->rollBack();
        SessionManager::setUploadResult('error', ['error_message' => '有効なデータが見つかりませんでした。']);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    $pdo->commit();
    SessionManager::setUploadResult('success', [
        'insert_count' => $insertCount,
        'update_count' => $updateCount,
        'total_rows' => $totalRows,
        'error_rows' => $errorRows
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    $errorMessage = 'データ処理中にエラーが発生しました。';
    if (($_ENV['ENVIRONMENT'] ?? 'development') !== 'production') {
        $errorMessage .= ' (' . $e->getMessage() . ')';
    }

    SessionManager::setUploadResult('error', ['error_message' => $errorMessage]);
    error_log('CSV Upload Error: ' . $e->getMessage() . ' File: ' . $file['name']);

    if (isset($handle) && is_resource($handle)) fclose($handle);
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;