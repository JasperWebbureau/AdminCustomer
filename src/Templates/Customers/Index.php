<section class="admin-page admin-customer-overview">
    <div class="admin-page-header">
        <div>
            <p class="admin-page-header__eyebrow"><?=t('admin_customer_eyebrow', 'Relaties')?></p>
            <h1><?=t('admin_customer_overview_title', 'Klanten')?></h1>
            <p class="admin-page-header__intro"><?=t('admin_customer_overview_intro', 'Vind klanten, contactpersonen en factuuradressen vanuit één overzicht.')?></p>
        </div>
    </div>

    <div data-admin-customer-content>
        <?=$content ?? ''?>
    </div>
</section>
