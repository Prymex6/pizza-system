<template>
  <div class="policy-content-body">
    <h1>Polityka Prywatności i Plików Cookie</h1>
    <p class="policy-date">Ostatnia aktualizacja: 2025 r.</p>

    <p>
      Dziękujemy za zainteresowanie naszą Polityką Prywatności i Plików Cookie.
      Poniższy dokument wyjaśnia, w jaki sposób zbieramy, używamy i chronimy
      Twoje dane osobowe, a także informuje o wykorzystaniu plików cookie w
      naszym serwisie, zgodnie z przepisami <strong>RODO</strong> (Rozporządzenie Parlamentu
      Europejskiego i Rady (UE) 2016/679) oraz prawem polskim.
    </p>

    <h2 id="administrator">1. Administrator Danych Osobowych</h2>
    <p>Administratorem Twoich danych osobowych jest:</p>
    <div class="admin-details">
      <p><strong>{{ restaurantName }}</strong></p>
      <p>Email: {{ restaurantEmail }}</p>
    </div>

    <h2 id="dane-zbierane">2. Rodzaje zbieranych danych</h2>
    <p>Zbieramy dane w dwóch głównych kategoriach:</p>
    <ul>
      <li>
        <strong>Dane przekazane dobrowolnie:</strong> Imię, nazwisko, adres
        e-mail, numer telefonu (np. podczas składania zamówienia lub rezerwacji).
      </li>
      <li>
        <strong>Dane zbierane automatycznie (pliki cookie i analityka):</strong>
        Adres IP, dane przeglądarki, czas spędzony na stronie, odwiedzane
        podstrony. Służy to do celów statystycznych i funkcjonalnych.
      </li>
    </ul>

    <h2 id="cel-przetwarzania">3. Cel i Podstawa Prawna Przetwarzania</h2>
    <table>
      <thead>
        <tr>
          <th>Cel Przetwarzania</th>
          <th>Podstawa Prawna (RODO)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Realizacja zamówień i rezerwacji</td>
          <td>Art. 6 ust. 1 lit. b RODO (wykonanie umowy)</td>
        </tr>
        <tr>
          <td>Prowadzenie statystyk i analiza ruchu</td>
          <td>Art. 6 ust. 1 lit. a RODO (Twoja dobrowolna zgoda na pliki cookie)</td>
        </tr>
        <tr>
          <td>Wyświetlanie spersonalizowanych reklam</td>
          <td>Art. 6 ust. 1 lit. a RODO (Twoja dobrowolna zgoda na pliki cookie)</td>
        </tr>
      </tbody>
    </table>

    <h2 id="ciasteczka">4. Polityka Plików Cookie</h2>
    <p>
      Nasza strona wykorzystuje pliki cookie w celu zapewnienia prawidłowego
      działania oraz w celach analitycznych i marketingowych, <strong>pod warunkiem
      Twojej aktywnej zgody</strong>.
    </p>

    <h3>Rodzaje używanych plików cookie:</h3>
    <ul>
      <li>
        <strong>Niezbędne (Wymagane):</strong> Umożliwiają podstawowe funkcje
        serwisu (np. logowanie, koszyk, nawigacja). Nie wymagają zgody.
      </li>
      <li>
        <strong>Analityczne:</strong> Śledzą zachowanie użytkownika i pomagają
        nam optymalizować działanie strony (np. Google Analytics). Wymagają
        Twojej zgody.
      </li>
      <li>
        <strong>Marketingowe:</strong> Służą do targetowania reklam (np. Facebook Pixel). Wymagają
        Twojej zgody.
      </li>
    </ul>

    <h2 id="prawa-uzytkownika">5. Prawa Użytkownika</h2>
    <p>Masz prawo do:</p>
    <ul>
      <li>Żądania dostępu do swoich danych osobowych.</li>
      <li>Sprostowania, usunięcia lub ograniczenia przetwarzania swoich danych.</li>
      <li>Wniesienia sprzeciwu wobec przetwarzania.</li>
      <li>Przeniesienia danych.</li>
      <li>Wycofania zgody w dowolnym momencie (nie wpływa to na zgodność z prawem przetwarzania przed wycofaniem).</li>
      <li>Wniesienia skargi do organu nadzorczego (Prezesa Urzędu Ochrony Danych Osobowych).</li>
    </ul>

    <h2 id="kontakt">6. Kontakt</h2>
    <p>
      W sprawach związanych z danymi osobowymi i plikami cookie prosimy o
      kontakt pod adresem e-mail: <strong>{{ restaurantEmail }}</strong>.
    </p>
  </div>
</template>

<script setup>
defineProps({
  restaurantName: { type: String, default: 'Restauracja' },
  restaurantEmail: { type: String, default: '' },
})
</script>

<style scoped>
.policy-content-body {
  font-family: sans-serif;
  color: #212121;
  max-width: 100%;
  line-height: 1.7;
}
.policy-content-body h1 {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 10px;
}
.policy-content-body h2 {
  font-size: 18px;
  font-weight: 600;
  margin-top: 25px;
  margin-bottom: 10px;
  border-bottom: 1px solid #ddd;
  padding-bottom: 5px;
}
.policy-content-body h3 {
  font-size: 16px;
  font-weight: 600;
  margin-top: 15px;
  margin-bottom: 5px;
}
.policy-date {
  font-style: italic;
  font-size: 13px;
  color: #888;
  margin-bottom: 20px;
}
.policy-content-body p,
.policy-content-body li {
  font-size: 14px;
  margin-bottom: 10px;
}
.admin-details {
  background: #f9f9f9;
  border: 1px solid #eee;
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 20px;
  font-size: 14px;
}
.admin-details p {
  margin: 0;
}
.policy-content-body ul {
  padding-left: 20px;
  list-style: disc;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}
th,
td {
  border: 1px solid #eee;
  padding: 10px;
  text-align: left;
  font-size: 13px;
}
th {
  background-color: #f4f4f4;
  font-weight: 600;
}
</style>
