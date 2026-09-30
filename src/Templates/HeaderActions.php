<?php $h = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }; ?>
<div class="admin-page-header__actions">
    <?php if (($mode ?? '') === 'overview') { ?>
        <a class="button button-publish" href="<?=$h($createUrl ?? '')?>"><i class="fas fa-plus" aria-hidden="true"></i> <?=t('admin_customer_new', 'Nieuwe klant')?></a>
    <?php } else { ?>
        <a class="button button-secondary" href="<?=$h($overviewUrl ?? '')?>"><i class="fas fa-arrow-left"></i> Terug naar overzicht</a>
    <?php } ?>
</div>
