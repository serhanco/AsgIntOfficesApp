# Ölçümleme sözleşmesi (dataLayer) · sürüm 1.0

Sitenin GA4 / Google Tag Manager / Meta'ya ne gönderdiğini tanımlar. Plan ve gerekçeler: proje klasöründe
`reviews/olcumleme-gtm-plani-2026-11-10.md`.

**Temel kural:** Buradaki olay adları ve alan adları canlıya çıktıktan sonra **değişmez, silinmez**; sadece yenileri eklenir.
Yeni alan veya olay eklenince `includes/measurement.php` içindeki `MEASURE_CONTRACT` sürümü artırılır ve bu belge güncellenir.

Kod:
- `includes/measurement.php`: hangi etiket yüklenir (Ayarlar > Ölçüm), sayfa bilgisi, `trackAttrs()` yardımcıları.
- `assets/js/track.js`: tıklama dinleyicisi, otomatik tanıma, `asgTrack()`.
- `scripts/olcum-test.js`: otomatik kontrol (aşağıda).

---

## 1. Yükleme

| Ayarlar > Ölçüm | Sitede yüklenen | Olaylar nereye gider |
|---|---|---|
| Doğrudan GA4 (varsayılan) | `gtag.js` (GA4 kimliği) + Meta Pixel kimliği varsa Pixel | `dataLayer` + `gtag('event')` + Pixel standart olayı |
| Google Tag Manager | Yalnızca GTM | `dataLayer` (GTM okur) |
| Kapalı | Hiçbiri | Sadece `dataLayer` (etkisiz) |

- Sıra: çerez onayı varsayılanı → sayfa bilgisi → GTM veya GA4. Sayfa kaynağındaki `<!-- olcum: ... -->` notu ne yüklendiğini söyler.
- GTM seçili ama kimlik geçersizse doğrudan GA4'e düşer.
- Giriş yapmış yöneticiye hiçbir şey yüklenmez. İstisna: adreste `gtm_debug` varsa (GTM Önizleme) ve GTM kimliği kayıtlıysa, o sayfa görüntülemesinde GTM yüklenir (yönetici dahil, yöntem hâlâ "Doğrudan GA4" olsa bile; o görüntülemede doğrudan GA4 yüklenmez).
- Çerez onayı: onay bölgesinde (AB/AEA, UK, CH, RU, bilinmeyen) Temel modda onaydan önce hiçbir şey yüklenmez; Gelişmiş modda Google etiketleri "denied" yüklenir. Meta Pixel (doğrudan modda) onaya kadar `fbq('consent','revoke')` ile bekletilir.

## 2. Sayfa bilgisi (her sayfada, etiketlerden önce)

```js
dataLayer.push({
  page_template, page_lang, page_dir, visitor_country, consent_region, wa_office_enabled,
  meta_pixel_id, contract_version,
  // ofis sayfasında:     office_id, office_slug, office_country
  // etkinlik sayfasında: event_id, event_slug, event_type, event_audience
});
```

| Alan | Değer | Kaynak |
|---|---|---|
| `page_template` | `home`, `nearest`, `offices`, `office`, `map`, `events`, `event`, `institutions`, `sitemap`, `not_found` | `measureTemplate()`; sayfa `$pageTemplate` ile ezebilir |
| `page_lang` | dil kodu (`en`, `tr`, `ar`…) | `langHtml()`; dil eklenince/çıkınca kendiliğinden |
| `page_dir` | `ltr` / `rtl` | `langDir()` |
| `visitor_country` | Cloudflare ülke kodu (`AZ`) veya `null` | `cfCountry()` |
| `consent_region` | `true`/`false` | `consentRegion()` |
| `wa_office_enabled` | `true`/`false` | Ayarlar > ofis WhatsApp düğmeleri |
| `meta_pixel_id` | Pixel kimliği veya `null` | Ayarlar; GTM'deki Meta etiketi bunu kullanır |
| `contract_version` | `1.0` | `MEASURE_CONTRACT` |
| `office_*`, `event_*` | aşağıdaki tablo | `measureOfficeFields()`, `measureEventFields()` |

Doğrudan GA4 modunda bu alanlar `gtag('set', …)` ile her GA4 olayına eklenir (`true/false` → `yes/no`).

## 3. Etkileşim olayları

```js
dataLayer.push({ track: null });
dataLayer.push({ event: 'asg_track', track: { name: 'click_whatsapp', cta_type, cta_owner, cta_position, cta_id,
  office_id, office_slug, office_country, event_id, event_slug, event_type, event_audience, link_domain, method } });
```

GTM'de tek etiket: `asg_track` özel olayında, GA4 olay adı = `track.name`, parametreler = yukarıdaki alanlar.
Boş alanlar `null` gelir. **Kişisel veri (form içeriği, telefon, e-posta) asla gönderilmez.**

### Olay adları

| `name` | Ne zaman | Anahtar olay | Meta |
|---|---|---|---|
| `click_whatsapp` | WhatsApp bağlantısı | ✔ | Contact |
| `click_phone` | `tel:` | ✔ | Contact |
| `click_email` | `mailto:` | ✔ | Contact |
| `click_directions` | Google Maps | ✔ | Contact |
| `click_consultation` | acibademinternational.com `/second-opinion`, `/online-consultation`, `/contact` | ✔ | Lead |
| `event_request_submit` | Etkinlik talep formu, sunucu onayından sonra | ✔ | Lead |
| `event_registration` | (ileride) etkinlik kaydı | ✔ | CompleteRegistration |
| `click_partner` | partner.acibademinternational.com | | |
| `click_outbound` | Diğer dış bağlantılar | | |
| `office_select` | Ofis sayfasına giden bağlantı, harita işareti, bulucu araması/seçimi | | |
| `event_select` | Etkinlik sayfasına giden bağlantı | | |
| `calendar_add` | `.ics` | | |
| `share` | `data-share` düğmesi (`method`: native / copy) | | |
| `language_change` | Dil menüsü (`method`: yeni dil) | | |
| `filter_use` | Etkinlik filtreleri (`method`: type / country / search / view_list / view_calendar), sayfa başına tür başına bir kez | | |
| `finder_result` | En yakın ofis kartının cevabı (`method`: gps / ip / timezone / language / search / chip / test; `cta_id`: office / hq / no_office / unknown) | | |
| `location_prompt` | Konum izni (`method`: granted / denied) | | |
| `faq_open` | (ileride) SSS sorusu açıldı, `cta_id` = soru kodu | | |

### Alanlar

| Alan | Değerler |
|---|---|
| `cta_type` | `phone`, `whatsapp`, `email`, `directions`, `consultation`, `partner`, `outbound`, `calendar`, `share`, `language`, `office_link`, `event_link`, `form`, `filter`, `finder`, `location`, `other` |
| `cta_owner` | `office`, `hq`, `event_location`, `event_contact` (boş = belirsiz) |
| `cta_position` | `header`, `mobile_menu`, `footer`, `home_hero`, `finder_card`, `finder_search`, `home_events`, `home_explore`, `office_hero`, `office_events`, `office_team`, `office_contact`, `office_map`, `office_faq`, `office_quick`, `office_cards`, `office_related`, `office_dock`, `event_head`, `event_locations`, `event_people`, `event_program`, `event_offices`, `event_contact`, `event_side`, `event_others`, `events_list`, `events_calendar`, `events_filters`, `event_request`, `offices_list`, `map`, `map_marker`, `unknown` |
| `cta_id` | Belirli düğmenin kalıcı kodu (ör. `second-opinion`, `event_request`, etkinlik türü) |
| `office_*` | Düğmenin ait olduğu ofis; başka bir ofise giden bağlantıda o ofis |
| `event_*` | Düğmenin ait olduğu etkinlik; `event_audience` = `b2c` / `b2b` |
| `link_domain` | Dış bağlantının alan adı |
| `method` | Olaya özel ek bilgi (tablolara bakın) |

## 4. Yeni düğme / yeni özellik eklerken

1. **Mevcut türde düğme** (telefon, WhatsApp, e-posta, harita, dış link, ofis/etkinlik linki): ek bir şey gerekmez, adresinden tanınır. Konumu doğru görünsün diye düğmenin içinde olduğu bölüme `data-track-area="..."` verin (yukarıdaki listeden ya da yeni bir ad).
2. **Kime ait olduğu** belirsizse: kapsayan öğeye `data-track-owner="office|hq|event_location|event_contact"`. Ofis/etkinlik bağlamı için PHP'de `<?= trackAttrs(measureOfficeFields($office)) ?>` veya `trackAttrs(measureEventFields($ev))`; JS ile çizilen kartta `data-office-slug` / `data-office-country`.
3. **Yeni tür düğme** (adresinden anlaşılmayan, ör. "görüntülü görüşme başlat"): `data-track="yeni_olay_adi"` (+ isterseniz `data-track-id`, `data-track-type`). Olayı bu belgeye ekleyin; anahtar olaysa GA4'te işaretleyin. GTM değişmez.
4. **Ölçülmesin**: `data-track="off"`.
5. **Sayfa scripti içinden olay** (form, filtre…): `window.asgTrack('olay_adi', { cta_type: ..., ... })`. Form olayları sunucu "tamam" dedikten sonra gönderilir.
6. **Yeni sayfa türü**: `measureTemplate()` listesine satır (veya sayfada `$pageTemplate`).
7. **Yeni alan** gerekiyorsa: `track.js` `FIELDS` listesine ekleyin, `MEASURE_CONTRACT` artırın, GTM'de değişken + GA4 etiketine satır, GA4'te özel boyut. (Tek GTM değişikliği gerektiren durum.)

Hiçbir şey gerektirmeyenler: ofis/etkinlik ekleme-silme, dil ekleme-çıkarma, WhatsApp aç/kapat.

## 5. Kontrol

```
node scripts/olcum-test.js                       # yerel (http://127.0.0.1:8080)
node scripts/olcum-test.js https://acibadem.world # canlı (Google/Meta istekleri engellenir, form gönderilmez)
```

Sayfa bilgisini, ana düğmelerin olaylarını ve tek etiket yüklendiğini kontrol eder. Düğme, sayfa veya `track.js` değiştiren her PR'da çalıştırın.
