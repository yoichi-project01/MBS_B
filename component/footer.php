<?php
// フッター部分の共通処理

// 統計ページの場合は特別なクロージング処理
if (isset($pageConfig) && $pageConfig['isStatisticsPage']): ?>
</div>
</main>
</div>
<?php endif; ?>

</body>

</html>