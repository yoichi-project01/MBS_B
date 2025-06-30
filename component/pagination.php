<?php

/**
 * ページネーションコンポーネント
 */
class Pagination
{
    private $currentPage;
    private $totalPages;
    private $baseUrl;
    private $queryParams;

    public function __construct($currentPage, $totalCount, $itemsPerPage, $baseUrl = '', $queryParams = [])
    {
        $this->currentPage = max(1, (int)$currentPage);
        $this->totalPages = $totalCount > 0 ? ceil($totalCount / $itemsPerPage) : 0;
        $this->baseUrl = $baseUrl;
        $this->queryParams = $queryParams;
    }

    /**
     * ページネーションHTMLを生成
     */
    public function render()
    {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<nav class="pagination-nav" role="navigation" aria-label="ページネーション">';
        $html .= '<div class="pagination-container">';

        // 前のページボタン
        $html .= $this->renderPrevButton();

        // ページ情報
        $html .= $this->renderPageInfo();

        // 次のページボタン
        $html .= $this->renderNextButton();

        $html .= '</div>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * 前のページボタンを生成
     */
    private function renderPrevButton()
    {
        if ($this->currentPage <= 1) {
            return '<span class="pagination-btn disabled" aria-disabled="true">
                        <i>⬅️</i>
                        前へ
                    </span>';
        }

        $url = $this->buildUrl($this->currentPage - 1);
        return '<a href="' . htmlspecialchars($url) . '" class="pagination-btn" aria-label="前のページへ">
                    <i>⬅️</i>
                    前へ
                </a>';
    }

    /**
     * 次のページボタンを生成
     */
    private function renderNextButton()
    {
        if ($this->currentPage >= $this->totalPages) {
            return '<span class="pagination-btn disabled" aria-disabled="true">
                        次へ
                        <i>➡️</i>
                    </span>';
        }

        $url = $this->buildUrl($this->currentPage + 1);
        return '<a href="' . htmlspecialchars($url) . '" class="pagination-btn" aria-label="次のページへ">
                    次へ
                    <i>➡️</i>
                </a>';
    }

    /**
     * ページ情報を生成
     */
    private function renderPageInfo()
    {
        return '<div class="page-info">
                    <span class="current-page" aria-current="page">' . $this->currentPage . '</span>
                    <span class="page-separator">/</span>
                    <span class="total-pages">' . $this->totalPages . '</span>
                </div>';
    }

    /**
     * URLを構築
     */
    private function buildUrl($page)
    {
        $params = array_merge($this->queryParams, ['page' => $page]);
        $queryString = http_build_query($params);
        return $this->baseUrl . ($queryString ? '?' . $queryString : '');
    }

    /**
     * 現在のページが有効かチェック
     */
    public function isValidPage()
    {
        return $this->currentPage <= $this->totalPages;
    }

    /**
     * リダイレクトが必要かチェック
     */
    public function needsRedirect()
    {
        return $this->totalPages > 0 && $this->currentPage > $this->totalPages;
    }

    /**
     * リダイレクト先URLを取得
     */
    public function getRedirectUrl()
    {
        return $this->buildUrl($this->totalPages);
    }

    /**
     * オフセットを計算
     */
    public function getOffset($itemsPerPage)
    {
        return ($this->currentPage - 1) * $itemsPerPage;
    }

    /**
     * ページネーション情報を配列で取得
     */
    public function getInfo()
    {
        return [
            'current_page' => $this->currentPage,
            'total_pages' => $this->totalPages,
            'has_prev' => $this->currentPage > 1,
            'has_next' => $this->currentPage < $this->totalPages,
            'prev_page' => $this->currentPage > 1 ? $this->currentPage - 1 : null,
            'next_page' => $this->currentPage < $this->totalPages ? $this->currentPage + 1 : null
        ];
    }
}