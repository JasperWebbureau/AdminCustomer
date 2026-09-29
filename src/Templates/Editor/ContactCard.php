<article class="admin-customer-entry-card" data-admin-customer-contact-card>
    <input type="hidden" name="contacts[<?=$h($contactKey)?>][public_id]" value="<?=$h($contact['public_id'] ?? '')?>">
    <div class="admin-customer-entry-card__header">
        <strong><i class="fas fa-user" aria-hidden="true"></i> Contactpersoon</strong>
        <button type="button" class="admin-customer-entry-remove" data-admin-customer-remove aria-label="Contactpersoon verwijderen"><i class="fas fa-trash"></i></button>
    </div>
    <div class="admin-form-grid">
        <label class="admin-field"><span>Naam *</span><input name="contacts[<?=$h($contactKey)?>][name]" value="<?=$h($contact['name'] ?? '')?>" required maxlength="255"></label>
        <label class="admin-field"><span>Rol / functie</span><input name="contacts[<?=$h($contactKey)?>][role]" value="<?=$h($contact['role'] ?? '')?>" maxlength="128"></label>
        <label class="admin-field"><span>E-mail</span><input type="email" name="contacts[<?=$h($contactKey)?>][email]" value="<?=$h($contact['email'] ?? '')?>" maxlength="255"></label>
        <label class="admin-field"><span>Telefoon</span><input name="contacts[<?=$h($contactKey)?>][phone]" value="<?=$h($contact['phone'] ?? '')?>" maxlength="64"></label>
    </div>
    <label class="admin-customer-primary">
        <input type="hidden" name="contacts[<?=$h($contactKey)?>][is_primary]" value="0">
        <input type="checkbox" name="contacts[<?=$h($contactKey)?>][is_primary]" value="1" data-admin-customer-contact-primary<?=!empty($contact['is_primary']) ? ' checked' : ''?>>
        Primair contact
    </label>
</article>
