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
    // ファイルの文字コードを検出して読み込み
    $fileContent = file_get_contents($file['tmp_name']);

    // 文字コード検出と変換
    $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'SJIS-WIN', 'SJIS', 'EUC-JP', 'ASCII'], true);
    if ($encoding !== 'UTF-8') {
        $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
    }

    // 一時ファイルに書き込み
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_upload');
    file_put_contents($tempFile, $fileContent);

    $handle = fopen($tempFile, 'r');
    if (!$handle) throw new Exception('ファイルを開けませんでした。');

    $isFirstRow = true;
    $insertCount = $updateCount = $totalRows = 0;
    $errorRows = [];
    $processedCustomerNos = [];
    $validator = new Validator();

    $pdo->beginTransaction();

    $rowNumber = 0;
    while (($data = fgetcsv($handle)) !== false) {
        $rowNumber++;

        // ヘッダー行をスキップ
        if ($isFirstRow) {
            $isFirstRow = false;
            continue;
        }

        // 空行をスキップ
        if (empty(array_filter($data))) {
            continue;
        }

        // CSVの列数チェック（最低限の列数）
        if (count($data) < 9) {
            $errorRows[] = $rowNumber;
            continue;
        }

        // データをトリムして前後の空白を除去
        $data = array_map('trim', $data);

        // CSVファイルの列構造に基づいてデータをマッピング
        // CSVの構造: 顧客ID, 店舗名, 顧客名, 担当者名, 住所, 電話番号, 配送条件, 備考, 顧客登録日, [追加列があれば]
        $customerNo = $data[0];           // 顧客ID
        $storeName = $data[1];            // 店舗名
        $customerName = $data[2];         // 顧客名
        $managerName = $data[3];          // 担当者名
        $address = $data[4];              // 住所
        $telephoneNumber = $data[5];      // 電話番号
        $deliveryConditions = $data[6];   // 配送条件
        $remarks = $data[7];              // 備考
        $registrationDate = $data[8];     // 顧客登録日

        // データバリデーション
        $validatedCustomerNo = $validator->validateCustomerData($data, $rowNumber);

        if ($validator->hasErrors() || $validatedCustomerNo === false) {
            $errorRows[] = $rowNumber;
            $validator = new Validator(); // バリデーターをリセット
            continue;
        }

        // 重複チェック（同一ファイル内）
        if (in_array($validatedCustomerNo, $processedCustomerNos)) {
            $errorRows[] = $rowNumber;
            continue;
        }
        $processedCustomerNos[] = $validatedCustomerNo;

        // 日付フォーマットの変換（YYYY/MM/DD または YYYY-MM-DD 形式を想定）
        if (!empty($registrationDate)) {
            // スラッシュ区切りをハイフン区切りに変換
            $registrationDate = str_replace('/', '-', $registrationDate);

            // 日付が有効かチェック
            $date = DateTime::createFromFormat('Y-m-d', $registrationDate);
            if (!$date || $date->format('Y-m-d') !== $registrationDate) {
                // 別の形式も試行
                $date = DateTime::createFromFormat('Y/m/d', str_replace('-', '/', $registrationDate));
                if (!$date) {
                    $errorRows[] = $rowNumber;
                    continue;
                }
                $registrationDate = $date->format('Y-m-d');
            }
        } else {
            $registrationDate = date('Y-m-d'); // デフォルトで今日の日付
        }

        // 既存データのチェック
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE customer_no = ?");
        $checkStmt->execute([$validatedCustomerNo]);
        $exists = $checkStmt->fetchColumn() > 0;

        // INSERT ON DUPLICATE KEY UPDATE クエリ
        $stmt = $pdo->prepare("
            INSERT INTO customers (
                customer_no, store_name, customer_name, manager_name,
                address, telephone_number, delivery_conditions,
                registration_date, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                store_name = VALUES(store_name),
                customer_name = VALUES(customer_name),
                manager_name = VALUES(manager_name),
                address = VALUES(address),
                telephone_number = VALUES(telephone_number),
                delivery_conditions = VALUES(delivery_conditions),
                registration_date = VALUES(registration_date),
                remarks = VALUES(remarks)
        ");

        $stmt->execute([
            $validatedCustomerNo,
            $storeName,
            $customerName,
            !empty($managerName) ? $managerName : null,
            $address,
            $telephoneNumber,
            !empty($deliveryConditions) ? $deliveryConditions : null,
            $registrationDate,
            !empty($remarks) ? $remarks : null
        ]);

        if ($exists) {
            $updateCount++;
        } else {
            $insertCount++;
        }
        $totalRows++;
    }

    fclose($handle);
    unlink($tempFile); // 一時ファイルを削除

    if ($totalRows === 0) {
        $pdo->rollBack();
        SessionManager::setUploadResult('error', ['error_message' => '有効なデータが見つかりませんでした。CSVファイルの形式を確認してください。']);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    $pdo->commit();

    // 成功メッセージの設定
    SessionManager::setUploadResult('success', [
        'insert_count' => $insertCount,
        'update_count' => $updateCount,
        'total_rows' => $totalRows,
        'error_rows' => $errorRows
    ]);
} catch (Exception $e) {
    $pdo = db_connect();
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $environment = $_ENV['ENVIRONMENT'] ?? $_SERVER['ENVIRONMENT'] ?? 'development';
    $errorMessage = 'データ処理中にエラーが発生しました。';
    
    if ($environment !== 'production') {
        $errorMessage .= ' (デバッグ情報: ' . htmlspecialchars($e->getMessage()) . ')';
        error_log('Upload Error Details: ' . $e->getTraceAsString());
    } else {
        error_log('Upload Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    }

    SessionManager::setUploadResult('error', ['error_message' => $errorMessage]);
    error_log('CSV Upload Error: ' . $e->getMessage() . ' File: ' . $file['name']);

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }

    if (isset($tempFile) && file_exists($tempFile)) {
        unlink($tempFile);
    }
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;