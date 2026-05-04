# Manual Testing Guide — RestaurantSaaS

Testowanie manualne całego systemu krok po kroku.
Zakładamy środowisko lokalne: landlord na `localhost:8000`, tenant na `test.localhost:8000`.

---

## 1. Landlord — logowanie i dashboard

- [ ] Wejdź na `localhost:8000` — strona landing powinna się załadować
- [ ] Przejdź na `localhost:8000/admin/login` → zaloguj się jako superadmin
- [ ] Dashboard pokazuje kafelki: wszystkie restauracje, aktywne, okres próbny, przychód miesięczny

---

## 2. Landlord — zarządzanie tenantami

- [ ] Przejdź do **Restauracje** → lista tenantów widoczna
- [ ] Utwórz nowego tenanta: podaj nazwę i subdomenę (`test`) → Zapisz
- [ ] Tenant pojawia się na liście ze statusem "Nieaktywny"
- [ ] Kliknij **Aktywuj** → status zmienia się na "Aktywny"
- [ ] Kliknij **Dezaktywuj** → status zmienia się na "Nieaktywny"
- [ ] Ponownie aktywuj tenanta
- [ ] Kliknij **Zaloguj** → otwiera się nowa karta z `test.localhost:8000/manager` z żółtym banerem
- [ ] Baner "Przeglądasz panel jako manager (tryb podglądu administratora)" widoczny na górze
- [ ] Kliknij "Zakończ podgląd" → baner znika
- [ ] Edytuj tenanta → zmień nazwę → Zapisz
- [ ] Usuń tenanta (opcjonalnie, tylko testowy)

---

## 3. Landlord — plany cenowe

- [ ] Przejdź do **Plany**
- [ ] Dodaj nowy plan: nazwa, cena, lista funkcji → Zapisz
- [ ] Plan pojawia się na liście
- [ ] Edytuj plan → zmień cenę → Zapisz
- [ ] Usuń plan

---

## 4. Landlord — modyfikacje

- [ ] Przejdź do **Modyfikacje**
- [ ] Lista modyfikacji widoczna
- [ ] Włącz/wyłącz modyfikację (toggle)
- [ ] Kliknij "▶ Zastosuj modyfikacje" → modyfikacja aplikuje się do tenantów
- [ ] Kliknij "Wyczyść cache"

---

## 5. Landlord — kontakty (formularz ze strony)

- [ ] Przejdź do **Kontakty**
- [ ] Lista zgłoszeń z formularza kontaktowego widoczna
- [ ] Kliknij zgłoszenie → oznacz jako przeczytane
- [ ] Usuń zgłoszenie

---

## 6. Landlord — wyszukiwarka restauracji

- [ ] Przejdź do **Wyszukaj restauracje**
- [ ] Wpisz nazwę restauracji → wyniki z Google Places (lub błąd jeśli brak klucza API)

---

## 7. Landlord — wsparcie (tickets)

- [ ] Przejdź do **Wsparcie**
- [ ] Lista zgłoszeń od tenantów widoczna
- [ ] Otwórz zgłoszenie → odpisz na wiadomość
- [ ] Zmień status zgłoszenia (otwarte / zamknięte)

---

## 8. Install wizard — pierwsza konfiguracja tenanta

- [ ] Wyloguj się z landlorda, wejdź na `test.localhost:8000`
- [ ] Przekierowanie na wizard instalacji (`/install`)
- [ ] **Krok 1 — Konto:** wpisz imię, e-mail, hasło → Dalej
- [ ] **Krok 2 — Restauracja:** wpisz nazwę, telefon, adres → Dalej
- [ ] **Krok 3 — Godziny:** ustaw godziny otwarcia → Zakończ
- [ ] Przekierowanie na panel managera

---

## 9. Panel managera — dashboard

- [ ] Zaloguj się na `test.localhost:8000/manager`
- [ ] Dashboard ładuje się: kafelki z przychodem, zamówieniami, klientami
- [ ] Wykres przychodu (30 dni — słupki) widoczny
- [ ] Mapa ciepła sprzedaży godzinowej widoczna
- [ ] Sidebar z kolorowymi ikonami widoczny

---

## 10. Panel managera — menu

- [ ] Przejdź do **Menu**
- [ ] Dodaj kategorię: nazwa + wybierz emoji z pickera → Zapisz
- [ ] Kategoria pojawia się na liście
- [ ] Dodaj produkt w kategorii: nazwa, cena, opis, URL zdjęcia
- [ ] Zaznacz "Produkt polecany" → gwiazdka widoczna na liście
- [ ] Zaznacz "Śledź stan magazynowy" i wpisz ilość → badge stanu widoczny
- [ ] Dodaj wariant (np. rozmiar M / L z ceną)
- [ ] Dodaj dodatek (np. ser dodatkowy +2 zł)
- [ ] Dodaj alergen (np. gluten)
- [ ] Zapisz produkt
- [ ] Wróć na listę → produkt widoczny z etykietami
- [ ] Przełącz dostępność produktu (toggle on/off) → status zmienia się natychmiast
- [ ] Edytuj produkt → zmień cenę → Zapisz
- [ ] Usuń produkt → znika z listy
- [ ] Usuń kategorię → znika z listy

---

## 11. Panel managera — zamówienia

- [ ] Przejdź do **Zamówienia**
- [ ] Lista zamówień widoczna z filtrami (status, data)
- [ ] Kliknij zamówienie → szczegóły: produkty, dane klienta, adres dostawy, płatność
- [ ] Zmień status zamówienia (przyjęte → w przygotowaniu → w drodze → dostarczone)
- [ ] Nowe zamówienie przychodzi → toast z powiadomieniem pojawia się automatycznie (WebSocket)

---

## 12. Panel managera — pracownicy

- [ ] Przejdź do **Pracownicy**
- [ ] Dodaj pracownika: imię, e-mail, rola (kucharz), hasło → Zapisz
- [ ] Pracownik pojawia się na liście z ikoną roli
- [ ] Edytuj pracownika → zmień rolę na kelner → Zapisz
- [ ] Resetuj hasło pracownika → e-mail z linkiem do resetu
- [ ] Usuń pracownika

---

## 13. Panel managera — uprawnienia ról

- [ ] Przejdź do **Uprawnienia ról**
- [ ] Tabela uprawnień per rola widoczna
- [ ] Zmień uprawnienie dla roli (checkbox) → Zapisz
- [ ] Zaloguj się jako pracownik z tą rolą → sprawdź czy uprawnienie jest egzekwowane

---

## 14. Panel managera — klienci

- [ ] Przejdź do **Klienci**
- [ ] Lista klientów widoczna (imię, e-mail, liczba zamówień, punkty lojalnościowe)
- [ ] Kliknij klienta → szczegóły: historia zamówień, punkty, poziom lojalnościowy
- [ ] Ręcznie dodaj punkty lojalnościowe klientowi → Zapisz
- [ ] Kliknij "Eksportuj CSV" → plik pobierany z listą klientów

---

## 15. Panel managera — lojalność

- [ ] Przejdź do **Lojalność**
- [ ] Konfiguracja poziomów widoczna (progi punktów, mnożniki)
- [ ] Zmień próg dla poziomu → Zapisz
- [ ] Przejdź do **Nagrody** → dodaj nagrodę (nazwa, punkty, opis) → Zapisz
- [ ] Nagroda pojawia się na liście
- [ ] Edytuj nagrodę → Zapisz
- [ ] Usuń nagrodę
- [ ] Przejdź do **Kampanie lojalnościowe**
- [ ] Dodaj kampanię z mnożnikiem punktów i datami trwania → Zapisz
- [ ] Edytuj kampanię → Zapisz
- [ ] Usuń kampanię

---

## 16. Panel managera — raporty sprzedaży

- [ ] Przejdź do **Raporty**
- [ ] Podsumowanie: przychód, liczba zamówień, średnia wartość, użyte kody rabatowe
- [ ] Wykres widoczny
- [ ] Kliknij "Eksportuj CSV" → plik pobierany

---

## 17. Panel managera — raporty pracowników

- [ ] Przejdź do **Raporty pracowników**
- [ ] Lista raportów od pracowników widoczna
- [ ] Oznacz raport jako przeczytany
- [ ] Oznacz wszystkie jako przeczytane
- [ ] Eksportuj raporty do CSV

---

## 18. Panel managera — stoliki i QR

- [ ] Przejdź do **Stoliki**
- [ ] Dodaj stolik: numer, pojemność → Zapisz
- [ ] Stolik pojawia się na liście
- [ ] Kliknij "Generuj QR" → obraz QR kodu widoczny i zapisywany
- [ ] Włącz/wyłącz stolik (toggle)
- [ ] Edytuj stolik → Zapisz
- [ ] Usuń stolik
- [ ] Przejdź do zakładki **Rezerwacje** → lista rezerwacji widoczna
- [ ] Zmień status rezerwacji (potwierdzona / anulowana)

---

## 19. Panel managera — strefy dostawy

- [ ] Przejdź do **Strefy dostawy**
- [ ] Mapa OpenStreetMap się ładuje
- [ ] Narysuj strefę (kliknij punkty na mapie)
- [ ] Wpisz nazwę strefy i koszt dostawy → Zapisz
- [ ] Strefa pojawia się na liście i na mapie
- [ ] Edytuj strefę → Zapisz
- [ ] Usuń strefę

---

## 20. Panel managera — opinie klientów

- [ ] Przejdź do **Opinie**
- [ ] Lista opinii widoczna
- [ ] Zatwierdź / odrzuć opinię
- [ ] Usuń opinię

---

## 21. Panel managera — ustawienia

- [ ] Przejdź do **Ustawienia**
- [ ] **Ogólne:** zmień nazwę restauracji, telefon, adres, NIP, opis → Zapisz
- [ ] **Wygląd:** zmień kolor akcentu (HEX), czcionkę, URL logo, URL hero → Zapisz → sprawdź kolor na stronie klienta
- [ ] **Wygląd:** dodaj blok na stronie głównej (ogłoszenie) → Zapisz → widoczny na stronie klienta
- [ ] **Godziny:** zmień godziny otwarcia → Zapisz
- [ ] **Zamówienia:** zmień minimalną wartość zamówienia, czas realizacji → Zapisz
- [ ] **Płatności:** włącz / wyłącz metody (gotówka, karta) → Zapisz → sprawdź na checkoucie
- [ ] **Powiadomienia:** włącz e-mail, wpisz adres → Zapisz
- [ ] **Urlop / zamknięcie:** włącz tryb urlopu, wpisz komunikat → Zapisz → strona klienta pokazuje komunikat i blokuje zamówienia
- [ ] Wyłącz tryb urlopu → Zapisz
- [ ] **Lojalność:** konfiguracja trybów naliczania punktów, bonusy urodzinowe, wygasanie → Zapisz
- [ ] **Regulamin / polityka prywatności:** edytuj treść → Zapisz → sprawdź na stronie klienta

---

## 22. Panel managera — wsparcie

- [ ] Przejdź do **Wsparcie**
- [ ] Utwórz zgłoszenie: temat, treść → Wyślij
- [ ] Zgłoszenie pojawia się na liście
- [ ] Otwórz zgłoszenie → odpisz
- [ ] Zamknij zgłoszenie
- [ ] Landlord widzi zgłoszenie w swoim panelu i odpisuje → odpowiedź pojawia się u managera

---

## 23. Panel managera — licencja

- [ ] Przejdź do **Licencja**
- [ ] Dane licencji widoczne: plan, data wygaśnięcia, status

---

## 24. Strona klienta — przeglądanie menu

- [ ] Wejdź na `test.localhost:8000` (bez logowania)
- [ ] Baner GDPR (cookie consent) pojawia się → kliknij "Akceptuj" → znika i nie wraca po odświeżeniu
- [ ] Kategorie i produkty widoczne
- [ ] Kliknij produkt → modal otwiera się ze zdjęciem, opisem, wariantami, dodatkami, alergenami
- [ ] Wyszukaj produkt (jeśli jest wyszukiwarka)
- [ ] Przejdź na podstronę **Kontakt** → formularz widoczny
- [ ] Wyślij wiadomość kontaktową → potwierdzenie "Wysłano"
- [ ] Przejdź na **Regulamin** → treść widoczna
- [ ] Przejdź na **Politykę prywatności** → treść widoczna

---

## 25. Strona klienta — rejestracja i logowanie

- [ ] Kliknij "Zarejestruj się"
- [ ] Wypełnij formularz: imię, e-mail, hasło → Zarejestruj
- [ ] E-mail powitalny przychodzi
- [ ] Wyloguj się
- [ ] Zaloguj się ponownie tymi samymi danymi
- [ ] Reset hasła: kliknij "Zapomniałem hasła" → wpisz e-mail → link w e-mailu
- [ ] Otwórz link → wpisz nowe hasło → zaloguj się nowym hasłem

---

## 26. Strona klienta — koszyk i checkout

- [ ] Kliknij produkt → wybierz wariant, dodaj dodatki → "Dodaj do koszyka"
- [ ] Ikonka koszyka w kolorze systemu z badge liczby produktów
- [ ] Otwórz koszyk → produkty z cenami, suma
- [ ] Zmień ilość produktu → suma się aktualizuje
- [ ] Usuń produkt z koszyka
- [ ] Kliknij "Zamów" → formularz checkoutu
- [ ] Wybierz **Dostawa**: wpisz adres → system waliduje strefę dostawy
- [ ] Wybierz **Odbiór osobisty** → pole adresu znika
- [ ] Wpisz kod rabatowy (jeśli istnieje) → rabat naliczany
- [ ] Wybierz metodę płatności (gotówka) → Złóż zamówienie
- [ ] Potwierdzenie z numerem `ORD-YYMMDD-NNNN`
- [ ] E-mail potwierdzenia zamówienia przychodzi

---

## 27. Strona klienta — śledzenie zamówienia

- [ ] Po złożeniu zamówienia wejdź na stronę śledzenia (`/order/{numer}/tracking`)
- [ ] Status "oczekujące" widoczny
- [ ] Manager zmienia status → strona automatycznie się aktualizuje (WebSocket)
- [ ] Po przypisaniu kierowcy i wysłaniu lokalizacji GPS → mapa OpenStreetMap z pozycją kierowcy pojawia się

---

## 28. Strona klienta — moje konto

- [ ] Przejdź do **Moje konto** (zalogowany klient)
- [ ] Zmień imię i numer telefonu → Zapisz
- [ ] Zmień hasło → Zapisz → wylogowanie → logowanie nowym hasłem
- [ ] Zmień e-mail → Zapisz → link weryfikacyjny przychodzi na nowy adres
- [ ] Otwórz link weryfikacyjny → e-mail zmieniony
- [ ] Sprawdź **Historia zamówień** → lista widoczna
- [ ] Kliknij zamówienie → szczegóły
- [ ] Anuluj zamówienie (jeśli status na to pozwala)
- [ ] Kliknij "Faktura/Paragon" → strona HTML z fakturą, przycisk drukowania
- [ ] Zakładka **Lojalność** → punkty, poziom, postęp do kolejnego poziomu, lista nagród do realizacji

---

## 29. Strona klienta — rezerwacja stolika

- [ ] Przejdź do **Rezerwacje** (jeśli włączone w ustawieniach)
- [ ] Wybierz datę, godzinę, liczbę osób, podaj imię i e-mail → Zarezerwuj
- [ ] Potwierdzenie rezerwacji widoczne na stronie
- [ ] E-mail potwierdzenia rezerwacji przychodzi
- [ ] Manager widzi rezerwację w swoim panelu → zatwierdza → e-mail potwierdzenia do klienta
- [ ] Manager anuluje rezerwację → e-mail anulacji do klienta

---

## 30. Strona klienta — opinia po zamówieniu

- [ ] Po zamówieniu ze statusem "Zakończone" kliknij "Oceń zamówienie" w historii zamówień (Moje konto)
- [ ] Wystaw ocenę gwiazdkową i treść, wyślij
- [ ] Opinia pojawia się w panelu managera (Manager → Opinie)

---

## 31. Panel kuchni (KDS)

- [ ] Zaloguj się jako kucharz na `test.localhost:8000/staff`
- [ ] Przekierowanie na `/staff/kitchen`
- [ ] Zamówienia w kolejności widoczne
- [ ] Zmień status zamówienia na "gotowe" → znika z kolejki / zmienia wygląd
- [ ] Nowe zamówienie pojawia się automatycznie (WebSocket)

---

## 32. Panel kelnera / obsługa stolików

- [ ] Zaloguj się jako kelner
- [ ] Przejdź do **Kelner** → lista stolików z statusami
- [ ] Zmień status stolika (wolny / zajęty / zarezerwowany)
- [ ] Wygeneruj QR kod dla stolika

---

## 33. Panel kelnera — POS

- [ ] Przejdź do **POS**
- [ ] Wybierz stolik
- [ ] Dodaj produkty do zamówienia (wyszukaj po nazwie)
- [ ] Dodaj notatkę do zamówienia
- [ ] Złóż zamówienie "na miejscu" → pojawia się na liście managera i w KDS
- [ ] Wybierz metodę płatności → sfinalizuj

---

## 34. Panel kelnera — raport pracownika

- [ ] Przejdź do **Mój raport** (jeśli kelner ma dostęp)
- [ ] Wypełnij raport z zmiany → Wyślij
- [ ] Raport pojawia się w panelu managera (Raporty pracowników)

---

## 35. Panel kierowcy

- [ ] Zaloguj się jako kierowca
- [ ] Przejdź do `/staff/driver`
- [ ] Lista zamówień do dostawy widoczna
- [ ] Kliknij "Przyjmij zamówienie" → przypisany do siebie
- [ ] Kliknij "Aktualizuj lokalizację" → GPS wysyłany do systemu
- [ ] Na stronie śledzenia klienta pojawia się mapa z pozycją kierowcy
- [ ] Kliknij "Dostarczone" → status zamówienia zmienia się

---

## 36. Przepływ powiadomień push

- [ ] Zaloguj się jako pracownik w Chrome
- [ ] Przeglądarka pyta o zgodę na powiadomienia → Zezwól
- [ ] Klient składa nowe zamówienie
- [ ] Powiadomienie push pojawia się na pulpicie (nawet gdy karta jest w tle)

---

## 37. Reset hasła pracownika

- [ ] Wyloguj się ze staffa
- [ ] Przejdź na `/forgot-password`
- [ ] Wpisz e-mail pracownika → "Link wysłany"
- [ ] Otwórz link z e-maila → wpisz nowe hasło → Zapisz
- [ ] Zaloguj się nowym hasłem

---

## 38. PWA

- [ ] Otwórz `test.localhost:8000` w Chrome na telefonie lub desktop
- [ ] W pasku adresu pojawia się ikona instalacji → Zainstaluj
- [ ] Aplikacja otwiera się jako osobne okno bez paska przeglądarki
- [ ] Ikony aplikacji (192x192, 512x512) wyglądają poprawnie

---

## 39. Tryb offline (Service Worker)

- [ ] Otwórz stronę klienta
- [ ] Wyłącz internet (DevTools → Network → Offline)
- [ ] Odśwież stronę → wyświetla się strona `/offline.html`
- [ ] Włącz internet → strona wraca do normalnego działania

---

## 40. Strona błędu licencji

- [ ] Wygaś licencję tenanta (zmień datę w bazie lub zawieś konto w landlordzie)
- [ ] Wejdź na `test.localhost:8000` → strona "Licencja wygasła" widoczna
- [ ] Panel managera niedostępny
- [ ] Po reaktywacji przez landlorda → strona wraca do normy

---

## 41. Rate limiting

- [ ] Wyślij formularz checkoutu 6 razy z rzędu szybko → 6. próba zwraca błąd 429
- [ ] Wyślij formularz resetu hasła 6 razy → błąd 429

---

## 42. Sitemap i robots.txt

- [ ] Wejdź na `test.localhost:8000/sitemap.xml` → plik XML z URLami widoczny
- [ ] Wejdź na `test.localhost:8000/robots.txt` → plik tekstowy widoczny
