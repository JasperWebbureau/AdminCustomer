<?php
$h = function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<section class="admin-page admin-customer-create">
    <div class="admin-page-header">
        <div>
            <p class="admin-page-header__eyebrow"><?=t('admin_customer_eyebrow', 'Klanten')?></p>
            <h1><?=t('admin_customer_create_title', 'Nieuwe klant')?></h1>
            <p class="admin-page-header__intro"><?=t('admin_customer_create_intro', 'Maak eerst de klant aan; contacten en adressen voeg je daarna in de editor toe.')?></p>
        </div>
    </div>

    <form class="admin-form" ajax="true" action="<?=$h($storeAction ?? '')?>" method="post">
        <grid>
        <section class="panel admin-panel" style="--cw:12">
            <div class="panel__header admin-panel__header"><h3><i class="fas fa-user"></i> Klantgegevens</h3></div>
            <grid class="panel__body admin-panel__body admin-form-grid fluid">
                <label class="admin-field"><span>Weergavenaam *</span><input name="display_name" required maxlength="255" autofocus></label>
                <label class="admin-field"><span>Bedrijfsnaam</span><input name="company_name" maxlength="255"></label>
                <label class="admin-field"><span>KvK-nummer</span><input name="registration_number" maxlength="64"></label>
                <label class="admin-field"><span>Btw-nummer</span><input name="tax_number" maxlength="64"></label>
                <label class="admin-field admin-field--wide"><span>Notities</span><textarea name="notes" rows="5" maxlength="10000"></textarea></label>
            </grid>
        </section>
        </grid>
        <div class="admin-form-actions" style="margin-top:var(--admin-space-md)">
            <a class="button button-secondary" href="<?=$h($overviewUrl ?? '')?>">Annuleren</a>
            <button class="button button-publish" type="submit"><i class="fas fa-save"></i> Klant aanmaken</button>
        </div>
    </form>
</section>
