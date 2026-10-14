/**
 * Public menu behaviour. Native browser APIs only (no animation library):
 *  - IntersectionObserver → scroll reveals, active-category tracking
 *  - View Transitions API → product photo morphs from list into the sheet
 *  - Web Animations API   → cart micro-interactions (fly-to-cart, bump)
 *  - CSS keyframes        → first-visit intro (see menu.css)
 */
import Alpine from 'alpinejs';

const root = document.documentElement;
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
const canTransition = typeof document.startViewTransition === 'function' && !reducedMotion;
const scrollBehavior = reducedMotion ? 'auto' : 'smooth';
const springy = 'cubic-bezier(0.34, 1.56, 0.64, 1)';

const readJson = (id) => JSON.parse(document.getElementById(id)?.textContent || '{}');
const config = readJson('menu-config');

if (!canTransition) {
    root.classList.add('no-vt');
}

/* ------------------------------------------------------------------
   First-visit intro: tap to skip; clean up once it has played.
   ------------------------------------------------------------------ */
if (root.classList.contains('intro')) {
    const end = () => root.classList.remove('intro', 'is-skipped');
    const timer = setTimeout(end, 2100);

    document.querySelector('.splash')?.addEventListener('click', () => {
        clearTimeout(timer);
        root.classList.add('is-skipped');
        setTimeout(end, 500);
    });
}

/* ------------------------------------------------------------------
   Scroll reveal: elements with [data-reveal] rise in once, with a
   small stagger between items that appear together.
   ------------------------------------------------------------------ */
const revealer = new IntersectionObserver(
    (entries) => {
        let delay = 0;
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            entry.target.style.setProperty('--reveal-delay', `${delay}ms`);
            entry.target.classList.add('is-in');
            revealer.unobserve(entry.target);
            delay = Math.min(delay + 70, 280);
        }
    },
    { rootMargin: '0px 0px -6% 0px' },
);
document.querySelectorAll('[data-reveal]').forEach((el) => revealer.observe(el));

/* Lazy images fade in once loaded (cached images are marked at once). */
document.querySelectorAll('img.img-fade').forEach((img) => {
    const done = () => img.classList.add('is-loaded');
    img.complete ? done() : img.addEventListener('load', done, { once: true });
});

/* ------------------------------------------------------------------
   Money: same rules as App\Support\Money (Western digits, no decimals
   for whole amounts, symbol position from the server).
   ------------------------------------------------------------------ */
function formatMoney(amount) {
    const whole = Math.round(amount * 100) % 100 === 0;
    const number = new Intl.NumberFormat(`${config.lang}-u-nu-latn`, {
        minimumFractionDigits: whole ? 0 : 2,
        maximumFractionDigits: whole ? 0 : 2,
    }).format(amount);

    return config.currency?.prefix ? `${config.currency.symbol}${number}` : `${number} ${config.currency?.symbol ?? ''}`;
}

/* Lower-case, accent-insensitive text for the menu search (Arabic + Turkish aware). */
function searchable(text) {
    return String(text ?? '')
        .normalize('NFKD')
        .replace(/[\u064B-\u065F\u0670\u0300-\u036F]/g, '')
        .replace(/[أإآ]/g, 'ا')
        .replace(/ة/g, 'ه')
        .replace(/ى/g, 'ي')
        .toLocaleLowerCase('tr')
        .replace(/ı/g, 'i')
        .trim();
}

/* ------------------------------------------------------------------
   Anonymous counters for the owner's insights: which product was opened or
   added. Nothing else is sent (no cart, no personal data), failures are
   ignored, and the server de-duplicates and rate limits.
   ------------------------------------------------------------------ */
function track(event, productId) {
    if (!config.trackUrl) return;

    fetch(config.trackUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ event, product: Number(productId) }),
        keepalive: true,
    }).catch(() => {});
}

/* ------------------------------------------------------------------
   Cart: lives in this browser only (localStorage, per shop).
   Prices are never trusted from here — the server recomputes them.
   ------------------------------------------------------------------ */
const storage = {
    read(key, fallback) {
        try {
            return JSON.parse(localStorage.getItem(key)) ?? fallback;
        } catch {
            return fallback;
        }
    },
    write(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch {
            // Private mode / storage full: the cart still works for this visit.
        }
    },
};

Alpine.store('cart', {
    lines: {}, // productId → quantity
    details: { type: null, table: '', name: '', address: '', note: '' },

    load(products) {
        const saved = storage.read(`sofra-cart-${config.shopId}`, {});
        const customer = storage.read('sofra-customer', {});

        // Drop anything that disappeared or sold out since last visit.
        for (const [id, qty] of Object.entries(saved.lines ?? {})) {
            if (products[id]?.available) this.lines[id] = Math.min(99, Math.max(1, qty | 0));
        }

        const types = config.orderTypes ?? [];
        // ?table=5 from the shop's QR link. Only a short, plain value is accepted;
        // the customer can still change or clear it in the cart.
        const table = (new URLSearchParams(location.search).get('table') ?? '').replace(/[^\p{L}\p{N}\- ]/gu, '').trim().slice(0, 20) || null;

        this.details = {
            type: table && types.includes('dine_in') ? 'dine_in' : types.includes(saved.type) ? saved.type : types[0],
            table: table ?? saved.table ?? '',
            name: customer.name ?? '',
            address: customer.address ?? '',
            note: '',
        };
    },

    save() {
        storage.write(`sofra-cart-${config.shopId}`, { lines: this.lines, type: this.details.type, table: this.details.table });
        storage.write('sofra-customer', { name: this.details.name, address: this.details.address });
    },

    qty(id) {
        return this.lines[id] ?? 0;
    },

    set(id, qty) {
        qty = Math.min(99, Math.max(0, qty | 0));
        if (qty === 0) {
            delete this.lines[id];
        } else {
            this.lines[id] = qty;
        }
        this.save();
    },

    clear() {
        this.lines = {};
        this.details.note = '';
        this.save();
    },

    get count() {
        return Object.values(this.lines).reduce((sum, qty) => sum + qty, 0);
    },
});

/* ------------------------------------------------------------------
   The page component: category tracking, product sheet, cart sheet.
   ------------------------------------------------------------------ */
Alpine.data('menuPage', () => ({
    products: {},
    sheet: null, // null | 'product' | 'cart'
    productId: null,
    qty: 1,
    closing: false,
    active: null,
    compact: false,
    dragY: 0,
    spyPaused: false,

    // Sending the order
    sending: false,
    errors: {},
    notice: null,
    sent: null,

    init() {
        this.products = readJson('menu-data');
        this.$store.cart.load(this.products);
        this.trackSections();
        this.trackHero();

        // The phone's back button closes the sheet instead of leaving the menu.
        window.addEventListener('popstate', () => {
            if (this.sheet) this.hide();
        });
    },

    get product() {
        return this.sheet === 'product' ? this.products[this.productId] : null;
    },

    get cartLines() {
        const lines = Object.entries(this.$store.cart.lines)
            .filter(([id]) => this.products[id])
            .map(([id, qty]) => ({ id: Number(id), qty, ...this.products[id], full: this.products[id].amount * qty }));

        const discounts = this.discountsFor(lines);

        return lines.map((line) => {
            const discount = discounts[line.id] ?? 0;
            return { ...line, discount, total: line.full - discount };
        });
    },

    get cartTotal() {
        return this.cartLines.reduce((sum, line) => sum + line.total, 0);
    },

    get savings() {
        return this.cartLines.reduce((sum, line) => sum + line.discount, 0);
    },

    /* ---------- Offers ("goes well with"). Same rule as App\Support\Offers —
       display only: the server recomputes every price and discount. ---------- */

    dismissed: [],

    offerPerUnit(discount, price) {
        const value = Number(discount.value);
        const amount = discount.type === 'percent' ? (value > 0 && value <= 90 ? Math.round(price * value) / 100 : 0) : Math.min(value, price);
        return Math.max(0, Math.round(amount * 100) / 100);
    },

    discountsFor(lines) {
        const qty = Object.fromEntries(lines.map((line) => [line.id, line.qty]));
        const byItem = {};

        for (const [id, product] of Object.entries(this.products)) {
            for (const suggestion of product.suggest ?? []) {
                if (suggestion.discount) (byItem[suggestion.item] ??= []).push({ main: Number(id), discount: suggestion.discount });
            }
        }

        const result = {};
        for (const [item, rules] of Object.entries(byItem)) {
            if (!qty[item] || !this.products[item]) continue;

            const price = this.products[item].amount;
            const candidates = rules
                .filter((rule) => qty[rule.main] > 0)
                .map((rule) => ({ units: qty[rule.main], per: this.offerPerUnit(rule.discount, price) }))
                .filter((candidate) => candidate.per > 0)
                .sort((a, b) => b.per - a.per);

            let remaining = qty[item];
            let total = 0;
            for (const candidate of candidates) {
                const units = Math.min(remaining, candidate.units);
                total += units * candidate.per;
                remaining -= units;
            }

            if (total > 0) result[item] = Math.min(Math.round(total * 100) / 100, price * qty[item]);
        }

        return result;
    },

    /** Orderable suggestions for these main products: not in the cart, not dismissed. */
    suggestionsFor(mainIds) {
        const seen = new Set();
        const list = [];

        for (const main of mainIds) {
            for (const suggestion of this.products[main]?.suggest ?? []) {
                const item = this.products[suggestion.item];
                const key = `${main}:${suggestion.item}`;
                if (!item?.available || this.$store.cart.qty(suggestion.item) > 0 || this.dismissed.includes(key) || seen.has(suggestion.item)) continue;
                seen.add(suggestion.item);

                const per = suggestion.discount ? this.offerPerUnit(suggestion.discount, item.amount) : 0;
                list.push({
                    key,
                    item: suggestion.item,
                    name: item.name,
                    thumb: item.thumb,
                    title: suggestion.title,
                    text: suggestion.text,
                    price: item.amount,
                    final: item.amount - per,
                    label: per > 0
                        ? suggestion.discount.type === 'percent'
                            ? config.t.percentOff.replace(':value', String(Number(suggestion.discount.value)))
                            : config.t.amountOff.replace(':amount', formatMoney(per))
                        : null,
                    condition: per > 0 ? config.t.withThis.replace(':name', this.products[main].name) : null,
                });
            }
        }

        return list;
    },

    get sheetSuggestions() {
        return this.sheet === 'product' && this.product?.available ? this.suggestionsFor([this.productId]) : [];
    },

    get cartSuggestions() {
        return this.sheet === 'cart' ? this.suggestionsFor(Object.keys(this.$store.cart.lines).filter((id) => this.products[id])) : [];
    },

    acceptSuggestion(suggestion) {
        this.$store.cart.set(suggestion.item, this.$store.cart.qty(suggestion.item) + 1);
        track('add_to_cart', suggestion.item);
        this.bump(this.$refs.cartBar);
    },

    dismissSuggestion(suggestion) {
        this.dismissed.push(suggestion.key);
    },

    money: formatMoney,

    /* ---------- Delivery fee + minimum (the server re-checks both) ---------- */

    get deliveryFee() {
        return this.$store.cart.details.type === 'delivery' ? config.deliveryFee ?? 0 : 0;
    },

    get grandTotal() {
        return this.cartTotal + this.deliveryFee;
    },

    get belowMinimum() {
        return this.$store.cart.details.type === 'delivery' && config.minOrder != null && this.cartTotal < config.minOrder;
    },

    /* ---------- Search ---------- */

    query: '',
    noResults: false,

    search() {
        const needle = searchable(this.query);
        let any = false;

        document.querySelectorAll('[data-section]').forEach((section) => {
            let visible = 0;
            section.querySelectorAll('[data-card][data-search]').forEach((card) => {
                const match = !needle || searchable(card.dataset.search).includes(needle);
                card.hidden = !match;
                if (match) visible++;
            });
            section.hidden = visible === 0;
            any ||= visible > 0;
        });

        this.noResults = !any;
    },

    /* ---------- Category bar ---------- */

    trackSections() {
        const sections = [...document.querySelectorAll('[data-section]')];
        if (!sections.length) return;

        this.active = sections[0].dataset.section;

        // The observer only tells us *when* to look; the answer itself is
        // computed: the last section whose top has passed 40% of the screen.
        // (Taking "the last intersecting entry" is unreliable after big jumps.)
        const pick = () => {
            if (this.spyPaused) return;
            const line = window.innerHeight * 0.4;
            let current = sections[0];
            for (const section of sections) {
                if (section.getBoundingClientRect().top <= line) current = section;
            }
            this.setActive(current.dataset.section);
        };

        const spy = new IntersectionObserver(pick, { rootMargin: '-40% 0px -55% 0px' });
        sections.forEach((section) => spy.observe(section));
        this.pickSection = pick;

        this.$nextTick(() => this.moveIndicator(false));
        window.addEventListener('resize', () => this.moveIndicator(false));
        document.fonts?.ready.then(() => this.moveIndicator(false));
    },

    setActive(id) {
        if (this.active === id) return;
        this.active = id;
        this.moveIndicator();
    },

    moveIndicator(scrollChip = true) {
        const chip = this.$refs.chips?.querySelector(`[data-chip="${this.active}"]`);
        const indicator = this.$refs.indicator;
        if (!chip || !indicator) return;

        indicator.style.width = `${chip.offsetWidth}px`;
        indicator.style.transform = `translateX(${chip.offsetLeft}px)`;
        indicator.style.opacity = '1';

        if (scrollChip) {
            chip.scrollIntoView({ behavior: scrollBehavior, block: 'nearest', inline: 'center' });
        }
    },

    go(id) {
        const section = document.getElementById(`section-${id}`);
        if (!section) return;

        // Pause tracking so the pill doesn't flicker over sections we pass.
        this.spyPaused = true;
        this.setActive(id);

        const barHeight = this.$refs.bar?.offsetHeight ?? 0;
        const top = section.getBoundingClientRect().top + window.scrollY - barHeight - 12;
        window.scrollTo({ top, behavior: scrollBehavior });

        clearTimeout(this.spyTimer);
        this.spyTimer = setTimeout(() => {
            this.spyPaused = false;
            this.pickSection?.();
        }, 900);
    },

    trackHero() {
        const sentinel = document.getElementById('hero-end');
        if (!sentinel) return;
        new IntersectionObserver(([entry]) => (this.compact = !entry.isIntersecting)).observe(sentinel);
    },

    /* ---------- Sheets (product details & cart) ---------- */

    async open(id, trigger) {
        const product = this.products[id];
        if (!product || this.sheet) return;

        this.trigger = trigger;
        this.source = trigger?.closest('[data-card]')?.querySelector('[data-photo]') ?? null;
        this.qty = this.$store.cart.qty(id) || 1;
        track('product_view', id);

        const morph = canTransition && this.source && product.image;
        await this.show(() => {
            this.productId = Number(id);
            this.sheet = 'product';
        }, morph ? { from: this.source, to: () => this.$refs.hero } : null);
    },

    async openCart(trigger) {
        if (this.sheet) return;
        this.trigger = trigger;
        this.source = null;
        this.errors = {};
        this.notice = null;
        await this.show(() => (this.sheet = 'cart'));
    },

    /**
     * Shows a sheet, optionally morphing an element (the product photo)
     * into its place inside the sheet with a View Transition.
     */
    async show(update, morph = null) {
        history.pushState({ sheet: true }, '');

        const apply = async () => {
            update();
            this.closing = false;
            root.classList.add('sheet-open');
            await this.$nextTick();
        };

        if (canTransition) {
            if (morph) morph.from.style.viewTransitionName = 'product-hero';
            const transition = document.startViewTransition(async () => {
                if (morph) morph.from.style.viewTransitionName = '';
                await apply();
                const target = morph?.to();
                if (target) target.style.viewTransitionName = 'product-hero';
            });
            await transition.finished.catch(() => {});
            if (morph?.to()) morph.to().style.viewTransitionName = '';
        } else {
            await apply();
        }

        this.$refs[this.sheet === 'cart' ? 'cartClose' : 'close']?.focus({ preventScroll: true });
    },

    /** User asked to close: go "back" so history stays clean. */
    close() {
        if (history.state?.sheet) {
            history.back(); // → popstate → hide()
        } else {
            this.hide();
        }
    },

    async hide() {
        if (!this.sheet) return;

        const finish = () => {
            this.sheet = null;
            this.productId = null;
            this.closing = false;
            this.dragY = 0;
            root.classList.remove('sheet-open');
            if (this.sent) this.sent = null;
        };

        const source = this.source;
        const hero = this.sheet === 'product' ? this.$refs.hero : null;
        const morphBack = canTransition && source && hero && isOnScreen(source);

        if (canTransition) {
            if (morphBack) hero.style.viewTransitionName = 'product-hero';
            const transition = document.startViewTransition(async () => {
                if (morphBack) hero.style.viewTransitionName = '';
                finish();
                await this.$nextTick();
                if (morphBack) source.style.viewTransitionName = 'product-hero';
            });
            await transition.finished.catch(() => {});
            if (source) source.style.viewTransitionName = '';
        } else {
            this.closing = true;
            await new Promise((resolve) => setTimeout(resolve, 260));
            finish();
        }

        this.trigger?.focus({ preventScroll: true });
    },

    /* Swipe a sheet down to close it. */
    dragStart(event) {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        const panel = event.currentTarget.closest('.sheet-panel');
        if (panel.scrollTop > 0) return; // scrolling the content, not dragging

        const startY = event.clientY;
        const move = (e) => (this.dragY = Math.max(0, e.clientY - startY));
        const up = () => {
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', up);
            window.removeEventListener('pointercancel', up);
            if (this.dragY > 110) {
                this.close();
            } else {
                this.dragY = 0;
            }
        };

        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', up);
        window.addEventListener('pointercancel', up);
    },

    /* ---------- Adding to the cart ---------- */

    /** "+" on a card: add one, with a dot flying down to the cart bar. */
    quickAdd(id, button) {
        this.$store.cart.set(id, this.$store.cart.qty(id) + 1);
        track('add_to_cart', id);
        this.flyToCart(button);
        this.bump(button);
    },

    /** "Add to order" in the product sheet. */
    addFromSheet() {
        if (this.qty > this.$store.cart.qty(this.productId)) track('add_to_cart', this.productId);
        this.$store.cart.set(this.productId, this.qty);
        this.close();
        setTimeout(() => this.bump(this.$refs.cartBar), 350);
    },

    removeFromSheet() {
        this.$store.cart.set(this.productId, 0);
        this.close();
    },

    flyToCart(from) {
        if (reducedMotion || !from) return;
        const start = from.getBoundingClientRect();
        const dot = document.createElement('span');
        dot.className = 'pointer-events-none fixed z-[60] size-4 rounded-full bg-brand shadow-lg';
        dot.style.left = `${start.left + start.width / 2 - 8}px`;
        dot.style.top = `${start.top + start.height / 2 - 8}px`;
        document.body.append(dot);

        // The cart bar lives at the bottom centre of the screen.
        const dx = window.innerWidth / 2 - (start.left + start.width / 2);
        const dy = window.innerHeight - 44 - (start.top + start.height / 2);

        dot.animate(
            [
                { transform: 'translate(0, 0) scale(1)', opacity: 1 },
                { transform: `translate(${dx * 0.55}px, ${dy * 0.25 - 70}px) scale(1.15)`, opacity: 1, offset: 0.45 },
                { transform: `translate(${dx}px, ${dy}px) scale(0.4)`, opacity: 0.2 },
            ],
            { duration: 650, easing: 'cubic-bezier(0.5, 0, 0.75, 0)' },
        ).finished.then(() => {
            dot.remove();
            this.bump(this.$refs.cartBar);
        });
    },

    bump(el) {
        if (reducedMotion || !el) return;
        el.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.08)' }, { transform: 'scale(1)' }], {
            duration: 420,
            easing: springy,
        });
    },

    /* ---------- Sending the order ---------- */

    async send() {
        const cart = this.$store.cart;
        this.sending = true;
        this.errors = {};
        this.notice = null;

        try {
            const response = await fetch(config.orderUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({
                    lang: config.lang,
                    items: this.cartLines.map((line) => ({ id: line.id, qty: line.qty })),
                    ...cart.details,
                }),
            });
            const data = await response.json().catch(() => ({}));

            if (response.status === 422 && data.closed) {
                // The shop paused ordering (or closed for the day) while the cart was open.
                this.notice = data.message;
                return;
            }
            if (response.status === 422 && data.unavailable) {
                // Sold out meanwhile: take them out and tell the customer.
                for (const id of data.unavailable) {
                    cart.set(id, 0);
                    if (this.products[id]) this.products[id].available = false;
                }
                this.notice = data.message;
                return;
            }
            if (response.status === 422) {
                this.errors = Object.fromEntries(Object.entries(data.errors ?? {}).map(([key, messages]) => [key, messages[0]]));
                return;
            }
            if (!response.ok) throw new Error(response.statusText);

            this.sent = data;
            cart.clear();
            if (!data.demo) this.openWhatsApp();
        } catch {
            this.notice = config.t.error;
        } finally {
            this.sending = false;
        }
    },

    openWhatsApp() {
        // Phones: same-tab navigation. wa.me is a universal link, so the
        // WhatsApp app opens directly — this is the only method that works
        // reliably in iOS Safari after a network request and inside in-app
        // browsers (Instagram, Facebook), which silently drop new tabs.
        // The menu stays in history, so "back" returns to the success screen.
        if (matchMedia('(pointer: coarse)').matches) {
            window.location.href = this.sent.url;
            return;
        }

        // Desktop: a new tab (WhatsApp Web / Desktop) keeps the menu open.
        // (Not the 'noopener' feature: it makes window.open always return null.)
        const tab = window.open(this.sent.url, '_blank');
        if (tab) {
            tab.opener = null;
        } else {
            window.location.href = this.sent.url;
        }
    },
}));

/* ------------------------------------------------------------------
   "Rate us": stars + optional note/photo, posted to the shop owner.
   Phone photos are shrunk in the browser first, so uploads stay fast.
   ------------------------------------------------------------------ */
async function shrinkPhoto(file, maxSide = 1600) {
    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
        return blob ? new File([blob], 'review.jpg', { type: 'image/jpeg' }) : file;
    } catch {
        return file;
    }
}

Alpine.data('reviewForm', ({ url, lang, error }) => ({
    open: false,
    done: false,
    busy: false,
    error: '',
    rating: 0,
    hover: 0,
    comment: '',
    name: '',
    contact: '',
    website: '',
    photo: null,
    preview: null,

    show() {
        this.open = true;
        document.documentElement.classList.add('overflow-hidden');
    },
    hide() {
        this.open = false;
        document.documentElement.classList.remove('overflow-hidden');
    },
    async pick(event) {
        const file = event.target.files?.[0];
        if (!file) return;
        this.photo = await shrinkPhoto(file);
        this.preview = URL.createObjectURL(this.photo);
    },
    clearPhoto() {
        this.photo = null;
        this.preview = null;
        this.$refs.file.value = '';
    },
    async submit() {
        if (!this.rating || this.busy) return;
        this.busy = true;
        this.error = '';

        const body = new FormData();
        body.append('rating', this.rating);
        body.append('lang', lang);
        body.append('website', this.website);
        for (const key of ['comment', 'name', 'contact']) {
            if (this[key].trim()) body.append(key, this[key].trim());
        }
        if (this.photo) body.append('photo', this.photo);

        try {
            const response = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json' } });
            if (response.ok) {
                this.done = true;
            } else {
                const data = await response.json().catch(() => ({}));
                this.error = Object.values(data.errors ?? {})[0]?.[0] ?? error;
            }
        } catch {
            this.error = error;
        } finally {
            this.busy = false;
        }
    },
}));
function isOnScreen(el) {
    const rect = el.getBoundingClientRect();
    return rect.bottom > 0 && rect.top < window.innerHeight;
}

window.Alpine = Alpine;
Alpine.start();
