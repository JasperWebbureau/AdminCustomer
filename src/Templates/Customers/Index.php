<section class="admin-page admin-customer-overview">
    <div class="admin-page-header">
        <div>
            <p class="admin-page-header__eyebrow"><?=t('admin_customer_eyebrow', 'Relaties')?></p>
            <h1><?=t('admin_customer_overview_title', 'Klanten')?></h1>
            <p class="admin-page-header__intro"><?=t('admin_customer_overview_intro', 'Vind klanten, contactpersonen en factuuradressen vanuit één overzicht.')?></p>
        </div>
        <div class="admin-page-header__actions">
            <a class="button button-publish" href="<?=htmlspecialchars((string)($createUrl ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
                <i class="fas fa-plus" aria-hidden="true"></i>
                <?=t('admin_customer_new', 'Nieuwe klant')?>
            </a>
        </div>
    </div>

    <div data-admin-customer-content>
        <?=$content ?? ''?>
    </div>
</section>
