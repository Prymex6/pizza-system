<template>
    <Head title="Moje konto" />

    <ClientLayout>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Moje konto</h1>

            <!-- Flash messages -->
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ $page.props.flash.success }}
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex space-x-8">
                    <button
                        @click="activeTab = 'profile'"
                        :class="[
                            'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
                            activeTab === 'profile'
                                ? 'border-red-500 text-red-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Dane osobowe
                    </button>
                    <button
                        @click="activeTab = 'orders'"
                        :class="[
                            'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
                            activeTab === 'orders'
                                ? 'border-red-500 text-red-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Historia zamówień
                    </button>
                    <button
                        @click="activeTab = 'password'"
                        :class="[
                            'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
                            activeTab === 'password'
                                ? 'border-red-500 text-red-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Zmiana hasła
                    </button>
                    <button
                        @click="activeTab = 'loyalty'"
                        :class="[
                            'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
                            activeTab === 'loyalty'
                                ? 'border-red-500 text-red-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Punkty ({{ loyalty?.points ?? 0 }})
                    </button>
                </nav>
            </div>

            <!-- Profile Tab -->
            <div v-show="activeTab === 'profile'" class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Dane osobowe</h2>
                <p class="text-sm text-gray-700 mb-1 font-medium" data-customer-name>{{ customer.name }}</p>
                <p class="text-sm text-gray-500 mb-4" data-customer-email>{{ customer.email }}</p>

                <form @submit.prevent="submitProfile" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Imię i nazwisko</label>
                            <input
                                id="name"
                                name="name"
                                v-model="profileForm.name"
                                type="text"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                :class="{ 'border-red-500': profileForm.errors.name }"
                            />
                            <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600">{{ profileForm.errors.name }}</p>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input
                                id="email"
                                name="email"
                                v-model="profileForm.email"
                                type="email"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                :class="{ 'border-red-500': profileForm.errors.email }"
                            />
                            <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">{{ profileForm.errors.email }}</p>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                            <input
                                id="phone"
                                v-model="profileForm.phone"
                                type="tel"
                                placeholder="123 456 789"
                                @blur="profileForm.phone = formatPhone(profileForm.phone)"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Data urodzenia <span class="text-gray-400 text-xs font-normal">(opcjonalnie – bonus urodzinowy)</span></label>
                            <input
                                id="date_of_birth"
                                v-model="profileForm.date_of_birth"
                                type="date"
                                :max="new Date().toISOString().split('T')[0]"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            />
                            <p v-if="profileForm.errors.date_of_birth" class="mt-1 text-sm text-red-600">{{ profileForm.errors.date_of_birth }}</p>
                        </div>
                    </div>

                    <hr class="my-4" />
                    <h3 class="text-md font-medium text-gray-900">Adres dostawy</h3>

                    <div>
                        <label for="delivery_address" class="block text-sm font-medium text-gray-700 mb-1">Adres (ulica, numer, miasto)</label>
                        <input
                            ref="addressInput"
                            id="delivery_address"
                            v-model="profileForm.delivery_address"
                            type="text"
                            placeholder="ul. Kwiatowa 15/3, Kraków"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        />
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                        >
                            {{ profileForm.processing ? 'Zapisywanie...' : 'Zapisz zmiany' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Orders Tab -->
            <div v-show="activeTab === 'orders'">
                <div v-if="orders.data.length === 0" class="bg-white rounded-lg shadow-md p-8 text-center">
                    <i class="fa-solid fa-bag-shopping text-4xl text-gray-300 mb-3 block"></i>
                    <p class="font-medium text-gray-500 mb-1">Nie masz jeszcze żadnych zamówień</p>
                    <p class="text-sm text-gray-400 mb-4">Odkryj nasze menu i złóż pierwsze zamówienie!</p>
                    <Link :href="route('tenant.menu')" class="inline-block bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">
                        Przejdź do menu
                    </Link>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="order in orders.data"
                        :key="order.id"
                        class="bg-white rounded-lg shadow-md p-5"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                            <div>
                                <p class="font-semibold text-gray-900">Zamówienie #{{ order.order_number }}</p>
                                <p class="text-sm text-gray-500">{{ formatDate(order.created_at) }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span :class="statusClass(order.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                                    {{ statusLabel(order.status) }}
                                </span>
                                <span class="text-lg font-bold text-gray-900">{{ formatPrice(order.total) }} zł</span>
                            </div>
                        </div>

                        <div class="border-t pt-3">
                            <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-600 mb-2">
                                <span>{{ typeLabel(order.type) }}</span>
                                <span v-if="order.delivery_address">{{ order.delivery_address }}</span>
                                <span>{{ paymentLabel(order.payment_method) }}</span>
                            </div>

                            <div class="space-y-1">
                                <div
                                    v-for="item in order.items"
                                    :key="item.id"
                                    class="flex justify-between text-sm"
                                >
                                    <span class="text-gray-700">
                                        {{ item.quantity }}x {{ item.name }}
                                        <span v-if="item.variant_name" class="text-gray-400">({{ item.variant_name }})</span>
                                    </span>
                                    <span class="text-gray-500">{{ formatPrice(item.price * item.quantity) }} zł</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-t flex flex-wrap gap-4">
                            <Link
                                v-if="order.status !== 'completed' && order.status !== 'cancelled'"
                                :href="route('tenant.order.tracking', order.order_number)"
                                class="text-sm text-red-600 hover:text-red-700 font-medium"
                            >
                                Śledź zamówienie →
                            </Link>
                            <a
                                v-if="$page.props.app_version === 'test'"
                                :href="route('tenant.order.invoice', order.order_number)"
                                target="_blank"
                                class="text-sm text-gray-500 hover:text-gray-700"
                            >
                                <i class="fa-solid fa-print mr-1"></i> Faktura/Paragon
                            </a>
                            <!-- Review button (#26) -->
                            <button
                                v-if="order.status === 'completed' && !order.review"
                                @click="openReview(order)"
                                class="text-sm text-yellow-600 hover:text-yellow-700 font-medium"
                            >
                                <i class="fa-solid fa-star mr-1"></i> Oceń zamówienie
                            </button>
                            <span v-if="order.review" class="text-sm text-gray-400 flex items-center gap-1">
                                <i v-for="n in 5" :key="n" class="fa-solid fa-star text-xs" :class="n <= order.review.rating ? 'text-yellow-400' : 'text-gray-200'"></i>
                                Oceniono
                            </span>
                            <!-- Cancel order button -->
                            <button
                                v-if="canCancelOrder(order)"
                                @click="cancelOrder(order.order_number)"
                                class="text-xs text-red-600 hover:text-red-800 font-medium mt-1 block"
                            >
                                Anuluj zamówienie
                            </button>
                        </div>
                        <!-- Review form (inline) -->
                        <div v-if="reviewOrderId === order.id" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <form @submit.prevent="submitReview(order.id)">
                                <p class="font-medium text-gray-900 mb-2">Oceń zamówienie #{{ order.order_number }}</p>
                                <div class="flex gap-2 mb-3">
                                    <button v-for="star in 5" :key="star" type="button" @click="reviewRating = star"
                                        class="text-2xl" :class="star <= reviewRating ? 'text-yellow-400' : 'text-gray-300'"><i class="fa-solid fa-star"></i></button>
                                </div>
                                <textarea v-model="reviewContent" rows="3" required placeholder="Podziel się swoją opinią..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-400 mb-2"></textarea>
                                <div class="flex gap-2">
                                    <button type="submit" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-sm font-medium">Wyślij ocenę</button>
                                    <button type="button" @click="reviewOrderId = null" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Anuluj</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="orders.links && orders.last_page > 1" class="flex justify-center gap-1 pt-4">
                        <template v-for="link in orders.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-2 text-sm rounded-lg transition-colors',
                                    link.active
                                        ? 'bg-red-600 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                                ]"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3 py-2 text-sm text-gray-400"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Loyalty Tab -->
            <div v-show="activeTab === 'loyalty'" class="space-y-4">
                <!-- Not enabled -->
                <div v-if="!loyalty?.enabled" class="bg-white rounded-lg shadow-md p-8 text-center">
                    <p class="text-gray-500">Program lojalnościowy nie jest aktywny w tej restauracji.</p>
                </div>

                <template v-else>
                <!-- Tier card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Program lojalnościowy</h2>
                            <p class="text-sm text-gray-500">Zbieraj punkty i wymieniaj na nagrody</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="px-4 py-2 rounded-full text-white text-sm font-bold shadow"
                                :style="{ backgroundColor: loyalty?.tier_info?.color ?? '#cd7f32' }"
                            >
                                {{ loyalty?.tier_info?.name ?? 'Brąz' }}
                            </span>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-900">{{ loyalty?.points ?? 0 }} pkt</div>
                                <div class="text-xs text-gray-400">Łącznie zarobione: {{ loyalty?.points_earned_total ?? 0 }} pkt</div>
                            </div>
                        </div>
                    </div>

                    <!-- Tier perks -->
                    <div class="grid grid-cols-3 gap-3 mb-5 text-center">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="text-xs text-gray-400 mb-1">Mnożnik</div>
                            <div class="font-bold text-gray-800">×{{ loyalty?.tier_info?.multiplier ?? 1 }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="text-xs text-gray-400 mb-1">Bonus miesięczny</div>
                            <div class="font-bold text-gray-800">{{ loyalty?.tier_info?.monthly_bonus ?? 0 }} pkt</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="text-xs text-gray-400 mb-1">Bonus na dostawę</div>
                            <div class="font-bold text-gray-800">
                                {{ (loyalty?.tier_info?.delivery_bonus ?? 0) >= 999 ? 'gratis' : (loyalty?.tier_info?.delivery_bonus ?? 0) + ' PLN' }}
                            </div>
                        </div>
                    </div>

                    <div v-if="loyalty?.next_tier">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>{{ loyalty.tier_info?.name }}</span>
                            <span>{{ loyalty.next_tier?.name }} (brak {{ loyalty.points_to_next }} pkt)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div
                                class="h-3 rounded-full transition-all"
                                :style="{
                                    width: progressPercent + '%',
                                    backgroundColor: loyalty.next_tier?.color ?? '#f59e0b'
                                }"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Brakuje {{ loyalty.points_to_next }} pkt do poziomu {{ loyalty.next_tier?.name }}</p>
                    </div>
                    <div v-else class="text-center py-3">
                        <p class="text-green-600 font-semibold">Osiągnąłeś najwyższy poziom! <i class="fa-solid fa-trophy"></i></p>
                    </div>
                </div>

                <!-- Loyalty program description -->
                <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <h4 class="text-sm font-semibold text-blue-800 mb-2">Jak działa program lojalnościowy?</h4>
                    <ul class="text-xs text-blue-700 space-y-1">
                        <li>• Za każde zamówienie zbierasz punkty lojalnościowe</li>
                        <li>• Punkty możesz wymieniać na zniżki przy kolejnych zamówieniach</li>
                        <li>• Im wyższy poziom, tym więcej punktów za zamówienie</li>
                        <li>• Specjalne nagrody dostępne w panelu nagród poniżej</li>
                    </ul>
                </div>

                <!-- Referral section -->
                <div v-if="customer.referral_code" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-md font-semibold text-gray-900 mb-1">Poleć znajomego</h3>
                    <p class="text-sm text-gray-500 mb-4">Gdy znajomy zarejestruje się przez Twój link i złoży pierwsze zamówienie, otrzymasz punkty lojalnościowe.</p>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs font-medium text-gray-500 mb-1">Twój kod polecenia</p>
                            <div class="flex items-center gap-2">
                                <span class="px-4 py-2 bg-gray-100 rounded-lg font-mono font-bold text-gray-900 tracking-widest text-lg select-all">{{ customer.referral_code }}</span>
                                <button @click="copyCode" class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-copy mr-1"></i>{{ codeCopied ? 'Skopiowano!' : 'Kopiuj' }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 mb-1">Link do rejestracji</p>
                            <div class="flex items-center gap-2">
                                <input
                                    :value="referralLink"
                                    readonly
                                    class="flex-1 px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 select-all"
                                    @click="$event.target.select()"
                                />
                                <button @click="copyLink" class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap">
                                    <i class="fa-solid fa-link mr-1"></i>{{ linkCopied ? 'Skopiowano!' : 'Kopiuj' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available rewards -->
                <div v-if="loyalty?.available_rewards?.length" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-md font-semibold text-gray-900 mb-4">Dostępne nagrody</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="reward in loyalty.available_rewards"
                            :key="reward.id"
                            class="border rounded-lg p-4 transition-colors"
                            :class="reward.affordable ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50 opacity-60'"
                        >
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <p class="font-medium text-sm text-gray-800">{{ reward.name }}</p>
                                    <p v-if="reward.description" class="text-xs text-gray-500 mt-0.5">{{ reward.description }}</p>
                                    <p class="text-xs mt-1">
                                        <span v-if="reward.type === 'fixed_discount'" class="text-green-600 font-medium">-{{ reward.value }} PLN</span>
                                        <span v-else-if="reward.type === 'percent_discount'" class="text-green-600 font-medium">-{{ reward.value }}%</span>
                                        <span v-else-if="reward.type === 'free_delivery'" class="text-green-600 font-medium">Darmowa dostawa</span>
                                        <span v-else class="text-purple-600 font-medium">{{ reward.product_name }}{{ reward.variant_name ? ` (${reward.variant_name})` : '' }}</span>
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-sm font-bold text-blue-600">{{ reward.cost_points }} pkt</div>
                                    <div v-if="!reward.affordable" class="text-xs text-gray-400">
                                        brak {{ reward.cost_points - loyalty.points }} pkt
                                    </div>
                                    <a
                                        v-else
                                        :href="route('tenant.checkout') + '?reward_id=' + reward.id"
                                        class="inline-block mt-1 px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors"
                                    >Zamów teraz</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Points history -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-md font-semibold text-gray-900 mb-4">Historia punktów</h3>
                    <div v-if="!loyalty?.history?.length" class="text-center py-6 text-gray-400">
                        Brak historii punktów.
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                        <div
                            v-for="(entry, idx) in loyalty.history"
                            :key="idx"
                            class="flex items-center justify-between py-3"
                        >
                            <div>
                                <p class="text-sm text-gray-800">{{ entry.description }}</p>
                                <p class="text-xs text-gray-400">{{ formatDate(entry.created_at) }}</p>
                            </div>
                            <span
                                :class="entry.points >= 0 ? 'text-green-600' : 'text-red-600'"
                                class="font-semibold text-sm"
                            >
                                {{ entry.points >= 0 ? '+' : '' }}{{ entry.points }} pkt
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Redemption history -->
                <div v-if="loyalty?.redemptions?.length" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-md font-semibold text-gray-900 mb-4">Historia wymian nagród</h3>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="(r, idx) in loyalty.redemptions"
                            :key="idx"
                            class="flex items-center justify-between py-3"
                        >
                            <div>
                                <p class="text-sm text-gray-800">{{ r.reward_name || r.description || 'Wymiana punktów' }}</p>
                                <p class="text-xs text-gray-400">{{ formatDate(r.created_at) }}</p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-red-600">-{{ r.points_spent }} pkt</div>
                                <div v-if="r.discount_value > 0" class="text-xs text-green-600">-{{ r.discount_value }} PLN</div>
                            </div>
                        </div>
                    </div>
                </div>
                </template>
            </div>

            <!-- Password Tab -->
            <div v-show="activeTab === 'password'" class="space-y-6">
              <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Zmiana hasła</h2>

                <form @submit.prevent="submitPassword" class="space-y-4 max-w-md">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Obecne hasło</label>
                        <input
                            id="current_password"
                            name="current_password"
                            v-model="passwordForm.current_password"
                            type="password"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            :class="{ 'border-red-500': passwordForm.errors.current_password }"
                        />
                        <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.current_password }}</p>
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Nowe hasło</label>
                        <input
                            id="new_password"
                            name="password"
                            v-model="passwordForm.password"
                            type="password"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            :class="{ 'border-red-500': passwordForm.errors.password }"
                        />
                        <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Powtórz nowe hasło</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        />
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                        >
                            {{ passwordForm.processing ? 'Zapisywanie...' : 'Zmień hasło' }}
                        </button>
                    </div>
                </form>
              </div>

              <!-- GDPR: Delete account -->
              <div class="bg-white rounded-lg shadow-md p-6 border border-red-100">
                <h2 class="text-lg font-semibold text-red-700 mb-2">Usuń konto</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Usunięcie konta jest nieodwracalne. Twoje dane osobowe zostaną usunięte, a historia zamówień zanonimizowana zgodnie z RODO.
                </p>
                <button
                    type="button"
                    @click="showDeleteConfirm = true"
                    class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg"
                >
                    Usuń moje konto
                </button>

                <!-- Confirm dialog -->
                <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Czy na pewno?</h3>
                        <p class="text-sm text-gray-600 mb-6">Ta operacja jest nieodwracalna. Twoje konto zostanie trwale usunięte.</p>
                        <div class="flex gap-3 justify-end">
                            <button @click="showDeleteConfirm = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm">Anuluj</button>
                            <Link :href="route('tenant.account.destroy')" method="delete" as="button" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">
                                Tak, usuń konto
                            </Link>
                        </div>
                    </div>
                </div>
              </div>
            </div>
        </div>
    </ClientLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { formatPhone } from '@/utils/phone'

const props = defineProps({
    customer: Object,
    orders: Object,
    loyalty: Object,
    googleMapsConfigured: { type: Boolean, default: false },
})

const progressPercent = computed(() => {
    if (!props.loyalty?.next_tier) return 100
    const tierMin = props.loyalty.tier_info?.min ?? 0
    const nextMin = props.loyalty.next_tier?.min ?? 1
    const current = props.loyalty.points ?? 0
    const progress = ((current - tierMin) / (nextMin - tierMin)) * 100
    return Math.min(100, Math.max(0, Math.round(progress)))
})

const activeTab = ref('profile')
const showDeleteConfirm = ref(false)

const referralLink = computed(() => {
    const base = window.location.origin + '/konto/rejestracja'
    return props.customer.referral_code ? `${base}?ref=${props.customer.referral_code}` : base
})
const codeCopied = ref(false)
const linkCopied = ref(false)
const copyCode = () => {
    navigator.clipboard.writeText(props.customer.referral_code)
    codeCopied.value = true
    setTimeout(() => { codeCopied.value = false }, 2000)
}
const copyLink = () => {
    navigator.clipboard.writeText(referralLink.value)
    linkCopied.value = true
    setTimeout(() => { linkCopied.value = false }, 2000)
}

// Reviews (#26)
const reviewOrderId = ref(null)
const reviewRating = ref(5)
const reviewContent = ref('')
const openReview = (order) => {
    reviewOrderId.value = order.id
    reviewRating.value = 5
    reviewContent.value = ''
}
const submitReview = (orderId) => {
    useForm({ order_id: orderId, rating: reviewRating.value, content: reviewContent.value })
        .post(route('tenant.review.store'), {
            onSuccess: () => { reviewOrderId.value = null },
        })
}

const canCancelOrder = (order) => {
    if (!['pending', 'accepted'].includes(order.status)) return false
    const created = new Date(order.created_at)
    const diffMinutes = (Date.now() - created.getTime()) / 60000
    return diffMinutes <= 15
}

const cancelOrder = (orderNumber) => {
    if (!confirm('Na pewno chcesz anulować to zamówienie?')) return
    router.delete(route('tenant.account.order.cancel', { orderNumber }))
}

const addressInput = ref(null)

const profileForm = useForm({
    name: props.customer.name || '',
    email: props.customer.email || '',
    phone: formatPhone(props.customer.phone) || '',
    delivery_address: props.customer.delivery_address
        ? `${props.customer.delivery_address}${props.customer.delivery_city ? ', ' + props.customer.delivery_city : ''}`
        : '',
    delivery_city: '',
    delivery_postal_code: '',
    date_of_birth: props.customer.date_of_birth
        ? props.customer.date_of_birth.substring(0, 10)
        : '',
})

onMounted(() => {
    if (!props.googleMapsConfigured || !addressInput.value) return
    const checkMaps = () => {
        if (!window.google?.maps?.places) {
            setTimeout(checkMaps, 200)
            return
        }
        const ac = new window.google.maps.places.Autocomplete(addressInput.value, {
            componentRestrictions: { country: 'pl' },
            fields: ['formatted_address'],
            types: ['address'],
        })
        ac.addListener('place_changed', () => {
            const place = ac.getPlace()
            if (place.formatted_address) profileForm.delivery_address = place.formatted_address
        })
    }
    if (!document.querySelector('script[src*="maps.googleapis.com"]')) {
        const s = document.createElement('script')
        s.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_API_KEY}&libraries=places&language=pl`
        s.async = true
        s.onload = checkMaps
        document.head.appendChild(s)
    } else {
        checkMaps()
    }
})

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const submitProfile = () => {
    profileForm.put(route('tenant.account.update'))
}

const submitPassword = () => {
    passwordForm.put(route('tenant.account.password'), {
        onSuccess: () => passwordForm.reset(),
    })
}

const formatPrice = (price) => {
    return parseFloat(price).toFixed(2).replace('.', ',')
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('pl-PL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const statusLabel = (status) => {
    const labels = {
        pending: 'Oczekujące',
        paid: 'Opłacone',
        preparing: 'W przygotowaniu',
        ready: 'Gotowe',
        on_delivery: 'W dostawie',
        completed: 'Zrealizowane',
        cancelled: 'Anulowane',
    }
    return labels[status] || status
}

const statusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-blue-100 text-blue-800',
        preparing: 'bg-orange-100 text-orange-800',
        ready: 'bg-green-100 text-green-800',
        on_delivery: 'bg-purple-100 text-purple-800',
        completed: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const typeLabel = (type) => {
    const labels = {
        delivery: 'Dostawa',
        pickup: 'Odbiór osobisty',
        dine_in: 'Na miejscu',
    }
    return labels[type] || type
}

const paymentLabel = (method) => {
    const labels = {
        online: 'Płatność online',
        cash: 'Gotówka',
        card_on_delivery: 'Karta przy odbiorze',
    }
    return labels[method] || method
}
</script>
