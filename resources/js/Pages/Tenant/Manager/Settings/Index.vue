<template>
    <ManagerLayout>
        <Head title="Ustawienia" />

        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Ustawienia</h1>
                <p class="mt-1 text-sm text-gray-600">Konfiguracja restauracji i systemu</p>
            </div>

            <form @submit.prevent="saveSettings" novalidate>
                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="flex space-x-8 overflow-x-auto">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            type="button"
                            @click="activeTab = tab.id"
                            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors"
                            :class="activeTab === tab.id
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        >
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <!-- General Tab -->
                <div v-show="activeTab === 'general'" class="bg-white shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900">Informacje o restauracji</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nazwa restauracji</label>
                            <input v-model="form.restaurant_name" name="restaurant_name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Imię i nazwisko właściciela / nazwa firmy
                                <span class="text-gray-400 font-normal text-xs">(do dokumentów prawnych)</span>
                            </label>
                            <input v-model="form.restaurant_owner_name" type="text" placeholder="np. Jan Kowalski lub XYZ Sp. z o.o." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            <p class="mt-1 text-xs text-gray-500">Wyświetlane w regulaminie i polityce prywatności jako dane Usługodawcy/Administratora danych.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                            <input v-model="form.restaurant_phone" type="tel" placeholder="123 456 789" @blur="form.restaurant_phone = formatPhone(form.restaurant_phone)" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input v-model="form.restaurant_email" type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adres</label>
                            <input v-model="form.restaurant_address" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NIP <span class="text-gray-400 font-normal text-xs">(opcjonalnie, widoczny na fakturach)</span></label>
                            <input v-model="form.restaurant_nip" type="text" maxlength="20" placeholder="0000000000" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Opis restauracji</label>
                        <textarea v-model="form.restaurant_description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Google Place ID</label>
                        <input v-model="form.google_place_id" type="text" placeholder="np. ChIJ..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        <p class="mt-1 text-xs text-gray-500">
                            Identyfikator wizytówki Google. Opinie z Google pojawią się automatycznie na stronie restauracji.
                            <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" class="text-blue-600 hover:underline">Jak znaleźć Place ID?</a>
                        </p>
                    </div>
                </div>

                <!-- Appearance Tab -->
                <div v-show="activeTab === 'appearance'" class="space-y-6">
                    <div class="bg-white shadow rounded-lg p-6 space-y-6">
                        <h2 class="text-lg font-semibold text-gray-900">Wygląd strony</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Logo restauracji</label>
                                <ImageUpload v-model="form.logo_url" field="logo_url" hint="Logo wyświetlane w nagłówku i stopce" preview-class="h-20 max-w-[200px] object-contain mx-auto" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                                <ImageUpload v-model="form.favicon_url" field="favicon_url" hint="Ikona w zakładce przeglądarki (32x32px)" preview-class="h-12 w-12 object-contain mx-auto" />
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-sm font-semibold text-gray-700 mb-4">Sekcja Hero (baner główny)</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Zdjęcie hero (tło baneru)</label>
                                <ImageUpload v-model="form.hero_image_url" field="hero_image_url" hint="Wgraj plik lub wpisz URL poniżej (zalecane min. 1920x800px)" preview-class="w-full h-56 object-cover rounded-lg" />
                                <div class="mt-2">
                                    <input
                                        v-model="form.hero_image_url"
                                        type="url"
                                        placeholder="lub wklej URL zdjęcia: https://..."
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tytuł na hero</label>
                                    <input v-model="form.hero_title" type="text" placeholder="domyślnie nazwa restauracji" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Podtytuł na hero</label>
                                    <input v-model="form.hero_subtitle" type="text" placeholder="domyślnie opis restauracji" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Level B: Theme colors & font -->
                    <div class="bg-white shadow rounded-lg p-6 space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Kolory i czcionka</h2>
                            <p class="text-sm text-gray-500 mt-1">Dostosuj główny kolor i typografię witryny klienta</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Główny kolor akcentu</label>
                                <div class="flex items-center gap-3">
                                    <input
                                        v-model="form.theme_primary_color"
                                        type="color"
                                        class="h-10 w-16 rounded cursor-pointer border border-gray-300 p-0.5 bg-white"
                                    />
                                    <input
                                        v-model="form.theme_primary_color"
                                        type="text"
                                        maxlength="7"
                                        placeholder="#b91c1c"
                                        class="w-28 px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                    <div class="h-8 w-8 rounded-full border border-gray-300 shadow-sm" :style="{ backgroundColor: form.theme_primary_color }"></div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Używany w nawigacji, przyciskach, ikonach i akcentach</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Czcionka witryny</label>
                                <select v-model="form.theme_font" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="inter">Inter – nowoczesna, czytelna</option>
                                    <option value="roboto">Roboto – klasyczna, neutralna</option>
                                    <option value="merriweather">Merriweather – elegancka (serif)</option>
                                    <option value="playfair">Playfair Display – luksusowa (serif)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Zmiana czcionki dotyczy całej witryny klienta</p>
                            </div>
                        </div>

                        <!-- Live preview -->
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <p class="text-xs text-gray-500 mb-2 font-medium">Podgląd:</p>
                            <div class="flex items-center gap-4 flex-wrap">
                                <span class="text-2xl font-bold" :style="{ color: form.theme_primary_color }">Restauracja</span>
                                <button type="button" class="px-4 py-2 text-white text-sm rounded-lg font-medium" :style="{ backgroundColor: form.theme_primary_color }">Zamów teraz</button>
                                <span class="text-sm text-gray-600">Tekst zwykły</span>
                            </div>
                        </div>
                    </div>

                    <!-- Level A: Custom CSS -->
                    <div class="bg-white shadow rounded-lg p-6 space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Własny CSS</h2>
                            <p class="text-sm text-gray-500 mt-1">Zaawansowane – CSS zostanie załadowany na każdej stronie klienta</p>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-700">
                            Niepoprawny CSS może zepsuć wygląd witryny. Testuj zmiany ostrożnie.
                            Możesz używać zmiennej <code class="font-mono bg-amber-100 px-1 rounded">var(--color-primary)</code> jako główny kolor.
                        </div>

                        <textarea
                            v-model="form.custom_css"
                            rows="12"
                            placeholder="/* Własne style CSS */&#10;&#10;/* Przykład: zmień kolor przycisku koszyka */&#10;.cart-btn { border-radius: 0 !important; }&#10;&#10;/* Przykład: dodaj własny nagłówek */&#10;.hero-section { min-height: 90vh; }"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50"
                            spellcheck="false"
                        ></textarea>
                    </div>
                </div>

                <!-- Modules Tab -->
                <div v-show="activeTab === 'modules'" class="space-y-6">
                    <!-- About Us -->
                    <div class="bg-white shadow rounded-lg p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Sekcja "O nas"</h2>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.about_enabled" class="h-4 w-4 text-blue-600 rounded" />
                                <span class="ml-2 text-sm text-gray-700">Włączona</span>
                            </label>
                        </div>
                        <div v-if="form.about_enabled" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tytuł sekcji</label>
                                <input v-model="form.about_title" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Treść</label>
                                <textarea v-model="form.about_text" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Opowiedz historię swojej restauracji..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Zdjęcie</label>
                                <ImageUpload v-model="form.about_image_url" field="about_image_url" preview-class="w-full h-32 object-cover" />
                            </div>
                        </div>
                    </div>

                    <!-- Gallery -->
                    <div class="bg-white shadow rounded-lg p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Galeria zdjęć</h2>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.gallery_enabled" class="h-4 w-4 text-blue-600 rounded" />
                                <span class="ml-2 text-sm text-gray-700">Włączona</span>
                            </label>
                        </div>
                        <div v-if="form.gallery_enabled" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tytuł sekcji</label>
                                <input v-model="form.gallery_title" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Zdjęcia galerii</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                                    <div v-for="(img, index) in galleryList.filter(Boolean)" :key="'gal-' + index" class="relative group rounded-lg overflow-hidden bg-gray-100">
                                        <img :src="img" alt="" class="w-full h-24 object-cover" />
                                        <button
                                            type="button"
                                            @click="removeGalleryItem(galleryList.indexOf(img))"
                                            class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm opacity-0 group-hover:opacity-100 transition-opacity"
                                        >&times;</button>
                                    </div>
                                    <!-- Upload slot -->
                                    <label class="flex flex-col items-center justify-center h-24 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                                        <i class="fa-solid fa-plus text-gray-400 text-xl mb-1"></i>
                                        <span class="text-xs text-gray-500">Dodaj zdjęcie</span>
                                        <input type="file" accept="image/*" class="hidden" @change="uploadGalleryImage" :disabled="galleryUploading" />
                                    </label>
                                </div>
                                <p v-if="galleryUploading" class="text-sm text-blue-600">Przesyłanie...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Reservations -->
                    <div class="bg-white shadow rounded-lg p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Rezerwacje stolików</h2>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.reservations_enabled" class="h-4 w-4 text-blue-600 rounded" />
                                <span class="ml-2 text-sm text-gray-700">Włączone</span>
                            </label>
                        </div>
                        <p v-if="form.reservations_enabled" class="text-xs text-gray-500 -mt-1">
                            Maks. liczba osób jest ustawiana na poziomie każdego stolika (Stoliki i Rezerwacje → Min./Maks. gości).
                        </p>
                        <div v-if="form.reservations_enabled" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Wyprzedzenie (dni)</label>
                                <input v-model="form.reservations_advance_days" type="number" min="1" max="90" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                <p class="mt-1 text-xs text-gray-500">Na ile dni wprzód można rezerwować</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Czas rezerwacji (min)</label>
                                <input v-model="form.reservations_slot_duration" type="number" min="30" max="300" step="30" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                        </div>
                    </div>

                    <!-- Level C: Homepage Blocks editor -->
                    <div class="bg-white shadow rounded-lg p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Bloki strony głównej</h2>
                                <p class="text-xs text-gray-500 mt-0.5">Własne sekcje wyświetlane na stronie klienta (pod hero)</p>
                            </div>
                            <button
                                type="button"
                                @click="addBlock"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium border border-blue-200 rounded px-3 py-1.5 hover:bg-blue-50 transition-colors"
                            >
                                <i class="fa-solid fa-plus mr-1 text-blue-500"></i> Dodaj blok
                            </button>
                        </div>

                        <div v-if="form.homepage_blocks.length === 0" class="text-center py-10 text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-lg">
                            Brak bloków. Kliknij "Dodaj blok" aby stworzyć pierwszą sekcję na stronie głównej.
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="(block, index) in form.homepage_blocks"
                                :key="block.id"
                                class="border rounded-lg p-4 space-y-3"
                                :class="block.enabled ? 'border-gray-200 bg-white' : 'border-gray-100 bg-gray-50 opacity-60'"
                            >
                                <!-- Block header -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox" v-model="block.enabled" class="h-4 w-4 text-blue-600 rounded" />
                                            <span class="ml-2 text-sm font-semibold text-gray-800">{{ blockTypeLabel(block.type) }}</span>
                                        </label>
                                        <span v-if="block.title" class="text-xs text-gray-400">– {{ block.title }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="moveBlockUp(index)" :disabled="index === 0" class="p-1 text-gray-400 hover:text-gray-700 disabled:opacity-25 text-xs" title="Przesuń wyżej"><i class="fa-solid fa-chevron-up"></i></button>
                                        <button type="button" @click="moveBlockDown(index)" :disabled="index === form.homepage_blocks.length - 1" class="p-1 text-gray-400 hover:text-gray-700 disabled:opacity-25 text-xs" title="Przesuń niżej"><i class="fa-solid fa-chevron-down"></i></button>
                                        <button type="button" @click="removeBlock(index)" class="p-1 text-red-400 hover:text-red-600 text-sm ml-1" title="Usuń"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                </div>

                                <!-- Block type selector -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Typ bloku</label>
                                    <select v-model="block.type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="announcement">Ogłoszenie – kolorowy baner z tekstem</option>
                                        <option value="promo_text">Sekcja tekstowa – tytuł i akapit</option>
                                        <option value="cta">Przycisk CTA – wezwanie do działania</option>
                                    </select>
                                </div>

                                <!-- Title -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tytuł</label>
                                    <input
                                        v-model="block.title"
                                        type="text"
                                        placeholder="np. Promocja tygodnia!"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>

                                <!-- Content (for announcement + promo_text) -->
                                <div v-if="block.type !== 'cta'">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Treść</label>
                                    <textarea
                                        v-model="block.content"
                                        rows="2"
                                        placeholder="Treść sekcji..."
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    ></textarea>
                                </div>

                                <!-- Bg color (announcement only) -->
                                <div v-if="block.type === 'announcement'" class="flex items-center gap-3">
                                    <label class="text-xs font-medium text-gray-600">Kolor tła:</label>
                                    <input v-model="block.bg_color" type="color" class="h-8 w-14 rounded cursor-pointer border border-gray-300 p-0.5" />
                                    <input v-model="block.bg_color" type="text" maxlength="7" class="w-24 px-2 py-1 border border-gray-300 rounded text-xs font-mono" />
                                </div>

                                <!-- CTA fields -->
                                <div v-if="block.type === 'cta'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Tekst przycisku</label>
                                        <input v-model="block.link_text" type="text" placeholder="np. Zamów teraz" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">URL docelowy</label>
                                        <input v-model="block.link_url" type="text" placeholder="np. /menu lub https://..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hours Tab -->
                <div v-show="activeTab === 'hours'" class="bg-white shadow rounded-lg p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Godziny otwarcia</h2>

                    <div v-for="(dayData, dayKey) in form.opening_hours" :key="dayKey" class="flex items-center space-x-4 py-3 border-b border-gray-100 last:border-0">
                        <div class="w-32">
                            <label class="flex items-center">
                                <input type="checkbox" v-model="dayData.enabled" class="h-4 w-4 text-blue-600 rounded" />
                                <span class="ml-2 text-sm font-medium text-gray-700">{{ dayNames[dayKey] }}</span>
                            </label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input
                                v-model="dayData.open"
                                type="time"
                                :disabled="!dayData.enabled"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-100 disabled:text-gray-400"
                            />
                            <span class="text-gray-500">-</span>
                            <input
                                v-model="dayData.close"
                                type="time"
                                :disabled="!dayData.enabled"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-100 disabled:text-gray-400"
                            />
                        </div>
                    </div>
                </div>

                <!-- Orders Tab -->
                <div v-show="activeTab === 'orders'" class="bg-white shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900">Zamówienia</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Minimalna wartość zamówienia (PLN)</label>
                            <input v-model="form.min_order_value" type="number" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Szacowany czas przygotowania (min)</label>
                            <input v-model="form.estimated_preparation_time" type="number" min="5" max="120" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700">Typy zamówień</h3>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.delivery_enabled" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="ml-2 text-sm text-gray-700">Dostawa</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.pickup_enabled" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="ml-2 text-sm text-gray-700">Odbiór osobisty</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.dine_in_enabled" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="ml-2 text-sm text-gray-700">Na miejscu (stolik)</span>
                        </label>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.order_auto_accept" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="ml-2 text-sm text-gray-700">Automatyczne przyjmowanie zamówień</span>
                        </label>
                        <p class="ml-6 text-xs text-gray-500 mt-1">Zamówienia po opłaceniu zostaną automatycznie przekazane do kuchni</p>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.driver_tracking_enabled" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="ml-2 text-sm text-gray-700">Śledzenie GPS kierowcy</span>
                        </label>
                        <p class="ml-6 text-xs text-gray-500 mt-1">Klient może śledzić pozycję kierowcy na mapie podczas dostawy</p>
                    </div>
                </div>

                <!-- Payments Tab -->
                <div v-show="activeTab === 'payments'" class="bg-white shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900">Bramki płatności</h2>
                    <p class="text-sm text-gray-500">Włącz i skonfiguruj bramki płatności. Klienci zobaczą tylko te, które mają uzupełnione dane.</p>

                    <!-- Płatności lokalne -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Przy odbiorze</h3>
                        <label class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" v-model="form.payment_cash_enabled" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="text-sm font-medium"><i class="fa-solid fa-money-bill-wave mr-1 text-green-600"></i> Gotówka przy odbiorze</span>
                        </label>
                        <label class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" v-model="form.payment_card_on_delivery_enabled" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="text-sm font-medium"><i class="fa-solid fa-credit-card mr-1 text-blue-600"></i> Karta przy odbiorze</span>
                        </label>
                    </div>

                    <!-- Bramki online (dostępne tylko w wersji testowej) -->
                    <template v-if="$page.props.app_version === 'test'">

                    <!-- Przelewy24 -->
                    <div class="border rounded-xl overflow-hidden">
                        <div class="flex items-center justify-between p-4 bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-xl font-bold text-red-600">P24</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Przelewy24</p>
                                    <p class="text-xs text-gray-500">Przelew, BLIK, karta – ponad 200 metod</p>
                                </div>
                            </div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.payment_p24_enabled" class="h-4 w-4 text-blue-600 rounded" />
                                <span class="text-sm text-gray-700">Aktywne</span>
                            </label>
                        </div>
                        <div v-if="form.payment_p24_enabled" class="p-4 space-y-4 border-t">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Merchant ID</label>
                                    <input v-model="form.p24_merchant_id" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">POS ID</label>
                                    <input v-model="form.p24_pos_id" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">API Key</label>
                                    <input v-model="form.p24_api_key" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">CRC Key</label>
                                    <input v-model="form.p24_crc" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                            </div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.p24_sandbox" class="h-4 w-4 text-blue-600 rounded" />
                                <span class="text-sm text-gray-700">Tryb testowy (sandbox)</span>
                            </label>
                        </div>
                    </div>

                    <!-- PayU -->
                    <div class="border rounded-xl overflow-hidden">
                        <div class="flex items-center justify-between p-4 bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-xl font-bold text-[#00b3e3]">PayU</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">PayU</p>
                                    <p class="text-xs text-gray-500">Szybki przelew, BLIK, karta</p>
                                </div>
                            </div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.payment_payu_enabled" class="h-4 w-4 text-blue-600 rounded" />
                                <span class="text-sm text-gray-700">Aktywne</span>
                            </label>
                        </div>
                        <div v-if="form.payment_payu_enabled" class="p-4 space-y-4 border-t">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">POS ID</label>
                                    <input v-model="form.payu_pos_id" type="text" placeholder="np. 300746" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">MD5 Signature Key</label>
                                    <input v-model="form.payu_signature_key" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">OAuth Client ID</label>
                                    <input v-model="form.payu_client_id" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">OAuth Client Secret</label>
                                    <input v-model="form.payu_client_secret" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                            </div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" :true-value="'sandbox'" :false-value="'production'" v-model="form.payu_mode" class="h-4 w-4 text-blue-600 rounded" />
                                <span class="text-sm text-gray-700">Tryb testowy (sandbox)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Tpay -->
                    <div class="border rounded-xl overflow-hidden">
                        <div class="flex items-center justify-between p-4 bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-xl font-bold text-[#3d9bff]">Tpay</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Tpay</p>
                                    <p class="text-xs text-gray-500">Szybkie przelewy i BLIK</p>
                                </div>
                            </div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.payment_tpay_enabled" class="h-4 w-4 text-blue-600 rounded" />
                                <span class="text-sm text-gray-700">Aktywne</span>
                            </label>
                        </div>
                        <div v-if="form.payment_tpay_enabled" class="p-4 space-y-4 border-t">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Client ID</label>
                                    <input v-model="form.tpay_client_id" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Client Secret</label>
                                    <input v-model="form.tpay_client_secret" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                            </div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" :true-value="'sandbox'" :false-value="'production'" v-model="form.tpay_mode" class="h-4 w-4 text-blue-600 rounded" />
                                <span class="text-sm text-gray-700">Tryb testowy (sandbox)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Stripe -->
                    <div class="border rounded-xl overflow-hidden">
                        <div class="flex items-center justify-between p-4 bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-xl font-bold text-[#635bff]">Stripe</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Stripe</p>
                                    <p class="text-xs text-gray-500">Karta kredytowa / debetowa (globalnie)</p>
                                </div>
                            </div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.payment_stripe_enabled" class="h-4 w-4 text-blue-600 rounded" />
                                <span class="text-sm text-gray-700">Aktywne</span>
                            </label>
                        </div>
                        <div v-if="form.payment_stripe_enabled" class="p-4 space-y-4 border-t">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Publishable Key (pk_...)</label>
                                    <input v-model="form.stripe_public_key" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Secret Key (sk_...)</label>
                                    <input v-model="form.stripe_secret_key" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Webhook Secret (whsec_...)</label>
                                    <input v-model="form.stripe_webhook_secret" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                    <p class="text-xs text-gray-500 mt-1">URL webhooków: <code>{{ $page.props.ziggy?.url }}/payment/webhook/stripe</code></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    </template>

                    <!-- Komunikat dla wersji stabilnej – tylko dla super admina -->
                    <div v-else-if="$page.props.impersonating" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <p class="font-semibold mb-1">Bramki płatności online niedostępne</p>
                        <p>Integracje z P24, PayU, Tpay i Stripe są dostępne wyłącznie w wersji <strong>testowej</strong>. Aby je włączyć, ustaw <code class="bg-amber-100 px-1 rounded">APP_VERSION=test</code> w pliku <code class="bg-amber-100 px-1 rounded">.env</code>.</p>
                    </div>
                </div>

                <!-- Printer Tab -->
                <div v-show="activeTab === 'printer'" class="bg-white shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900">Drukarka termiczna</h2>

                    <template v-if="$page.props.app_version === 'test'">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.printer_enabled" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="ml-2 text-sm text-gray-700">Drukowanie bonów włączone</span>
                        </label>

                        <div v-if="form.printer_enabled" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Typ drukarki</label>
                                <select v-model="form.printer_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="network">Sieciowa (IP)</option>
                                    <option value="bluetooth">Bluetooth</option>
                                    <option value="usb">USB</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Adres drukarki</label>
                                <input v-model="form.printer_address" type="text" placeholder="np. 192.168.1.100:9100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                        </div>
                    </template>
                    <div v-else-if="$page.props.impersonating" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <p class="font-semibold mb-1">Drukarka termiczna niedostępna</p>
                        <p>Integracja z drukarką jest dostępna wyłącznie w <strong>wersji testowej</strong>. Administrator systemu może zmienić wersję w panelu zarządzania.</p>
                    </div>
                </div>

                <!-- Notifications Tab -->
                <div v-show="activeTab === 'notifications'" class="bg-white shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900">Powiadomienia</h2>

                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.notification_sound_enabled" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="ml-2 text-sm text-gray-700">Dźwięk powiadomień o nowych zamówieniach</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.notification_email_enabled" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="ml-2 text-sm text-gray-700">Powiadomienia e-mail o nowych zamówieniach</span>
                        </label>
                    </div>

                    <div v-if="form.notification_email_enabled">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adres e-mail do powiadomień</label>
                        <input v-model="form.notification_email_address" type="email" class="w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                    </div>
                </div>

                <!-- SMS Tab -->
                <div v-show="activeTab === 'sms'" class="bg-white shadow rounded-lg p-6 space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Powiadomienia SMS</h2>
                        <p class="text-sm text-gray-500 mt-1">Integracja z SMSAPI.pl – klienci otrzymują SMS przy zmianie statusu zamówienia</p>
                    </div>

                    <template v-if="$page.props.app_version === 'test'">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.sms_enabled" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="ml-2 text-sm text-gray-700">Powiadomienia SMS włączone</span>
                        </label>

                        <div v-if="form.sms_enabled" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Token API SMSAPI.pl (OAuth2)</label>
                                <input
                                    v-model="form.smsapi_token"
                                    type="password"
                                    placeholder="Wklej tutaj token Bearer z panelu SMSAPI"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Token znajdziesz w panelu SMSAPI &rarr; API &rarr; OAuth2 &rarr; Wygeneruj token.
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nazwa nadawcy (maks. 11 znaków)</label>
                                <input
                                    v-model="form.sms_sender_name"
                                    type="text"
                                    maxlength="11"
                                    placeholder="np. Restauracja"
                                    class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p class="mt-1 text-xs text-gray-500">Tylko litery i cyfry, bez spacji. Musi być zarejestrowana w SMSAPI.</p>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-700">
                                <strong>SMS są wysyłane gdy:</strong>
                                <ul class="mt-1 list-disc list-inside space-y-1 text-xs">
                                    <li>Zamówienie jest opłacone (paid)</li>
                                    <li>Trwa przygotowanie (preparing)</li>
                                    <li>Zamówienie gotowe do odbioru (ready)</li>
                                    <li>Zamówienie w dostawie (on_delivery)</li>
                                    <li>Zamówienie zrealizowane (completed)</li>
                                    <li>Zamówienie anulowane (cancelled)</li>
                                </ul>
                            </div>
                        </div>
                    </template>
                    <div v-else-if="$page.props.impersonating" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <p class="font-semibold mb-1">Powiadomienia SMS niedostępne</p>
                        <p>Integracja z SMSAPI.pl jest dostępna wyłącznie w <strong>wersji testowej</strong>. Administrator systemu może zmienić wersję w panelu zarządzania.</p>
                    </div>
                </div>

                <!-- Integrations Tab -->
                <div v-show="activeTab === 'integrations'" class="space-y-6">

                    <!-- Social Media -->
                    <div class="bg-white shadow rounded-lg p-6 space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Media społecznościowe</h2>
                            <p class="text-sm text-gray-500 mt-1">Linki pojawią się jako ikony w stopce strony klienta</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fa-brands fa-facebook text-blue-600 mr-1"></i> Facebook
                                </label>
                                <input v-model="form.facebook_url" type="url" placeholder="https://facebook.com/twoja-restauracja" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fa-brands fa-instagram text-pink-500 mr-1"></i> Instagram
                                </label>
                                <input v-model="form.instagram_url" type="url" placeholder="https://instagram.com/twoja-restauracja" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fa-brands fa-tiktok mr-1 text-gray-700"></i> TikTok
                                </label>
                                <input v-model="form.tiktok_url" type="url" placeholder="https://tiktok.com/@twoja-restauracja" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                        </div>
                    </div>

                    <!-- Google Analytics -->
                    <div class="bg-white shadow rounded-lg p-6 space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Google Analytics (GA4)</h2>
                            <p class="text-sm text-gray-500 mt-1">Śledź ruch i zachowanie użytkowników na swojej stronie</p>
                        </div>

                        <div class="max-w-md">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Measurement ID</label>
                            <input
                                v-model="form.google_analytics_id"
                                type="text"
                                placeholder="np. G-XXXXXXXXXX"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Znajdziesz go w Google Analytics → Admin → Strumień danych → Measurement ID (format: <code class="bg-gray-100 px-1 rounded">G-XXXXXXXXXX</code>)
                            </p>
                        </div>
                    </div>

                    <!-- Facebook Pixel -->
                    <div class="bg-white shadow rounded-lg p-6 space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Facebook Pixel</h2>
                            <p class="text-sm text-gray-500 mt-1">Mierzenie skuteczności reklam Meta (Facebook, Instagram)</p>
                        </div>

                        <div class="max-w-md">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pixel ID</label>
                            <input
                                v-model="form.facebook_pixel_id"
                                type="text"
                                placeholder="np. 123456789012345"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Znajdziesz go w Menedżerze reklam Meta → Źródła danych → Pixele (format: 15-cyfrowy numer)
                            </p>
                        </div>
                    </div>
                </div>


                <!-- Loyalty Tab (v2) -->
                <div v-show="activeTab === 'loyalty'" class="space-y-6">
                    <div class="bg-white shadow rounded-lg p-6 space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Program lojalnościowy</h2>
                            <p class="text-sm text-gray-500 mt-1">Klienci zbierają punkty, awansują w tierach i wymieniają nagrody</p>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.loyalty_enabled" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="text-sm font-medium text-gray-700">Program lojalnościowy włączony</span>
                        </label>
                    </div>

                    <div v-if="form.loyalty_enabled" class="space-y-6">
                        <!-- Earn mode -->
                        <div class="bg-white shadow rounded-lg p-6 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Sposób naliczania punktów</h3>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="form.loyalty_earn_mode" value="per_pln" class="h-4 w-4 text-blue-600" />
                                    <span class="text-sm text-gray-700">Za każde 1 PLN zamówienia</span>
                                </label>
                                <div v-if="form.loyalty_earn_mode === 'per_pln'" class="ml-6">
                                    <label class="text-xs text-gray-500 block mb-1">Punktów za 1 PLN</label>
                                    <input v-model="form.loyalty_points_per_pln" type="number" min="1" max="100" class="w-24 px-3 py-1.5 border border-gray-300 rounded text-sm" />
                                </div>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="form.loyalty_earn_mode" value="per_order" class="h-4 w-4 text-blue-600" />
                                    <span class="text-sm text-gray-700">Stała liczba punktów za zamówienie</span>
                                </label>
                                <div v-if="form.loyalty_earn_mode === 'per_order'" class="ml-6">
                                    <label class="text-xs text-gray-500 block mb-1">Punktów za zamówienie</label>
                                    <input v-model="form.loyalty_points_per_order" type="number" min="1" class="w-24 px-3 py-1.5 border border-gray-300 rounded text-sm" />
                                </div>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="form.loyalty_earn_mode" value="tiered" class="h-4 w-4 text-blue-600" />
                                    <span class="text-sm text-gray-700">Progowy (im wyższa kwota, tym więcej pkt/PLN)</span>
                                </label>
                                <div v-if="form.loyalty_earn_mode === 'tiered'" class="ml-6 p-3 bg-gray-50 rounded-lg text-xs text-gray-500">
                                    &lt;50 PLN → 1 pkt/PLN · 50–100 PLN → 1.5 pkt/PLN · &gt;100 PLN → 2 pkt/PLN
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Podstawa awansu w tierach</label>
                                <select v-model="form.loyalty_tier_basis" class="w-64 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="ever_earned">Łączne punkty kiedykolwiek zarobione</option>
                                    <option value="current_balance">Bieżące saldo punktów</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tiers config -->
                        <div class="bg-white shadow rounded-lg p-6 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Poziomy (tiery)</h3>
                            <p class="text-xs text-gray-500">Progi punktów oraz premie dla każdego poziomu</p>
                            <div class="space-y-3">
                                <div v-for="tier in loyaltyTiersV2" :key="tier.key" class="border border-gray-200 rounded-lg p-3">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="inline-block w-3 h-3 rounded-full" :style="{ background: tier.color }"></span>
                                        <span class="font-semibold text-sm">{{ tier.name }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <div>
                                            <label class="text-xs text-gray-500 block mb-1">Min. punktów</label>
                                            <input v-model="tier.min" type="number" min="0" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" :disabled="tier.key === 'bronze'" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500 block mb-1">Mnożnik punktów</label>
                                            <input v-model="tier.multiplier" type="number" min="1" max="10" step="0.25" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500 block mb-1">Bonus miesięczny (pkt)</label>
                                            <input v-model="tier.monthly_bonus" type="number" min="0" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500 block mb-1">Bonus na dostawę (PLN / 999=gratis)</label>
                                            <input v-model="tier.delivery_bonus" type="number" min="0" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bonuses -->
                        <div class="bg-white shadow rounded-lg p-6 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Bonusy jednorazowe</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm text-gray-700 block mb-1">Za rejestrację (pkt)</label>
                                    <input v-model="form.loyalty_bonus_registration" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                </div>
                                <div>
                                    <label class="text-sm text-gray-700 block mb-1">Za pierwsze zamówienie (pkt)</label>
                                    <input v-model="form.loyalty_bonus_first_order" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                </div>
                                <div>
                                    <label class="text-sm text-gray-700 block mb-1">Za polecenie nowego klienta (pkt)</label>
                                    <input v-model="form.loyalty_bonus_referral" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                </div>
                                <div>
                                    <label class="text-sm text-gray-700 block mb-1">Bonus urodzinowy (pkt)</label>
                                    <input v-model="form.loyalty_bonus_birthday" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                </div>
                                <div>
                                    <label class="text-sm text-gray-700 block mb-1">Mnożnik w miesiącu urodzin (×)</label>
                                    <input v-model="form.loyalty_bonus_birthday_multiplier" type="number" min="1" max="10" step="0.5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                </div>
                            </div>
                        </div>

                        <!-- Expiry -->
                        <div class="bg-white shadow rounded-lg p-6 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Wygasanie punktów</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm text-gray-700 block mb-1">Punkty wygasają po (dni, 0 = nigdy)</label>
                                    <input v-model="form.loyalty_points_expiry_days" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                </div>
                                <div>
                                    <label class="text-sm text-gray-700 block mb-1">Powiadomienie (dni przed wygaśnięciem)</label>
                                    <input v-model="form.loyalty_expiry_warning_days" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Uruchom <code>php artisan loyalty:expire-points</code> jako zadanie cron (raz dziennie)</p>
                        </div>
                    </div>
                </div>

                <!-- Terms/Privacy Tab (#14) -->
                <div v-show="activeTab === 'terms'" class="space-y-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                        <strong>Dostępne tokeny</strong> — zostaną automatycznie zastąpione danymi restauracji:<br>
                        <code class="bg-blue-100 px-1 rounded">{restaurant_name}</code>
                        <code class="bg-blue-100 px-1 rounded ml-1">{restaurant_owner_name}</code>
                        <code class="bg-blue-100 px-1 rounded ml-1">{restaurant_address}</code>
                        <code class="bg-blue-100 px-1 rounded ml-1">{restaurant_phone}</code>
                        <code class="bg-blue-100 px-1 rounded ml-1">{restaurant_email}</code>
                        <code class="bg-blue-100 px-1 rounded ml-1">{restaurant_nip}</code>
                        <code class="bg-blue-100 px-1 rounded ml-1">{year}</code>
                    </div>
                    <div class="bg-white shadow rounded-lg p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Regulamin</h2>
                        <p class="text-sm text-gray-500">Treść widoczna na stronie <strong>/regulamin</strong>. Dozwolony HTML (h2, h3, p, ul, ol, li).</p>
                        <textarea v-model="form.terms_content" rows="18" class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="bg-white shadow rounded-lg p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Polityka prywatności</h2>
                        <p class="text-sm text-gray-500">Treść widoczna na stronie <strong>/polityka-prywatnosci</strong>. Dozwolony HTML.</p>
                        <textarea v-model="form.privacy_content" rows="18" class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>

                <!-- Vacation / Closure Tab (#34) -->
                <div v-show="activeTab === 'vacation'" class="bg-white shadow rounded-lg p-6 space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Tryb urlopowy / zamknięcie</h2>
                        <p class="text-sm text-gray-500 mt-1">Tymczasowo wstrzymaj przyjmowanie zamówień z komunikatem dla klientów</p>
                    </div>

                    <div class="flex items-start gap-3 p-4 rounded-lg" :class="form.vacation_mode ? 'bg-orange-50 border border-orange-200' : 'bg-gray-50 border border-gray-200'">
                        <input type="checkbox" v-model="form.vacation_mode" id="vacation_mode" name="vacation_mode" class="h-5 w-5 text-orange-500 rounded mt-0.5" />
                        <div>
                            <label for="vacation_mode" class="font-semibold text-gray-800 text-sm cursor-pointer">
                                Włącz tryb urlopowy
                            </label>
                            <p class="text-xs text-gray-500 mt-1">Zamówienia zostaną zablokowane i klienci zobaczą podany komunikat</p>
                        </div>
                    </div>

                    <div v-if="form.vacation_mode">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Komunikat dla klientów</label>
                        <textarea v-model="form.vacation_message" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400" placeholder="np. Jesteśmy na urlopie do 10 marca. Do zobaczenia!"></textarea>
                    </div>

                    <!-- Specific closure days (#31) -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Konkretne dni zamknięcia</h3>
                        <p class="text-sm text-gray-500 mb-3">Ustaw konkretne daty (np. święta), kiedy restauracja jest zamknięta bez włączania trybu urlopowego</p>
                        <div class="flex items-center gap-2 mb-3">
                            <input type="date" v-model="newClosedDay" class="px-3 py-2 border border-gray-300 rounded-lg text-sm" :min="today" />
                            <button type="button" @click="addClosedDay" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-medium">Dodaj dzień</button>
                        </div>
                        <div v-if="form.closed_days && form.closed_days.length" class="flex flex-wrap gap-2">
                            <span v-for="day in form.closed_days" :key="day" class="inline-flex items-center gap-1 bg-orange-100 text-orange-800 text-sm px-3 py-1 rounded-full">
                                {{ day }}
                                <button type="button" @click="removeClosedDay(day)" class="ml-1 text-orange-600 hover:text-orange-800 font-bold">&times;</button>
                            </span>
                        </div>
                        <p v-else class="text-sm text-gray-400 italic">Brak zaplanowanych dni zamknięcia</p>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="mt-6 flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Zapisywanie...' : 'Zapisz ustawienia' }}
                    </button>
                </div>
            </form>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import ImageUpload from '@/Components/ImageUpload.vue'
import { formatPhone } from '@/utils/phone'

const props = defineProps({
    settings: Object,
})

const page = usePage()
const isTestVersion = computed(() => page.props.app_version === 'test')

const activeTab = ref('general')

const allTabs = [
    { id: 'general', label: 'Ogólne' },
    { id: 'appearance', label: 'Wygląd' },
    { id: 'modules', label: 'Moduły' },
    { id: 'hours', label: 'Godziny otwarcia' },
    { id: 'orders', label: 'Zamówienia' },
    { id: 'payments', label: 'Płatności' },
    { id: 'printer', label: 'Drukarka', testOnly: true },
    { id: 'notifications', label: 'Powiadomienia' },
    { id: 'sms', label: 'SMS', testOnly: true },
    { id: 'loyalty', label: 'Lojalność' },
    { id: 'terms', label: 'Regulaminy' },
    { id: 'vacation', label: 'Urlop/Zamknięcie' },
    { id: 'integrations', label: 'Integracje' },
]

const tabs = computed(() => allTabs.filter(t => !t.testOnly || isTestVersion.value))

const dayNames = {
    monday: 'Poniedziałek',
    tuesday: 'Wtorek',
    wednesday: 'Środa',
    thursday: 'Czwartek',
    friday: 'Piątek',
    saturday: 'Sobota',
    sunday: 'Niedziela',
}

// Parse gallery_images from settings
const initGallery = () => {
    const raw = props.settings?.gallery_images
    if (!raw) return []
    if (Array.isArray(raw)) return raw.filter(Boolean)
    if (typeof raw === 'string') {
        try {
            const parsed = JSON.parse(raw)
            return Array.isArray(parsed) ? parsed.filter(Boolean) : []
        } catch {
            return []
        }
    }
    return []
}

// Parse homepage_blocks from settings
const initBlocks = () => {
    const raw = props.settings?.homepage_blocks
    if (!raw) return []
    if (Array.isArray(raw)) return raw
    if (typeof raw === 'string') {
        try { return JSON.parse(raw) } catch { return [] }
    }
    return []
}

const galleryList = reactive(initGallery())

const form = useForm({
    ...props.settings,
    gallery_images: galleryList.filter(Boolean),
    homepage_blocks: initBlocks(),
    theme_primary_color: props.settings?.theme_primary_color || '#b91c1c',
    theme_font: props.settings?.theme_font || 'inter',
    custom_css: props.settings?.custom_css || '',
    // Social media
    facebook_url: props.settings?.facebook_url || '',
    instagram_url: props.settings?.instagram_url || '',
    tiktok_url: props.settings?.tiktok_url || '',
    // Analytics
    google_analytics_id: props.settings?.google_analytics_id || '',
    facebook_pixel_id: props.settings?.facebook_pixel_id || '',
    // Payment gateways
    payment_p24_enabled: props.settings?.payment_p24_enabled ?? props.settings?.payment_online_enabled ?? false,
    p24_api_key: props.settings?.p24_api_key || '',
    payment_payu_enabled: props.settings?.payment_payu_enabled ?? false,
    payu_pos_id: props.settings?.payu_pos_id || '',
    payu_signature_key: props.settings?.payu_signature_key || '',
    payu_client_id: props.settings?.payu_client_id || '',
    payu_client_secret: props.settings?.payu_client_secret || '',
    payu_mode: props.settings?.payu_mode || 'sandbox',
    payment_tpay_enabled: props.settings?.payment_tpay_enabled ?? false,
    tpay_client_id: props.settings?.tpay_client_id || '',
    tpay_client_secret: props.settings?.tpay_client_secret || '',
    tpay_mode: props.settings?.tpay_mode || 'sandbox',
    payment_stripe_enabled: props.settings?.payment_stripe_enabled ?? false,
    stripe_public_key: props.settings?.stripe_public_key || '',
    stripe_secret_key: props.settings?.stripe_secret_key || '',
    stripe_webhook_secret: props.settings?.stripe_webhook_secret || '',
    // Loyalty v2
    loyalty_enabled: props.settings?.loyalty_enabled ?? false,
    loyalty_earn_mode: props.settings?.loyalty_earn_mode || 'per_pln',
    loyalty_points_per_pln: props.settings?.loyalty_points_per_pln || 1,
    loyalty_points_per_order: props.settings?.loyalty_points_per_order || 10,
    loyalty_tiers: props.settings?.loyalty_tiers || '',
    loyalty_tier_basis: props.settings?.loyalty_tier_basis || 'ever_earned',
    loyalty_points_expiry_days: props.settings?.loyalty_points_expiry_days ?? 0,
    loyalty_expiry_warning_days: props.settings?.loyalty_expiry_warning_days ?? 7,
    loyalty_bonus_registration: props.settings?.loyalty_bonus_registration ?? 50,
    loyalty_bonus_first_order: props.settings?.loyalty_bonus_first_order ?? 100,
    loyalty_bonus_referral: props.settings?.loyalty_bonus_referral ?? 200,
    loyalty_bonus_birthday: props.settings?.loyalty_bonus_birthday ?? 150,
    loyalty_bonus_birthday_multiplier: props.settings?.loyalty_bonus_birthday_multiplier ?? 2.0,
    // Terms / Privacy
    terms_content: props.settings?.terms_content || '',
    privacy_content: props.settings?.privacy_content || '',
    // Vacation
    vacation_mode: props.settings?.vacation_mode ?? false,
    vacation_message: props.settings?.vacation_message || '',
    // Specific closure days (#31)
    closed_days: (() => {
        const raw = props.settings?.closed_days
        if (!raw) return []
        return typeof raw === 'string' ? JSON.parse(raw) : raw
    })(),
})

// Specific closure days (#31)
const newClosedDay = ref('')
const today = new Date().toISOString().split('T')[0]
const addClosedDay = () => {
    if (!newClosedDay.value) return
    if (!form.closed_days.includes(newClosedDay.value)) {
        form.closed_days = [...form.closed_days, newClosedDay.value].sort()
    }
    newClosedDay.value = ''
}
const removeClosedDay = (day) => {
    form.closed_days = form.closed_days.filter(d => d !== day)
}

const galleryUploading = ref(false)


// Loyalty tiers v2
const initLoyaltyTiersV2 = () => {
    const raw = props.settings?.loyalty_tiers
    const defaults = [
        { key: 'bronze',   name: 'Brąz',    color: '#cd7f32', min: 0,     multiplier: 1.0,  monthly_bonus: 0,    delivery_bonus: 0   },
        { key: 'silver',   name: 'Srebro',  color: '#9ca3af', min: 500,   multiplier: 1.25, monthly_bonus: 50,   delivery_bonus: 10  },
        { key: 'gold',     name: 'Złoto',   color: '#f59e0b', min: 1500,  multiplier: 1.5,  monthly_bonus: 150,  delivery_bonus: 20  },
        { key: 'platinum', name: 'Platyna', color: '#60a5fa', min: 4000,  multiplier: 2.0,  monthly_bonus: 400,  delivery_bonus: 999 },
        { key: 'diamond',  name: 'Diament', color: '#a78bfa', min: 10000, multiplier: 3.0,  monthly_bonus: 1000, delivery_bonus: 999 },
    ]
    const data = (typeof raw === 'object' && raw !== null) ? raw : {}
    return defaults.map(d => ({ ...d, ...data[d.key] }))
}
const loyaltyTiersV2 = reactive(initLoyaltyTiersV2())

// Legacy tiers (kept for backward compat)
const initLoyaltyTiers = () => {
    const raw = props.settings?.loyalty_tiers
    const defaults = [
        { key: 'bronze', label: 'Brąz', min_points: 0, reward: '' },
        { key: 'silver', label: 'Srebro', min_points: 100, reward: '' },
        { key: 'gold', label: 'Złoto', min_points: 300, reward: '' },
        { key: 'platinum', label: 'Platyna', min_points: 600, reward: '' },
    ]
    if (!raw) return defaults
    const data = typeof raw === 'string' ? JSON.parse(raw) : raw
    return defaults.map(d => ({ ...d, ...data[d.key] }))
}
const loyaltyTiers = reactive(initLoyaltyTiers())

const removeGalleryItem = (index) => {
    galleryList.splice(index, 1)
}

const uploadGalleryImage = async (event) => {
    const file = event.target.files[0]
    if (!file) return
    galleryUploading.value = true
    const data = new FormData()
    data.append('file', file)
    data.append('field', 'gallery')
    try {
        const res = await axios.post(route('tenant.manager.settings.upload'), data)
        galleryList.push(res.data.url)
    } finally {
        galleryUploading.value = false
        event.target.value = ''
    }
}

// --- Homepage blocks (Level C) ---
const blockTypeLabel = (type) => {
    const labels = { announcement: 'Ogłoszenie', promo_text: 'Sekcja tekstowa', cta: 'Przycisk CTA' }
    return labels[type] || type
}

const addBlock = () => {
    form.homepage_blocks.push({
        id: crypto.randomUUID(),
        type: 'announcement',
        enabled: true,
        title: '',
        content: '',
        bg_color: '#fef3c7',
        link_url: '',
        link_text: '',
    })
}

const removeBlock = (index) => form.homepage_blocks.splice(index, 1)

const moveBlockUp = (index) => {
    if (index > 0) {
        const blocks = form.homepage_blocks
        ;[blocks[index - 1], blocks[index]] = [blocks[index], blocks[index - 1]]
    }
}

const moveBlockDown = (index) => {
    if (index < form.homepage_blocks.length - 1) {
        const blocks = form.homepage_blocks
        ;[blocks[index + 1], blocks[index]] = [blocks[index], blocks[index + 1]]
    }
}


const saveSettings = () => {
    form.gallery_images = galleryList.filter(Boolean)
    // Serialize loyalty tiers v2 as keyed object
    const tiersObj = {}
    loyaltyTiersV2.forEach(t => {
        tiersObj[t.key] = {
            min: Number(t.min),
            name: t.name,
            color: t.color,
            multiplier: Number(t.multiplier),
            monthly_bonus: Number(t.monthly_bonus),
            delivery_bonus: Number(t.delivery_bonus),
        }
    })
    form.loyalty_tiers = tiersObj
    form.put(route('tenant.manager.settings.update'))
}
</script>
