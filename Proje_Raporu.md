# Proje Raporu: Basit Üniversite Etkinlik Sistemi

## 1. Proje Özeti
Bu proje, üniversite öğrencileri ve organizatörleri için geliştirilmiş web tabanlı bir etkinlik yönetim sistemidir. Sistem, kullanıcıların kayıt olmasını, etkinlikler oluşturmasını (organizatörler için), etkinliklere bilet almasını (katılımcılar için) ve yöneticilerin sistemi denetlemesini sağlar.

## 2. Kullanılan Teknolojiler
Proje geliştirilirken aşağıdaki teknolojiler kullanılmıştır:
- **Backend**: PHP (PDO ile güvenli veritabanı bağlantısı)
- **Veritabanı**: MySQL (İlişkisel veritabanı yapısı)
- **Frontend**: HTML5, CSS3 (Basit ve anlaşılır arayüz)
- **Güvenlik**: PHP Session yönetimi ve `password_hash()` ile şifreleme.

## 3. Veritabanı Tasarımı
Proje veritabanı (`university_events`) üç ana tablodan oluşmaktadır:

### 3.1. Users (Kullanıcılar) Tablosu
Sistemdeki tüm kullanıcıların bilgilerini tutar.
- `id`: Benzersiz kullanıcı kimliği (Primary Key)
- `name`: Kullanıcı adı soyadı
- `email`: Kullanıcı e-posta adresi (Benzersiz)
- `password`: Şifrelenmiş parola
- `role`: Kullanıcı rolü (`admin`, `organizer`, `participant`)
- `created_at`: Kayıt tarihi

### 3.2. Events (Etkinlikler) Tablosu
Oluşturulan etkinliklerin detaylarını tutar.
- `id`: Etkinlik kimliği
- `title`: Etkinlik başlığı
- `description`: Etkinlik açıklaması
- `date`: Etkinlik tarihi ve saati
- `capacity`: Kontenjan
- `created_by`: Etkinliği oluşturan kullanıcı (Foreign Key -> users)
- `is_closed`: Etkinliğin iptal/kapanma durumu

### 3.3. Tickets (Biletler) Tablosu
Kullanıcıların etkinliklere katılımlarını (biletlerini) tutar.
- `id`: Bilet kimliği
- `user_id`: Bileti alan kullanıcı (Foreign Key -> users)
- `event_id`: İlgili etkinlik (Foreign Key -> events)

## 4. Sistem Modülleri ve Dosya Yapısı

### 4.1. Kimlik Doğrulama
- **`register.php`**: Kullanıcıların sisteme "Katılımcı" veya "Organizatör" olarak kayıt olmasını sağlar.
- **`login.php`**: Kayıtlı kullanıcıların e-posta ve şifre ile giriş yapmasını sağlar.
- **`logout.php`**: Oturumu güvenli bir şekilde sonlandırır.

### 4.2. Ana Yönetim
- **`config.php`**: Veritabanı bağlantı ayarlarını ve PDO yapılandırmasını içerir.
- **`dashboard.php`**: Kullanıcının rolüne göre özelleştirilmiş ana sayfadır.
    - **Admin**: Organizatör ekleyebilir, etkinlikleri sonlandırabilir.
    - **Organizatör**: Yeni etkinlik oluşturabilir, kendi etkinliklerini görebilir.
    - **Katılımcı**: Açık etkinlikleri listeyebilir, bilet alabilir veya biletini iptal edebilir.

### 4.3. Veritabanı Kurulumu
- **`schema.sql`**: Veritabanını ve tabloları oluşturmak için gerekli SQL komutlarını içerir.

## 5. Kullanıcı Senaryoları
1. **Kayıt ve Giriş**: Yeni bir kullanıcı sisteme üye olur ve giriş yapar.
2. **Etkinlik Oluşturma**: Bir organizatör, tarih ve kontenjan belirterek yeni bir etkinlik açar.
3. **Bilet Alma**: Bir katılımcı, dashboard üzerinden ilgisini çeken bir etkinliğe bilet alır. Kontenjan dolarsa bilet alımı engellenir.
4. **Yönetim**: Admin kullanıcısı, gerekirse etkinlikleri kapatabilir veya yeni organizatörler atayabilir.

## 6. Sonuç
Bu proje, temel PHP ve MySQL yetkinliklerini gösteren, rol tabanlı yetkilendirme (RBAC) ve CRUD işlemlerini içeren işlevsel bir web uygulamasıdır. Güvenli kodlama pratikleri (Prepared Statements, Password Hashing) dikkate alınarak geliştirilmiştir.
