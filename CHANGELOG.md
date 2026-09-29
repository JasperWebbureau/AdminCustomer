# Changelog

## 0.8.0 - 2026-09-28

- Publieke `SyncExternalCustomer`-use-case synchroniseert externe klanten idempotent en actualiseert gewijzigde brongegevens met behoud van publieke klant- en child-id's.
- Bronstatus kan actief of inactief worden overgenomen; consumerende modules blijven klantdata via bestaande providers lezen.

## 0.7.1 - 2026-09-21

- Dashboardbijdrage levert expliciete presentatiemetadata voor klantaanmaak en onvolledige klantgegevens.

## 0.7.0 - 2026-09-21

- Optionele Dashboard-provider toegevoegd voor klantenactiviteit, snelle klantaanmaak, actieve klanttelling en ontbrekende primaire contact-/adresgegevens.
- De integratie is tenantgebonden en blijft beperkt tot `Integration/Dashboard`.

## 0.6.1 - 2026-09-21

- Los klantenpaneel verwijderd; de controller levert nu navigatiemetadata aan het gezamenlijke AdminCore-paneel.

## 0.6.0 - 2026-09-19

- Optionele `Integration/Quote/CustomerSelectionProvider` toegevoegd.
- Alleen actieve klanten met een volledig primair factuuradres worden aan AdminQuote aangeboden.
- Bedrijfsgegevens, primair contact en factuuradres worden uitsluitend als scalars via het consumer-owned Quote-contract geleverd.
- AdminCustomer buiten de expliciete Quote-integratiemap blijft onafhankelijk van AdminQuote.
- Mapping-, status-, adres-, modulegrens- en echte tenantgebonden PDO-providertests toegevoegd.

## 0.5.0 - 2026-09-18

- Publieke, beperkte `CustomerDetailContext` en `CustomerDetailExtensionInterface` toegevoegd.
- Conventiegebaseerde loader ontdekt optionele klantdetailuitbreidingen zonder concrete module-import in AdminCustomer.
- Klanteditor bevat een generieke extensieslot die na een AJAX-save opnieuw wordt gerenderd.
- AdminInvoice kan daardoor een actie voor vooringevulde factuuraanmaak aanbieden zonder de Customer-kern aan Invoice te koppelen.
- Context-, discovery-, modulegrens-, template- en AJAX-verversingstests toegevoegd.

## 0.4.1 - 2026-09-18

- Overzicht- en editorassets worden expliciet vanuit de controller geregistreerd.
- Adressen en contacten toevoegen is daardoor niet langer afhankelijk van impliciete template-assetdetectie of een actuele TemplateRegistry-cache.
- Outlineknoppen zijn vervangen door de zichtbare secundaire knopvariant.
- Regressietest toegevoegd voor de expliciete CSS- en JavaScriptregistratie.

## 0.4.0 - 2026-09-18

- Optionele `Integration/Invoice/CustomerSelectionProvider` toegevoegd.
- Alleen actieve klanten met een primair factuuradres zijn selecteerbaar voor Invoice.
- Bedrijfsgegevens, primair contact en primair factuuradres worden als scalars aan Invoice geleverd.
- AdminCustomer buiten de expliciete Integration-map blijft onafhankelijk van AdminInvoice.
- Unit- en echte PDO-integratietests voor zoeken, tenantisolatie en snapshotmapping toegevoegd.

## 0.3.0 - 2026-09-18

- Nieuwe-klantflow en transactionele klanteditor toegevoegd.
- Overzichtsregels linken via `TableRenderer` naar de juiste klanteditor.
- Algemene klantgegevens, status en notities zijn wijzigbaar.
- Meerdere contactpersonen en getypeerde adressen kunnen worden toegevoegd, gewijzigd en verwijderd.
- De editor bewaakt één primair contact en één primair adres per adrestype.
- Bestaande publieke child-id's en externe bronreferenties blijven bij updates behouden.
- Vanilla JavaScript verzorgt uitsluitend dynamische formulierkaarten; opslag gebruikt het centrale Flexgrid AJAX-transport.
- Domein-, template-, JavaScript- en echte PDO-updatetests toegevoegd.

## 0.2.0 - 2026-09-18

- De uitgevoerde Autowire-scan geverifieerd met schema- en echte PDO-tests.
- Tenantgebonden, doorzoekbare en gepagineerde klantlijstquery toegevoegd.
- Zoeken ondersteunt klant- en bedrijfsnaam, nummers, contacten en adressen.
- AJAX-klantenoverzicht toegevoegd met statusfilter, sortering en paginering.
- De gedeelde Flexgrid `TableRenderer` wordt gebruikt voor alle klantregels.
- Generieke `admin-data-*`-classes voor toolbars en paginering aan AdminUi toegevoegd.
- Interactie gebruikt een vanilla JavaScript-class en het bestaande declaratieve Flexgrid AJAX-transport.
- Regressietests toegevoegd voor queryvalidatie, tenantisolatie, escaping, templates en JavaScript-afhankelijkheden.

## 0.1.0 - 2026-09-18

- Zelfstandig tenantgebonden `Customer`-aggregate toegevoegd.
- Meerdere contacten met exact één primair contact ondersteund.
- Meerdere factuur-, verzend-, bezoek- en overige adressen met één primair adres per type ondersteund.
- Transactionele en idempotente `CreateCustomer`-use-case toegevoegd.
- Opslag verdeeld over `admin_customer`, `admin_customer_contact` en `admin_customer_address`.
- Handmatige klanten gebruiken SQL `NULL` voor de optionele externe referentie.
- Frameworkvrije domein-, use-case- en Autowire-metadatatests toegevoegd.
- Schema- en echte PDO-integratietests voorbereid voor na de Autowire-scan.
