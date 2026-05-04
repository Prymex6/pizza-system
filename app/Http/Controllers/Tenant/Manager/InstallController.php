<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Models\Tenant\RolePermission;
use App\Models\Tenant\Setting;
use App\Http\Controllers\Tenant\Manager\RolePermissionsController;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class InstallController extends Controller
{
    public function index()
    {
        if (Setting::get('setup_completed', false)) {
            return redirect()->route('tenant.manager.dashboard');
        }

        $existingManager = User::where('role', 'manager')->first();
        $step = session('install_step', 0);

        return Inertia::render('Tenant/Install', [
            'step'                   => $step,
            'existing_manager_email' => $existingManager?->email,
        ]);
    }

    /** Step 0 — create manager account OR update password of existing manager */
    public function createAccount(Request $request)
    {
        if (Setting::get('setup_completed', false)) {
            return redirect('/');
        }

        $existingManager = User::where('role', 'manager')->first();

        if ($existingManager) {
            // Manager already exists — just update their password and log in
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $existingManager->update(['password' => Hash::make($request->password)]);
            Auth::guard('tenant')->login($existingManager);
        } else {
            // Fresh install — create new manager account
            $validated = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role'     => 'manager',
            ]);

            Auth::guard('tenant')->login($user);
        }

        session(['install_step' => 1]);

        return redirect('/install');
    }

    /** Step 1 — save basic restaurant info */
    public function saveRestaurant(Request $request)
    {
        $validated = $request->validate([
            'restaurant_name'       => ['required', 'string', 'max:255'],
            'restaurant_owner_name' => ['required', 'string', 'max:255'],
            'restaurant_phone'      => ['nullable', 'string', 'max:30'],
            'restaurant_email'      => ['nullable', 'email', 'max:255'],
            'restaurant_address'    => ['nullable', 'string', 'max:500'],
            'restaurant_nip'        => ['nullable', 'string', 'max:20'],
        ]);

        if (array_key_exists('restaurant_phone', $validated)) {
            $validated['restaurant_phone'] = PhoneHelper::normalize($validated['restaurant_phone']) ?? '';
        }

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        session(['install_step' => 2]);

        return redirect('/install');
    }

    /** Step 2 — save opening hours and complete setup */
    public function saveHours(Request $request)
    {
        $hours = $request->validate([
            'opening_hours' => ['nullable', 'array'],
        ])['opening_hours'] ?? [];

        // Store as type 'json' — consistent with SettingsController
        Setting::set('opening_hours', $hours, 'json');

        return $this->finishSetup();
    }

    /** Complete setup without saving hours (skip) */
    public function complete(Request $request)
    {
        return $this->finishSetup();
    }

    private function finishSetup()
    {
        Setting::set('setup_completed', true);
        session()->forget('install_step');
        $this->seedDefaultRolePermissions();
        $this->seedDefaultContent();

        return redirect()->route('tenant.manager.dashboard')
            ->with('success', 'Konfiguracja zakończona! Witaj w ' . config('app.name') . '.');
    }

    private function seedDefaultContent(): void
    {
        $name = htmlspecialchars(Setting::get('restaurant_name', 'Restauracja'), ENT_QUOTES);

        if (!Setting::has('logo_url')) {
            Setting::set('logo_url', '/images/logo.png');
        }
        if (!Setting::has('favicon_url')) {
            Setting::set('favicon_url', '/images/favicon.png');
        }
        if (!Setting::has('hero_image_url')) {
            Setting::set('hero_image_url', '/images/hero.png');
        }

        if (!Setting::has('restaurant_description')) {
            Setting::set('restaurant_description',
                'Witamy w restauracji ' . $name . '! '
                . 'Oferujemy pyszne dania przygotowywane ze świeżych składników. '
                . 'Zamów online z dostawą lub odbierz osobiście — szybko, wygodnie i smacznie.'
            );
        }

        if (!Setting::has('about_text')) {
            Setting::set('about_text',
                'Witamy w restauracji ' . $name . '! '
                . 'Oferujemy pyszne dania przygotowywane ze świeżych składników. '
                . 'Zamów online z dostawą lub odbierz osobiście — szybko, wygodnie i smacznie.'
            );
        }

        if (!Setting::has('terms_content')) {
            Setting::set('terms_content', <<<'HTML'
<h1>Regulamin świadczenia usług drogą elektroniczną</h1>
<p style="color:#6b7280;font-size:0.9em;">Wersja obowiązująca od 1 stycznia 2026 r.</p>

<p><strong>Usługodawca:</strong> {restaurant_owner_name}<br>
Nazwa handlowa: {restaurant_name}<br>
Adres: {restaurant_address}<br>
E-mail: {restaurant_email} &nbsp;|&nbsp; Tel.: {restaurant_phone}<br>
NIP: {restaurant_nip}</p>

<hr>

<h2>§ 1. Definicje</h2>
<ol>
  <li><strong>Serwis</strong> – serwis internetowy prowadzony przez Usługodawcę pod adresem dostępnym za pośrednictwem przeglądarki internetowej, umożliwiający składanie zamówień na posiłki i napoje.</li>
  <li><strong>Usługodawca</strong> – {restaurant_owner_name}, dane wskazane powyżej.</li>
  <li><strong>Klient</strong> – pełnoletnia osoba fizyczna posiadająca pełną zdolność do czynności prawnych, osoba prawna lub jednostka organizacyjna nieposiadająca osobowości prawnej, korzystająca z Serwisu.</li>
  <li><strong>Konsument</strong> – Klient będący osobą fizyczną dokonujący zakupów w celach niezwiązanych bezpośrednio z działalnością gospodarczą lub zawodową (art. 22¹ Kodeksu cywilnego).</li>
  <li><strong>Zamówienie</strong> – oświadczenie woli Klienta złożone za pośrednictwem Serwisu, stanowiące ofertę zawarcia umowy sprzedaży.</li>
  <li><strong>Umowa</strong> – umowa sprzedaży zawierana na odległość pomiędzy Klientem a Usługodawcą za pośrednictwem Serwisu.</li>
  <li><strong>Regulamin</strong> – niniejszy dokument.</li>
</ol>

<h2>§ 2. Postanowienia ogólne</h2>
<ol>
  <li>Niniejszy Regulamin określa zasady i warunki korzystania z Serwisu, składania Zamówień, zawierania Umów oraz tryb postępowania reklamacyjnego.</li>
  <li>Regulamin jest dostępny nieodpłatnie pod adresem Serwisu w formie umożliwiającej jego pobranie, utrwalenie i wydrukowanie.</li>
  <li>Korzystanie z Serwisu jest równoznaczne z zaakceptowaniem postanowień niniejszego Regulaminu.</li>
  <li>Serwis przeznaczony jest wyłącznie dla osób pełnoletnich. Składając zamówienie, Klient oświadcza, że ukończył 18 lat.</li>
  <li>Usługodawca świadczy usługi drogą elektroniczną w rozumieniu ustawy z dnia 18 lipca 2002 r. o świadczeniu usług drogą elektroniczną (Dz.U. 2002 nr 144 poz. 1204 ze zm.).</li>
</ol>

<h2>§ 3. Wymagania techniczne</h2>
<ol>
  <li>Korzystanie z Serwisu wymaga posiadania urządzenia z dostępem do sieci Internet oraz aktualnej przeglądarki internetowej obsługującej JavaScript i pliki cookies.</li>
  <li>Usługodawca nie ponosi odpowiedzialności za problemy techniczne wynikające z konfiguracji urządzenia Klienta lub jakości połączenia internetowego.</li>
</ol>

<h2>§ 4. Rejestracja i konto użytkownika</h2>
<ol>
  <li>Klient może korzystać z Serwisu bez rejestracji lub założyć Konto, podając dane niezbędne do jego utworzenia.</li>
  <li>Klient zobowiązany jest do podania danych prawdziwych, aktualnych i kompletnych.</li>
  <li>Klient ponosi odpowiedzialność za wszelkie działania podjęte z wykorzystaniem jego Konta.</li>
  <li>Usługodawca może usunąć Konto lub zawiesić dostęp w przypadku naruszenia Regulaminu, po uprzednim wezwaniu Klienta do zaprzestania naruszeń.</li>
  <li>Klient może w każdej chwili usunąć swoje Konto, składając stosowną dyspozycję w ustawieniach Konta lub kontaktując się z Usługodawcą. Usunięcie konta następuje bez zbędnej zwłoki, nie później niż w ciągu 30 dni, a dane osobowe są anonimizowane zgodnie z Polityką Prywatności.</li>
</ol>

<h2>§ 5. Składanie zamówień i zawarcie umowy</h2>
<ol>
  <li>Zamówienia można składać za pośrednictwem Serwisu przez całą dobę, 7 dni w tygodniu. Zamówienia są realizowane wyłącznie w godzinach otwarcia wskazanych w Serwisie.</li>
  <li>Złożenie Zamówienia stanowi ofertę Klienta w rozumieniu art. 66 Kodeksu cywilnego. Umowa zostaje zawarta z chwilą potwierdzenia przyjęcia Zamówienia do realizacji przez Usługodawcę.</li>
  <li>Potwierdzenie przyjęcia Zamówienia wysyłane jest na adres e-mail Klienta (jeżeli został podany) lub wyświetlane w Serwisie.</li>
  <li>Usługodawca zastrzega sobie prawo do odmowy realizacji Zamówienia lub kontaktu w celu jego weryfikacji, w szczególności w przypadku: podejrzenia błędu lub nadużycia, niedostępności produktu, przekroczenia strefy dostawy, awarii systemu płatności.</li>
  <li>Prezentacja produktów w Serwisie stanowi zaproszenie do zawarcia umowy w rozumieniu art. 71 Kodeksu cywilnego, a nie ofertę w rozumieniu art. 66 Kodeksu cywilnego.</li>
</ol>

<h2>§ 6. Ceny i płatności</h2>
<ol>
  <li>Wszystkie ceny podane w Serwisie są cenami brutto wyrażonymi w złotych polskich (PLN) i zawierają podatek VAT w obowiązującej stawce.</li>
  <li>Koszt dostawy jest podawany odrębnie przed finalizacją Zamówienia i stanowi element ceny łącznej.</li>
  <li>Usługodawca oferuje następujące metody płatności: gotówka przy odbiorze, karta płatnicza przy odbiorze, płatność elektroniczna (przelew online, BLIK) – o ile opcja jest aktywna w Serwisie.</li>
  <li>W przypadku płatności online, Klient zostanie przekierowany do zewnętrznego operatora płatności. Usługodawca nie przechowuje danych kart płatniczych Klienta.</li>
  <li>Usługodawca zastrzega sobie prawo do zmiany cen produktów oraz wprowadzania i odwoływania promocji. Zmiany cen nie dotyczą Zamówień już przyjętych do realizacji.</li>
</ol>

<h2>§ 7. Dostawa i odbiór</h2>
<ol>
  <li>Usługodawca realizuje dostawy wyłącznie na adresy mieszczące się w zdefiniowanych strefach dostawy, których aktualna mapa dostępna jest w Serwisie.</li>
  <li>Podane czasy dostawy mają charakter szacunkowy. Usługodawca dołoży wszelkich starań, aby zamówienia były realizowane w możliwie najkrótszym czasie. Opóźnienia wynikające z siły wyższej, warunków drogowych lub atmosferycznych nie stanowią podstawy do odszkodowania.</li>
  <li>Klient zobowiązany jest do pozostawania pod wskazanym adresem dostawy lub zapewnienia innej osoby do odbioru zamówienia w deklarowanym czasie.</li>
  <li>W przypadku braku możliwości dostarczenia Zamówienia z winy Klienta (nieobecność, błędny adres), Usługodawca może pobrać opłatę za dostawę lub anulować Zamówienie.</li>
  <li>Klient może odebrać Zamówienie osobiście pod adresem: {restaurant_address}, w godzinach otwarcia wskazanych w Serwisie.</li>
</ol>

<h2>§ 8. Prawa Konsumenta – odstąpienie od umowy</h2>
<ol>
  <li>Na podstawie art. 38 pkt 4 ustawy z dnia 30 maja 2014 r. o prawach konsumenta (Dz.U. 2014 poz. 827 ze zm.), prawo do odstąpienia od umowy zawartej na odległość <strong>nie przysługuje</strong> Konsumentowi w odniesieniu do umów dotyczących świadczeń o właściwościach określonych przez Konsumenta w złożonym przez niego zamówieniu lub ściśle związanych z jego osobą, a także rzeczy, które po dostarczeniu, ze względu na swój charakter, zostają nierozłącznie połączone z innymi rzeczami – w tym produktów spożywczych przygotowywanych na zamówienie.</li>
  <li>Powyższe wyłączenie wynika z nietrwałego charakteru produktów gastronomicznych i jest zgodne z art. 38 pkt 4 i 12 ustawy o prawach konsumenta.</li>
</ol>

<h2>§ 9. Reklamacje</h2>
<ol>
  <li>Klient ma prawo złożyć reklamację dotyczącą realizacji Zamówienia lub funkcjonowania Serwisu.</li>
  <li>Reklamacje należy składać niezwłocznie po stwierdzeniu niezgodności, nie później jednak niż w ciągu 24 godzin od odbioru Zamówienia:
    <ul>
      <li>telefonicznie: {restaurant_phone},</li>
      <li>elektronicznie: {restaurant_email},</li>
      <li>pisemnie na adres: {restaurant_address}.</li>
    </ul>
  </li>
  <li>Reklamacja powinna zawierać: numer Zamówienia, datę i godzinę odbioru, opis niezgodności oraz oczekiwany sposób jej rozpatrzenia.</li>
  <li>Usługodawca rozpatruje reklamacje w terminie 14 dni kalendarzowych od dnia jej otrzymania i informuje Klienta o wyniku drogą wskazaną w reklamacji.</li>
  <li>Konsumentowi przysługuje prawo do skorzystania z pozasądowych metod rozpatrywania reklamacji i dochodzenia roszczeń, w tym mediacji prowadzonej przez właściwe Inspekcje Handlowe. Więcej informacji dostępnych na stronie <a href="https://www.uokik.gov.pl" target="_blank">uokik.gov.pl</a>.</li>
</ol>

<h2>§ 10. Ochrona danych osobowych</h2>
<p>Dane osobowe Klientów przetwarzane są zgodnie z Rozporządzeniem Parlamentu Europejskiego i Rady (UE) 2016/679 z dnia 27 kwietnia 2016 r. (RODO) oraz obowiązującymi przepisami krajowymi. Szczegółowe informacje zawiera Polityka Prywatności dostępna w Serwisie.</p>

<h2>§ 11. Własność intelektualna</h2>
<p>Wszelkie treści zamieszczone w Serwisie (grafiki, teksty, logotypy, układ stron) stanowią własność Usługodawcy lub podmiotów, którym przysługują odpowiednie prawa, i są chronione przepisami prawa autorskiego. Ich kopiowanie, modyfikowanie lub rozpowszechnianie bez zgody Usługodawcy jest zabronione.</p>

<h2>§ 12. Odpowiedzialność</h2>
<ol>
  <li>Usługodawca nie ponosi odpowiedzialności za przerwy w funkcjonowaniu Serwisu wynikające z przyczyn technicznych niezależnych od Usługodawcy, konserwacji lub działania siły wyższej.</li>
  <li>Usługodawca nie ponosi odpowiedzialności za treści publikowane przez Klientów (np. opinie) niezgodne z rzeczywistością.</li>
  <li>Odpowiedzialność Usługodawcy wobec Klientów niebędących Konsumentami ograniczona jest do wartości złożonego Zamówienia.</li>
</ol>

<h2>§ 13. Postanowienia końcowe</h2>
<ol>
  <li>W sprawach nieuregulowanych niniejszym Regulaminem zastosowanie mają przepisy prawa polskiego, w szczególności: Kodeksu cywilnego, ustawy o świadczeniu usług drogą elektroniczną, ustawy o prawach konsumenta oraz RODO.</li>
  <li>Wszelkie spory wynikające z korzystania z Serwisu będą rozstrzygane przez sąd powszechny właściwy miejscowo i rzeczowo dla siedziby Usługodawcy, z zastrzeżeniem bezwzględnie obowiązujących przepisów dotyczących Konsumentów.</li>
  <li>Usługodawca zastrzega sobie prawo do zmiany Regulaminu. O zmianach Klienci posiadający Konto zostaną powiadomieni drogą elektroniczną z co najmniej 14-dniowym wyprzedzeniem. Dalsze korzystanie z Serwisu po wejściu zmian w życie jest równoznaczne z ich akceptacją.</li>
  <li>Niniejszy Regulamin obowiązuje od dnia 1 stycznia 2026 r. i zastępuje wszelkie wcześniejsze wersje.</li>
</ol>
HTML, 'text');
        }

        if (!Setting::has('privacy_content')) {
            Setting::set('privacy_content', <<<'HTML'
<h1>Polityka Prywatności i Ochrony Danych Osobowych</h1>
<p style="color:#6b7280;font-size:0.9em;">Wersja obowiązująca od 1 stycznia 2026 r.</p>

<p>Niniejsza Polityka Prywatności została sporządzona zgodnie z wymogami Rozporządzenia Parlamentu Europejskiego i Rady (UE) 2016/679 z dnia 27 kwietnia 2016 r. w sprawie ochrony osób fizycznych w związku z przetwarzaniem danych osobowych i w sprawie swobodnego przepływu takich danych oraz uchylenia dyrektywy 95/46/WE (<strong>RODO</strong>), a także ustawy z dnia 10 maja 2018 r. o ochronie danych osobowych (Dz.U. 2018 poz. 1000 ze zm.).</p>

<hr>

<h2>1. Administrator danych osobowych</h2>
<p>Administratorem Twoich danych osobowych jest:</p>
<p><strong>{restaurant_owner_name}</strong><br>
Nazwa handlowa: {restaurant_name}<br>
Adres: {restaurant_address}<br>
E-mail: {restaurant_email}<br>
Tel.: {restaurant_phone}<br>
NIP: {restaurant_nip}</p>
<p>Administrator nie powołał Inspektora Ochrony Danych. W sprawach dotyczących danych osobowych prosimy o kontakt pod adresem e-mail wskazanym powyżej.</p>

<h2>2. Kategorie przetwarzanych danych osobowych</h2>
<p>W zależności od sposobu korzystania z Serwisu przetwarzamy następujące kategorie danych:</p>
<table style="width:100%;border-collapse:collapse;font-size:0.9em;">
  <thead>
    <tr style="background:#f3f4f6;">
      <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Kategoria</th>
      <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Zakres danych</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Dane identyfikacyjne</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">imię i nazwisko</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Dane kontaktowe</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">adres e-mail, numer telefonu</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Dane adresowe</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">adres dostawy (ulica, numer, kod pocztowy, miejscowość)</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Dane transakcyjne</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">historia zamówień, wartość zamówień, metoda płatności</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Dane konta</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">adres e-mail, hasło (w formie zaszyfrowanej – hash bcrypt), data rejestracji</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Dane techniczne</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">adres IP, typ przeglądarki, identyfikatory sesji, pliki cookies</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Dane opcjonalne</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">data urodzenia (wyłącznie w celu przyznania bonusu urodzinowego – podanie dobrowolne)</td>
    </tr>
  </tbody>
</table>

<h2>3. Cele i podstawy prawne przetwarzania danych</h2>
<table style="width:100%;border-collapse:collapse;font-size:0.9em;">
  <thead>
    <tr style="background:#f3f4f6;">
      <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Cel przetwarzania</th>
      <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Podstawa prawna (RODO)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Realizacja i obsługa zamówień</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. b – wykonanie umowy</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Prowadzenie konta użytkownika</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. b – wykonanie umowy</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Obsługa reklamacji i roszczeń</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. b – wykonanie umowy; art. 6 ust. 1 lit. f – uzasadniony interes</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Wypełnienie obowiązków podatkowych i rachunkowych</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. c – obowiązek prawny</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Marketing bezpośredni własnych produktów i usług</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. f – uzasadniony interes Administratora</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Wysyłka newslettera i komunikacji handlowej (za zgodą)</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. a – zgoda osoby, której dane dotyczą</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Zapobieganie nadużyciom, bezpieczeństwo systemu</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. f – uzasadniony interes Administratora</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #e5e7eb;">Statystyki i analiza ruchu (pliki cookies analityczne – za zgodą)</td>
      <td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. a – zgoda osoby, której dane dotyczą</td>
    </tr>
  </tbody>
</table>

<h2>4. Okres przechowywania danych</h2>
<ul>
  <li><strong>Dane zamówieniowe i transakcyjne</strong> – przez okres 5 lat od końca roku, w którym złożono zamówienie (wymogi wynikające z przepisów podatkowych i rachunkowych).</li>
  <li><strong>Dane konta użytkownika</strong> – do momentu usunięcia konta; po usunięciu konta dane są anonimizowane, z wyjątkiem danych niezbędnych do realizacji obowiązków prawnych.</li>
  <li><strong>Dane przetwarzane na podstawie zgody</strong> – do momentu cofnięcia zgody.</li>
  <li><strong>Dane przetwarzane w celu dochodzenia lub obrony roszczeń</strong> – do upływu okresu przedawnienia roszczeń (co do zasady 3 lub 6 lat – w zależności od rodzaju roszczenia).</li>
  <li><strong>Pliki cookies sesyjne</strong> – do zakończenia sesji przeglądarki; <strong>pliki cookies trwałe</strong> – zgodnie z okresem wskazanym w Polityce Cookies.</li>
</ul>

<h2>5. Odbiorcy danych</h2>
<p>Twoje dane mogą być udostępniane wyłącznie podmiotom, z których usług korzystamy w celu realizacji zamówień i obsługi Serwisu, m.in.:</p>
<ul>
  <li>dostawcy usług hostingowych i infrastruktury IT,</li>
  <li>operatorzy systemów płatności elektronicznych,</li>
  <li>podmioty świadczące usługi kurierskie i dostawcze,</li>
  <li>biura rachunkowe i kancelarie prawne (w zakresie wymaganym przepisami prawa),</li>
  <li>organy publiczne – wyłącznie na podstawie obowiązujących przepisów prawa i stosownych żądań.</li>
</ul>
<p>Administrator nie sprzedaje danych osobowych osobom trzecim ani nie udostępnia ich w celach reklamowych podmiotom zewnętrznym.</p>

<h2>6. Przekazywanie danych do państw trzecich</h2>
<p>Co do zasady dane osobowe przetwarzane są na terytorium Europejskiego Obszaru Gospodarczego (EOG). W przypadku korzystania z usług podmiotów spoza EOG (np. Google Analytics, Facebook Pixel – jeśli aktywne), przekazywanie danych odbywa się na podstawie standardowych klauzul umownych zatwierdzonych przez Komisję Europejską lub innych mechanizmów przewidzianych w RODO.</p>

<h2>7. Prawa osób, których dane dotyczą</h2>
<p>Przysługują Ci następujące prawa w związku z przetwarzaniem danych osobowych:</p>
<ul>
  <li><strong>Prawo dostępu</strong> (art. 15 RODO) – prawo do uzyskania informacji o przetwarzanych danych i kopii danych.</li>
  <li><strong>Prawo do sprostowania</strong> (art. 16 RODO) – prawo do żądania poprawienia nieprawidłowych lub uzupełnienia niekompletnych danych.</li>
  <li><strong>Prawo do usunięcia danych</strong> (art. 17 RODO) – prawo do żądania usunięcia danych („prawo do bycia zapomnianym"), z zastrzeżeniem przypadków, gdy przetwarzanie jest wymagane przepisami prawa.</li>
  <li><strong>Prawo do ograniczenia przetwarzania</strong> (art. 18 RODO) – prawo do żądania ograniczenia przetwarzania danych w określonych przypadkach.</li>
  <li><strong>Prawo do przenoszenia danych</strong> (art. 20 RODO) – prawo do otrzymania danych w ustrukturyzowanym formacie i przesłania ich innemu administratorowi.</li>
  <li><strong>Prawo do sprzeciwu</strong> (art. 21 RODO) – prawo do wniesienia sprzeciwu wobec przetwarzania danych na podstawie uzasadnionego interesu, w tym profilowania.</li>
  <li><strong>Prawo do cofnięcia zgody</strong> – w każdej chwili, bez wpływu na zgodność z prawem przetwarzania dokonanego przed jej cofnięciem.</li>
  <li><strong>Prawo do wniesienia skargi</strong> – do Prezesa Urzędu Ochrony Danych Osobowych (UODO), ul. Stawki 2, 00-193 Warszawa, gdy uznasz, że przetwarzanie Twoich danych narusza przepisy RODO.</li>
</ul>
<p>Aby skorzystać z powyższych praw, skontaktuj się z Administratorem pod adresem: <strong>{restaurant_email}</strong>. Odpowiedź udzielana jest bez zbędnej zwłoki, nie później niż w ciągu 30 dni od otrzymania wniosku.</p>

<h2>8. Pliki cookies i podobne technologie</h2>
<ol>
  <li>Serwis stosuje pliki cookies oraz podobne technologie śledzące (local storage, session storage) w celu zapewnienia prawidłowego funkcjonowania Serwisu, zapamiętywania preferencji użytkownika oraz – za Twoją zgodą – celów analitycznych i marketingowych.</li>
  <li>Wyróżniamy następujące kategorie cookies:
    <ul>
      <li><strong>Niezbędne</strong> – konieczne do działania Serwisu (sesja, koszyk, CSRF) – nie wymagają zgody.</li>
      <li><strong>Funkcjonalne</strong> – zapamiętują preferencje użytkownika.</li>
      <li><strong>Analityczne</strong> – umożliwiają analizę ruchu (np. Google Analytics) – wymagają zgody.</li>
      <li><strong>Marketingowe</strong> – służą do wyświetlania spersonalizowanych reklam – wymagają zgody.</li>
    </ul>
  </li>
  <li>Zgody na poszczególne kategorie cookies możesz udzielić lub cofnąć w każdej chwili za pomocą ustawień banera cookie dostępnego w Serwisie lub w ustawieniach swojej przeglądarki.</li>
</ol>

<h2>9. Bezpieczeństwo danych</h2>
<p>Administrator stosuje odpowiednie środki techniczne i organizacyjne w celu ochrony danych osobowych przed nieuprawnionym dostępem, utratą lub zniszczeniem, w tym: szyfrowanie transmisji danych (protokół HTTPS/TLS), hashowanie haseł, kontrolę dostępu do systemów informatycznych oraz regularne aktualizacje oprogramowania.</p>

<h2>10. Profilowanie i zautomatyzowane podejmowanie decyzji</h2>
<p>Administrator może dokonywać profilowania w celu personalizacji oferty (np. rekomendacje produktów, program lojalnościowy). Profilowanie to nie prowadzi do zautomatyzowanego podejmowania decyzji wywołujących skutki prawne dla Klienta w rozumieniu art. 22 RODO. Klientowi przysługuje prawo do sprzeciwu wobec profilowania na podstawie art. 21 RODO.</p>

<h2>11. Zmiany Polityki Prywatności</h2>
<p>Administrator zastrzega sobie prawo do zmiany niniejszej Polityki Prywatności w przypadku zmian przepisów prawa, wytycznych organów nadzorczych lub sposobu przetwarzania danych. O istotnych zmianach Klienci posiadający Konto zostaną powiadomieni drogą elektroniczną z co najmniej 14-dniowym wyprzedzeniem. Aktualna wersja Polityki jest zawsze dostępna w Serwisie.</p>
<p>Niniejsza Polityka Prywatności obowiązuje od dnia 1 stycznia 2026 r.</p>
HTML, 'text');
        }
    }

    private function seedDefaultRolePermissions(): void
    {
        foreach (RolePermissionsController::DEFAULTS as $role => $permissions) {
            foreach ($permissions as $permission) {
                RolePermission::firstOrCreate(['role' => $role, 'permission' => $permission]);
            }
        }
    }
}
