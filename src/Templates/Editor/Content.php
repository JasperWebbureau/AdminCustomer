<?php
$h = function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<form class="admin-form admin-customer-editor-form" data-admin-customer-form ajax="true" action="<?=$h($updateAction ?? '')?>" method="post">
    <input type="hidden" name="public_id" value="<?=$h($publicId ?? '')?>">
    <grid>
    <section class="panel admin-panel admin-customer-profile-panel" style="--cw:12">
        <div class="panel__header admin-panel__header"><h3><i class="fas fa-building"></i> Klantgegevens</h3></div>
        <grid class="panel__body admin-panel__body admin-form-grid fluid">
            <label class="admin-field"><span>Weergavenaam *</span><input name="display_name" value="<?=$h($displayName ?? '')?>" required maxlength="255"></label>
            <label class="admin-field"><span>Status *</span><select name="status"><?php foreach ($statusOptions as $value => $label) { ?><option value="<?=$h($value)?>"<?=($status ?? '') === $value ? ' selected' : ''?>><?=$h($label)?></option><?php } ?></select></label>
            <label class="admin-field"><span>Bedrijfsnaam</span><input name="company_name" value="<?=$h($companyName ?? '')?>" maxlength="255"></label>
            <label class="admin-field"><span>KvK-nummer</span><input name="registration_number" value="<?=$h($registrationNumber ?? '')?>" maxlength="64"></label>
            <label class="admin-field"><span>Btw-nummer</span><input name="tax_number" value="<?=$h($taxNumber ?? '')?>" maxlength="64"></label>
            <label class="admin-field admin-field--wide"><span>Notities</span><textarea name="notes" rows="5" maxlength="10000"><?=$h($notes ?? '')?></textarea></label>
        </grid>
    </section>

    <section class="panel admin-panel admin-customer-contacts-panel" style="--cw:6;--cw-sm:12;--cw-xs:12">
        <div class="panel__header admin-panel__header admin-customer-section-header">
            <h3><i class="fas fa-users"></i> Contactpersonen</h3>
            <button type="button" class="button button-secondary" data-admin-customer-add="contact"><i class="fas fa-plus"></i> Contact</button>
        </div>
        <div class="panel__body admin-panel__body admin-customer-entry-list" data-admin-customer-contacts>
            <?php foreach ($contacts as $index => $contact) { ?>
                <?php $contactKey = 'contact_' . $index; include __DIR__ . '/ContactCard.php'; ?>
            <?php } ?>
        </div>
    </section>

    <section class="panel admin-panel admin-customer-addresses-panel" style="--cw:6;--cw-sm:12;--cw-xs:12">
        <div class="panel__header admin-panel__header admin-customer-section-header">
            <h3><i class="fas fa-map-location-dot"></i> Adressen</h3>
            <button type="button" class="button button-secondary" data-admin-customer-add="address"><i class="fas fa-plus"></i> Adres</button>
        </div>
        <div class="panel__body admin-panel__body admin-customer-entry-list" data-admin-customer-addresses>
            <?php foreach ($addresses as $index => $address) { ?>
                <?php $addressKey = 'address_' . $index; include __DIR__ . '/AddressCard.php'; ?>
            <?php } ?>
        </div>
    </section>
    </grid>

    <div class="admin-form-actions admin-customer-editor-actions">
        <button class="button button-publish" type="submit"><i class="fas fa-save"></i> Wijzigingen opslaan</button>
    </div>

    <template data-admin-customer-template="contact">
        <?php $contactKey = '__INDEX__'; $contact = ['is_primary' => false]; include __DIR__ . '/ContactCard.php'; ?>
    </template>
    <template data-admin-customer-template="address">
        <?php $addressKey = '__INDEX__'; $address = ['type' => 'billing', 'country_code' => 'NL', 'is_primary' => false]; include __DIR__ . '/AddressCard.php'; ?>
    </template>
</form>
