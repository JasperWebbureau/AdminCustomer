<article class="admin-customer-entry-card" data-admin-customer-address-card>
    <input type="hidden" name="addresses[<?=$h($addressKey)?>][public_id]" value="<?=$h($address['public_id'] ?? '')?>">
    <div class="admin-customer-entry-card__header">
        <strong><i class="fas fa-location-dot" aria-hidden="true"></i> Adres</strong>
        <button type="button" class="admin-customer-entry-remove" data-admin-customer-remove aria-label="Adres verwijderen"><i class="fas fa-trash"></i></button>
    </div>
    <div class="admin-form-grid">
        <label class="admin-field"><span>Type *</span><select name="addresses[<?=$h($addressKey)?>][type]" data-admin-customer-address-type><?php foreach ($addressTypeOptions as $value => $label) { ?><option value="<?=$h($value)?>"<?=($address['type'] ?? 'billing') === $value ? ' selected' : ''?>><?=$h($label)?></option><?php } ?></select></label>
        <label class="admin-field"><span>Label</span><input name="addresses[<?=$h($addressKey)?>][label]" value="<?=$h($address['label'] ?? '')?>" maxlength="128" placeholder="Bijv. hoofdkantoor"></label>
        <label class="admin-field admin-field--wide"><span>Geadresseerde</span><input name="addresses[<?=$h($addressKey)?>][addressee]" value="<?=$h($address['addressee'] ?? '')?>" maxlength="255"></label>
        <label class="admin-field admin-field--wide"><span>Adresregel *</span><input name="addresses[<?=$h($addressKey)?>][line_1]" value="<?=$h($address['line_1'] ?? '')?>" required maxlength="255"></label>
        <label class="admin-field admin-field--wide"><span>Aanvulling</span><input name="addresses[<?=$h($addressKey)?>][line_2]" value="<?=$h($address['line_2'] ?? '')?>" maxlength="255"></label>
        <label class="admin-field"><span>Postcode *</span><input name="addresses[<?=$h($addressKey)?>][postal_code]" value="<?=$h($address['postal_code'] ?? '')?>" required maxlength="32"></label>
        <label class="admin-field"><span>Plaats *</span><input name="addresses[<?=$h($addressKey)?>][city]" value="<?=$h($address['city'] ?? '')?>" required maxlength="128"></label>
        <label class="admin-field"><span>Regio</span><input name="addresses[<?=$h($addressKey)?>][region]" value="<?=$h($address['region'] ?? '')?>" maxlength="128"></label>
        <label class="admin-field"><span>Landcode *</span><input name="addresses[<?=$h($addressKey)?>][country_code]" value="<?=$h($address['country_code'] ?? 'NL')?>" required maxlength="2"></label>
    </div>
    <label class="admin-customer-primary">
        <input type="hidden" name="addresses[<?=$h($addressKey)?>][is_primary]" value="0">
        <input type="checkbox" name="addresses[<?=$h($addressKey)?>][is_primary]" value="1" data-admin-customer-address-primary<?=!empty($address['is_primary']) ? ' checked' : ''?>>
        Primair adres van dit type
    </label>
</article>
