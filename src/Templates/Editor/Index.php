<?php
$h = function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<section class="admin-page admin-customer-editor-page">
    <div class="admin-page-header">
        <div>
            <p class="admin-page-header__eyebrow"><?=t('admin_customer_detail_eyebrow', 'Klanten · bewerken')?></p>
            <h1 data-admin-customer-name><?=$h($displayName ?? '')?></h1>
            <p class="admin-page-header__intro"><?=t('admin_customer_editor_intro', 'Beheer klantgegevens, contactpersonen en adressen.')?></p>
        </div>
        <div class="admin-page-header__actions">
            <a class="button button-secondary" href="<?=$h($overviewUrl ?? '')?>"><i class="fas fa-arrow-left"></i> Terug naar overzicht</a>
        </div>
    </div>

    <div data-admin-customer-editor>
        <?=$content ?? ''?>
    </div>

    <?php if (trim((string)($detailExtensions ?? '')) !== '') { ?>
        <grid class="admin-customer-detail-extensions" data-admin-customer-detail-extensions>
            <?=$detailExtensions?>
        </grid>
    <?php } ?>
</section>
