<?php

/**
 * アラート表示コンポーネント
 */
class AlertComponent
{
    /**
     * SweetAlert用のJavaScriptを生成
     */
    public static function renderUploadAlert($result)
    {
        if (!$result['status']) {
            return '';
        }

        $script = '<script>
        document.addEventListener("DOMContentLoaded", function() {';

        if ($result['status'] === 'success') {
            $script .= self::renderSuccessAlert($result);
        } else {
            $script .= self::renderErrorAlert($result);
        }

        $script .= '});
        </script>';

        return $script;
    }

    /**
     * 成功アラートのJavaScriptを生成
     */
    private static function renderSuccessAlert($result)
    {
        $insertCount = $result['insert_count'] ?? 0;
        $updateCount = $result['update_count'] ?? 0;
        $totalRows = $result['total_rows'] ?? 0;
        $errorRows = $result['error_rows'] ?? [];

        $message = "CSVファイルが正常にアップロードされました。";
        if (!empty($errorRows)) {
            $errorRowsDisplay = implode(', ', array_slice($errorRows, 0, 5));
            if (count($errorRows) > 5) {
                $errorRowsDisplay .= " 他" . (count($errorRows) - 5) . "行";
            }
            $message .= "<br><span style='color: #ff6b6b;'>⚠️ エラー行: " . $errorRowsDisplay . "行目</span>";
        }

        $errorSection = '';
        if (!empty($errorRows)) {
            $errorSection = '<div style="border-top: 1px solid #dee2e6; margin-top: 8px; padding-top: 8px;">
                                <span style="color: #dc3545;">❌ エラー:</span> <strong>' . count($errorRows) . '件</strong>
                            </div>';
        }

        return 'Swal.fire({
            icon: "success",
            title: "登録が成功しました",
            html: `
                <div style="text-align: left; margin: 20px 0;">
                    <p style="margin-bottom: 15px;">' . addslashes($message) . '</p>
                    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                        <strong>📊 処理結果</strong><br>
                        <div style="margin-top: 10px; line-height: 1.8;">
                            <div><span style="color: #28a745;">✅ 新規追加:</span> <strong>' . $insertCount . '件</strong></div>
                            <div><span style="color: #17a2b8;">🔄 更新:</span> <strong>' . $updateCount . '件</strong></div>
                            <div style="border-top: 1px solid #dee2e6; margin-top: 8px; padding-top: 8px;">
                                <span style="color: #6c757d;">📈 合計処理:</span> <strong>' . $totalRows . '件</strong>
                            </div>
                            ' . $errorSection . '
                        </div>
                    </div>
                </div>
            `,
            confirmButtonText: "OK",
            confirmButtonColor: "#2f5d3f",
            width: "500px",
            timer: 10000,
            timerProgressBar: true
        });';
    }

    /**
     * エラーアラートのJavaScriptを生成
     */
    private static function renderErrorAlert($result)
    {
        $errorMessage = $result['error_message'] ?? 'ファイルの形式やサイズを確認してください。';

        return 'Swal.fire({
            icon: "error",
            title: "登録できませんでした",
            text: "' . addslashes($errorMessage) . '",
            confirmButtonText: "OK",
            confirmButtonColor: "#dc3545"
        });';
    }

    /**
     * 一般的なアラート表示用のJavaScriptを生成
     */
    public static function show($type, $title, $message, $options = [])
    {
        $defaultOptions = [
            'confirmButtonText' => 'OK',
            'confirmButtonColor' => $type === 'success' ? '#2f5d3f' : '#dc3545'
        ];

        $options = array_merge($defaultOptions, $options);

        $script = '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: "' . $type . '",
                title: "' . addslashes($title) . '",
                text: "' . addslashes($message) . '",';

        foreach ($options as $key => $value) {
            if (is_string($value)) {
                $script .= $key . ': "' . addslashes($value) . '",';
            } else {
                $script .= $key . ': ' . json_encode($value) . ',';
            }
        }

        $script .= '});
        });
        </script>';

        return $script;
    }

    /**
     * 確認ダイアログ用のJavaScriptを生成
     */
    public static function confirm($title, $text, $confirmCallback, $options = [])
    {
        $defaultOptions = [
            'showCancelButton' => true,
            'confirmButtonText' => '実行',
            'cancelButtonText' => 'キャンセル',
            'confirmButtonColor' => '#2f5d3f',
            'cancelButtonColor' => '#dc3545'
        ];

        $options = array_merge($defaultOptions, $options);

        $script = '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "' . addslashes($title) . '",
                text: "' . addslashes($text) . '",';

        foreach ($options as $key => $value) {
            if (is_string($value)) {
                $script .= $key . ': "' . addslashes($value) . '",';
            } else {
                $script .= $key . ': ' . json_encode($value) . ',';
            }
        }

        $script .= '}).then((result) => {
                if (result.isConfirmed) {
                    ' . $confirmCallback . '
                }
            });
        });
        </script>';

        return $script;
    }
}