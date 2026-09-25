# AsgIntOfficesApp - Yönetim Paneli (Admin Panel) Aksiyon Planı

Bu belge, sisteme entegre edilecek Yönetim Paneli'nin mimari ve operasyonel planını içermektedir. Yapay zeka ile refactor edilmeye, tartışılmaya ve projenin ihtiyaçlarına göre şekillendirilmeye uygun bir taslak olarak hazırlanmıştır.

## 1. Veritabanı Revizyonu ve Sabit Alanlar
Mevcut statik çeviriler (`lang/en.php` ve `lang/ru.php`) UI bileşenleri (menüler, butonlar, sabit metinler) için kullanılmaya devam edilecek. Ancak dinamik içerikler (veritabanından gelen ofis isimleri, adresler, etkinlik detayları) girildiği dilde ve formatta, tekil olarak tutulacaktır. Veritabanında çoklu dil (kolon veya JSON) yapısına gidilmeyecektir.
* **Planlanan Değişiklikler:**
  * `office_activities`: Mevcut `title` ve `description` alanları tek dilde tutulmaya devam edecek.
  * `offices`: `display_name` ve `address` alanları tek formatta (örneğin uluslararası formatta) tutulacaktır.
  * `office_teams`: Özel isimler (isim, soyisim) dilden bağımsızdır. `languages` sütunu virgülle ayrılmış dil kodları veya dil dosyası anahtarları şeklinde tutulabilir.

## 2. Güvenlik ve Kimlik Doğrulama (Authentication)
Ağır bir ACL (Erişim Kontrol Listesi) veya framework bazlı karmaşık bir auth sistemi yerine, mikro uygulamanın doğasına uygun, hafif ve güvenli bir PHP Session mimarisi kurulacaktır.
* **Planlanan Değişiklikler:**
  * Yeni `admin_users` tablosu oluşturulması (`id`, `username`, `password_hash`, `created_at`).
  * Güvenli bir login sayfası (`/admin/login.php`) tasarımı.
  * Şifrelerin `password_hash()` standartlarında tek yönlü şifrelenmesi.
  * Her admin sayfası için oturum (Session) kontrolü sağlayacak bir helper/middleware mimarisi.

## 3. Yönetim Paneli Arayüzü (UI/UX) ve Modüller
Önyüzde (Frontend) kullanılan Tailwind CSS altyapısı değerlendirilerek, responsive, hafif ve modern bir admin arayüzü (SPA hissi veren) tasarlanacaktır.
* **Düzen:** Sol Sidebar (Ana Menü) + Üst Bar (Oturum durumu vb.) + Ana İçerik Alanı.
* **Modüller:**
  1. **Dashboard:** İstatistikler (Toplam ofis, toplam takım üyesi, sistem durumu özetleri).
  2. **Offices (CRUD):** Ofisleri listeleme, ekleme, düzenleme (İletişim bilgileri, harita koordinatları, `is_active` toggle'ı).
  3. **Teams (CRUD):** Seçili ofise bağlı ekip üyelerini yönetme. Görsel ekleme, Online/Offline/Away durumu yönetimi, Rol seçimi.
  4. **Activities (CRUD):** Seçili ofise etkinlik ekleme. İkon/tip (`tag_key`) seçimi ve içerik girişleri.

## 4. Medya ve Dosya Yükleme (Upload Management)
Takım üyelerinin (ve projenin büyümesine bağlı olarak ofislerin) görsellerinin güvenle sunucuya alınması.
* **Planlanan Değişiklikler:**
  * Yüklenen dosyaların `assets/images/uploads/` dizinine kaydedilmesi.
  * Yalnızca geçerli web resim formatlarına (JPG, PNG, WEBP) izin veren MIME kontrolleri.
  * Dosya ismi çakışmalarını önlemek adına benzersiz isimlendirme (örn: `uniqid()` veya timestamp).

## 5. Frontend Entegrasyonu (Görünüm Katmanı)
Admin paneli ile veritabanına eklenen verilerin ön yüze yansıtılması.
* **Planlanan Değişiklikler:**
  * Statik UI metinleri (menüler vb.) mevcut `$current_lang` değişkenine (en/ru) ve `lang/*.php` dosyalarına göre çalışmaya devam edecek.
  * Dinamik DB içerikleri doğrudan ilgili tablodan çekilip gösterilecek (Örn: `<?= e($activity['title']) ?>`).

---

## 📝 Değerlendirme ve Açık Sorular (Refactoring Öncesi Notlar)
1. **Fotoğraf Boyutlandırma:** Admin panelden yüklenen resimler için sunucu tarafında (PHP GD veya Imagick ile) otomatik bir kırpma/boyutlandırma optimizasyonuna gerek var mı, yoksa yüklenen görseller olduğu gibi kaydedilip frontend tarafında (CSS `object-cover` ile) mı halledilecek? Uzun vadeli performans için otomatik WEBP çevirisi ve boyutlandırma düşünülmeli mi?
