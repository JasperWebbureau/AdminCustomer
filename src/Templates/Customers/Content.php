<?php
$h = function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<form class="admin-form admin-data-results admin-customer-results" data-admin-customer-filters ajax="true" action="<?=$h($refreshAction ?? '')?>" method="post">
    <input type="hidden" name="sort" value="<?=$h($query->getSort())?>">
    <input type="hidden" name="direction" value="<?=$h($query->getDirection())?>">
    <input type="hidden" name="page" value="<?=$h($result->getPage())?>">

    <div class="admin-summary-grid admin-customer-summary">
        <?php foreach ($summaryCards as $card) { ?>
            <article class="admin-stat-card">
                <span class="admin-tone-icon is-<?=$h($card['tone'])?>"><i class="<?=$h($card['icon'])?>"></i></span>
                <span class="admin-stat-card__content">
                    <span class="admin-stat-card__label"><?=$h($card['label'])?></span>
                    <strong class="admin-stat-card__value"><?=$h($card['value'])?></strong>
                    <small class="admin-stat-card__meta"><?=$h($card['meta'])?></small>
                </span>
            </article>
        <?php } ?>
    </div>

    <grid>
    <section class="panel admin-panel admin-customer-list-panel" style="--cw:12">
        <div class="panel__body admin-panel__body admin-panel__body--flush">
            <div class="admin-data-toolbar admin-customer-toolbar">
                <label class="admin-data-search">
                    <span class="fg-table__visually-hidden"><?=t('admin_customer_search_label', 'Klanten zoeken')?></span>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="search" name="q" value="<?=$h($query->getSearch())?>" placeholder="<?=t('admin_customer_search_placeholder', 'Zoek op klant, contact, e-mail, adres of nummer…')?>" autocomplete="off">
                </label>

                <select name="status" aria-label="<?=t('admin_customer_status_filter', 'Filter op klantstatus')?>">
                    <?php foreach ($statusOptions as $value => $label) { ?>
                        <option value="<?=$h($value)?>"<?=$query->getStatus() === $value ? ' selected' : ''?>><?=$h($label)?></option>
                    <?php } ?>
                </select>

                <button class="button button-secondary admin-data-reset" type="button" data-admin-customer-reset title="<?=t('admin_customer_reset_filters', 'Filters wissen')?>">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    <span><?=t('admin_customer_reset', 'Wissen')?></span>
                </button>
            </div>

            <?php
            $tableRenderer = new \Flexgrid\Html\Table\TableRenderer($table);
            echo $tableRenderer->render();
            ?>

            <div class="admin-data-pagination">
                <label>
                    <span><?=t('admin_customer_show', 'Toon')?></span>
                    <select name="per_page" aria-label="<?=t('admin_customer_page_size', 'Aantal klanten per pagina')?>">
                        <?php foreach ($pageSizes as $pageSize) { ?>
                            <option value="<?=$h($pageSize)?>"<?=$query->getPerPage() === $pageSize ? ' selected' : ''?>><?=$h($pageSize)?></option>
                        <?php } ?>
                    </select>
                    <span><?=t('admin_customer_results', 'resultaten')?></span>
                </label>

                <span class="admin-data-pagination__count">
                    <?=$h($result->getFirstPosition())?>–<?=$h($result->getLastPosition())?> <?=t('admin_customer_of', 'van')?> <?=$h($result->getTotal())?>
                </span>

                <nav class="admin-data-pagination__pages" aria-label="<?=t('admin_customer_pagination', 'Klantpagina’s')?>">
                    <button type="button" data-admin-customer-page="<?=$h(max(1, $result->getPage() - 1))?>"<?=$result->getPage() <= 1 ? ' disabled' : ''?> aria-label="<?=t('admin_customer_previous', 'Vorige pagina')?>"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                    <?php foreach ($pages as $page) { ?>
                        <button type="button" data-admin-customer-page="<?=$h($page)?>"<?=$page === $result->getPage() ? ' class="is-active" aria-current="page"' : ''?>><?=$h($page)?></button>
                    <?php } ?>
                    <button type="button" data-admin-customer-page="<?=$h(min($result->getTotalPages(), $result->getPage() + 1))?>"<?=$result->getPage() >= $result->getTotalPages() ? ' disabled' : ''?> aria-label="<?=t('admin_customer_next', 'Volgende pagina')?>"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                </nav>
            </div>
        </div>
    </section>
    </grid>
</form>
