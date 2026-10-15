import Alpine from 'alpinejs';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/**
 * Toast notifications. Server flash messages and client events both land here.
 * Usage: Alpine.store('toast').push('Saved', 'success')
 */
Alpine.store('toast', {
    items: [],
    push(message, tone = 'success') {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, tone });
        setTimeout(() => this.dismiss(id), 4200);
    },
    dismiss(id) {
        this.items = this.items.filter((item) => item.id !== id);
    },
});

/**
 * Copy text to the clipboard and flip a "copied" flag for ~1.6s.
 * Usage: <div x-data="copyable('https://...')"> <button @click="copy">
 */
Alpine.data('copyable', (text) => ({
    copied: false,
    async copy() {
        try {
            await navigator.clipboard.writeText(text);
        } catch {
            // Older browsers / insecure origins: fall back to a hidden textarea.
            const el = Object.assign(document.createElement('textarea'), { value: text });
            document.body.append(el);
            el.select();
            document.execCommand('copy');
            el.remove();
        }
        this.copied = true;
        setTimeout(() => (this.copied = false), 1600);
    },
}));

/**
 * Turns a shop name into a link-friendly slug while the owner types,
 * until they edit the slug themselves.
 */
Alpine.data('slugField', (initial = '') => ({
    slug: initial,
    touched: initial !== '',
    busy: false,
    fromName(name) {
        if (this.touched) return;
        this.slug = name
            .toLowerCase()
            .replace(/ı/g, 'i').replace(/ğ/g, 'g').replace(/ü/g, 'u')
            .replace(/ş/g, 's').replace(/ö/g, 'o').replace(/ç/g, 'c')
            .normalize('NFKD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 40);
    },
    clean() {
        this.touched = true;
        this.slug = this.slug.toLowerCase().replace(/[^a-z0-9-]/g, '').slice(0, 40);
    },
}));

/**
 * Photo picker with instant preview.
 *
 * Phones produce 4–12 MB photos. We shrink them in the browser *before*
 * uploading (max `maxSide` px), which makes uploads on mobile data fast.
 * The server still validates and re-encodes everything — this is purely
 * a speed-up, never a security measure.
 */
Alpine.data('imageInput', ({ url = null, maxSide = 2000 } = {}) => ({
    original: url,
    preview: url,
    removed: false,
    busy: false,
    dragging: false,

    async pick(event) {
        const input = this.$refs.file;
        const file = event.dataTransfer?.files?.[0] ?? input.files?.[0];
        this.dragging = false;
        if (!file || !file.type.startsWith('image/')) return;

        this.removed = false;
        this.preview = URL.createObjectURL(file);
        this.busy = true;

        try {
            const resized = await shrinkImage(file, maxSide);
            const transfer = new DataTransfer();
            transfer.items.add(resized);
            input.files = transfer.files;
        } catch {
            // Keep the original file; the server will handle it.
            if (event.dataTransfer) {
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
            }
        } finally {
            this.busy = false;
        }
    },

    remove() {
        this.$refs.file.value = '';
        this.preview = null;
        this.removed = this.original !== null;
    },

    undo() {
        this.preview = this.original;
        this.removed = false;
    },
}));

async function shrinkImage(file, maxSide) {
    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
    const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));

    // Already small: don't spend time re-encoding.
    if (scale === 1 && file.size < 1.5 * 1024 * 1024) {
        bitmap.close();
        return file;
    }

    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);
    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    // PNG keeps transparency (logos); photos become JPEG.
    const type = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
    const blob = await new Promise((resolve, reject) =>
        canvas.toBlob((b) => (b ? resolve(b) : reject(new Error('encode failed'))), type, 0.88),
    );
    const name = file.name.replace(/\.[^.]+$/, '') + (type === 'image/png' ? '.png' : '.jpg');

    return new File([blob], name, { type });
}

/**
 * "Available / Sold out" switch that saves instantly (optimistic UI)
 * and flips back if the server refuses.
 */
Alpine.data('availabilityToggle', ({ url, on, failedMessage }) => ({
    on,
    async toggle() {
        const previous = this.on;
        this.on = !this.on;

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ is_available: this.on }),
            });
            if (!response.ok) throw new Error(response.statusText);
        } catch {
            this.on = previous;
            Alpine.store('toast').push(failedMessage, 'error');
        }
    },
}));

/**
 * Builds the shop link with an optional table number (?table=5) to copy.
 * It is the SAME menu and QR code for every table; the number just pre-fills
 * the order, and customers can change it.
 */
Alpine.data('tableLink', (base) => ({
    table: '',
    copied: false,

    get url() {
        const table = this.table.replace(/[^\p{L}\p{N}\- ]/gu, '').trim().slice(0, 20);

        return table ? `${base}?table=${encodeURIComponent(table)}` : base;
    },

    async copy() {
        try {
            await navigator.clipboard.writeText(this.url);
        } catch {
            const el = Object.assign(document.createElement('textarea'), { value: this.url });
            document.body.append(el);
            el.select();
            document.execCommand('copy');
            el.remove();
        }
        this.copied = true;
        setTimeout(() => (this.copied = false), 1600);
    },
}));

/**
 * A switch that saves instantly (optimistic) and flips back if the server
 * refuses. `invert` sends the opposite of what the switch shows (a "taking
 * orders" switch that stores `orders_paused`). `onDone(data)` gets the reply.
 */
Alpine.data('instantSwitch', ({ url, field, on, invert = false, failedMessage, onDone = null }) => ({
    on,
    busy: false,
    async toggle() {
        if (this.busy) return;
        const previous = this.on;
        this.on = !this.on;
        this.busy = true;

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ [field]: invert ? !this.on : this.on }),
            });
            if (!response.ok) throw new Error(response.statusText);
            const data = await response.json().catch(() => ({}));
            if (data.message) Alpine.store('toast').push(data.message);
            onDone?.(data);
        } catch {
            this.on = previous;
            Alpine.store('toast').push(failedMessage, 'error');
        } finally {
            this.busy = false;
        }
    },
}));

/**
 * The "Today" page: edit one product in a sheet (names, description, price,
 * sale price, photo) and update the list in place, no page reload.
 */
Alpine.data('quickEdit', ({ items, urls, locales, messages }) => ({
    items,
    locales,
    lang: locales[0],
    editingId: null,
    form: { names: {}, descriptions: {}, price: '', compare: '' },
    errors: [],
    busy: false,
    preview: null,
    file: null,
    removeImage: false,
    q: '',

    match(id) {
        const needle = this.q.trim().toLowerCase();

        return !needle || Object.values(this.items[id].names).join(' ').toLowerCase().includes(needle);
    },

    get item() {
        return this.items[this.editingId] ?? null;
    },

    open(id) {
        const item = this.items[id];
        this.editingId = id;
        this.lang = this.locales[0];
        this.form = {
            names: { ...item.names },
            descriptions: { ...item.descriptions },
            price: item.price,
            compare: item.compare ?? '',
        };
        this.errors = [];
        this.file = null;
        this.preview = item.thumb;
        this.removeImage = false;
        document.documentElement.classList.add('overflow-hidden');
    },

    close() {
        this.editingId = null;
        document.documentElement.classList.remove('overflow-hidden');
    },

    pickFile(event) {
        const file = event.target.files?.[0];
        if (!file) return;
        this.file = file;
        this.preview = URL.createObjectURL(file);
        this.removeImage = false;
    },

    dropImage() {
        this.file = null;
        this.preview = null;
        this.removeImage = true;
    },

    async send(id, fields) {
        const body = new FormData();
        body.append('_method', 'PATCH');
        for (const [key, value] of fields) body.append(key, value ?? '');

        const response = await fetch(urls.product.replace('__id__', id), {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body,
        });
        const data = await response.json().catch(() => ({}));

        return { ok: response.ok, status: response.status, data };
    },

    async save() {
        this.busy = true;
        this.errors = [];

        const fields = [['price', this.form.price], ['compare_at_price', this.form.compare]];
        for (const locale of this.locales) {
            fields.push([`name[${locale}]`, this.form.names[locale]], [`description[${locale}]`, this.form.descriptions[locale]]);
        }
        if (this.file) fields.push(['image', this.file]);
        if (this.removeImage) fields.push(['remove_image', '1']);

        try {
            const { ok, status, data } = await this.send(this.editingId, fields);

            if (status === 422) {
                this.errors = Object.values(data.errors ?? {}).flat();
                return;
            }
            if (!ok) throw new Error('failed');

            this.items[this.editingId] = data.product;
            Alpine.store('toast').push(data.message);
            this.close();
        } catch {
            this.errors = [messages.failed];
        } finally {
            this.busy = false;
        }
    },

    /** Ends a sale: the old (struck-through) price goes away, the current price stays. */
    async endSale(id) {
        try {
            const { ok, data } = await this.send(id, [['compare_at_price', '']]);
            if (!ok) throw new Error('failed');
            this.items[id] = data.product;
            Alpine.store('toast').push(messages.saleEnded);
        } catch {
            Alpine.store('toast').push(messages.failed, 'error');
        }
    },
}));

/**
 * Normalizes text for forgiving search in Arabic and Turkish:
 * strips Arabic diacritics, unifies alef/taa-marbuta/yaa forms,
 * and treats Turkish ı/İ like i.
 */
const searchable = (text) =>
    text
        .normalize('NFKD')
        .replace(/[ً-ٰٟ̀-ͯ]/g, '')
        .replace(/[أإآ]/g, 'ا')
        .replace(/ة/g, 'ه')
        .replace(/ى/g, 'ي')
        .toLocaleLowerCase('tr')
        .replace(/ı/g, 'i');

/**
 * Instant client-side filter for the product list (a shop has dozens or
 * a few hundred products — no need for a server round-trip).
 */
Alpine.data('productFilter', () => ({
    query: '',
    category: 'all',
    get needle() {
        return searchable(this.query.trim());
    },
    matches(text, categoryId) {
        const inCategory = this.category === 'all' || this.category === categoryId;
        return inCategory && (this.needle === '' || searchable(text).includes(this.needle));
    },
    sectionVisible(texts, categoryId) {
        if (this.category !== 'all' && this.category !== categoryId) return false;
        return this.needle === '' || texts.some((text) => searchable(text).includes(this.needle));
    },
    /** @param {Array<[string, number]>} entries [searchText, categoryId] pairs */
    noResults(entries) {
        return this.needle !== '' && !entries.some(([text, categoryId]) => this.matches(text, categoryId));
    },
}));

/**
 * Brand color picker with a live preview. `foreground` mirrors
 * Shop::brandForeground() so the preview matches the real menu.
 */
Alpine.data('brandPicker', (initial) => ({
    color: initial,
    get foreground() {
        const hex = this.color.replace('#', '');
        if (!/^[0-9a-f]{6}$/i.test(hex)) return '#ffffff';
        const linear = (c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
        const [r, g, b] = [0, 2, 4].map((i) => linear(parseInt(hex.slice(i, i + 2), 16) / 255));
        const luminance = 0.2126 * r + 0.7152 * g + 0.0722 * b;
        return (luminance + 0.05) / 0.062 > 1.05 / (luminance + 0.05) ? '#1f2417' : '#ffffff';
    },
}));

/**
 * Drag-and-drop ordering for a list. Put x-data="sortable({ url, failedMessage })"
 * on the <ol>/<ul>, `data-sort-id` on each item and `data-handle` on its grip.
 * Pointer events, so the same code serves mouse and touch. The new order is
 * saved when the item is dropped; the up/down buttons keep working too.
 */
Alpine.data('sortable', ({ url, failedMessage }) => ({
    drag: null,

    rows() {
        return [...this.$el.querySelectorAll(':scope > [data-sort-id]')];
    },

    start(event) {
        if (event.button !== undefined && event.button !== 0) return;
        const item = event.target.closest('[data-sort-id]');
        if (!item) return;

        event.preventDefault();
        event.currentTarget.setPointerCapture(event.pointerId);

        // The dragged row follows the pointer with a transform and the others
        // slide out of the way; the DOM is only reordered on drop (moving nodes
        // mid-drag would cancel the pointer capture on touch screens).
        const items = this.rows().filter((row) => row.offsetParent !== null);
        const page = (el) => el.getBoundingClientRect().top + window.scrollY;
        const mid = items.map((row) => page(row) + row.offsetHeight / 2);
        const index = items.indexOf(item);

        this.drag = {
            item, items, index, mid,
            startY: event.clientY,
            startScroll: window.scrollY,
            newIndex: index,
            order: this.rows().map((row) => row.dataset.sortId).join(),
            gap: items.length > 1 ? Math.max(0, page(items[1]) - page(items[0]) - items[0].offsetHeight) : 0,
        };

        items.forEach((row) => { row.style.transition = row === item ? 'none' : 'transform 160ms ease'; });
        item.classList.add('z-10', 'shadow-xl', 'ring-2', 'ring-clay/40', 'relative');
    },

    move(event) {
        const drag = this.drag;
        if (!drag) return;

        if (event.clientY < 70) window.scrollBy(0, -14);
        else if (event.clientY > window.innerHeight - 70) window.scrollBy(0, 14);

        const dy = event.clientY - drag.startY + (window.scrollY - drag.startScroll);
        drag.item.style.transform = `translateY(${dy}px)`;

        const center = drag.mid[drag.index] + dy;
        drag.newIndex = drag.mid.filter((value, i) => i !== drag.index && value < center).length;

        const shift = drag.item.offsetHeight + drag.gap;
        drag.items.forEach((row, i) => {
            if (row === drag.item) return;
            let offset = 0;
            if (i > drag.index && i <= drag.newIndex) offset = -shift;
            if (i < drag.index && i >= drag.newIndex) offset = shift;
            row.style.transform = offset ? `translateY(${offset}px)` : '';
        });
    },

    async end() {
        const drag = this.drag;
        if (!drag) return;
        this.drag = null;

        drag.items.forEach((row) => { row.style.transform = ''; row.style.transition = ''; });
        drag.item.classList.remove('z-10', 'shadow-xl', 'ring-2', 'ring-clay/40', 'relative');

        if (drag.newIndex === drag.index) return;

        const others = drag.items.filter((row) => row !== drag.item);
        if (drag.newIndex >= others.length) others.at(-1).after(drag.item);
        else others[drag.newIndex].before(drag.item);

        const rows = this.rows();
        const ids = rows.map((row) => Number(row.dataset.sortId));

        try {
            const response = await fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ ids }),
            });
            if (!response.ok) throw new Error(response.statusText);

            // Keep the up/down buttons truthful about first / last.
            rows.forEach((row, position) => {
                row.querySelectorAll('input[name="direction"]').forEach((input) => {
                    const button = input.nextElementSibling;
                    if (button) button.disabled = input.value === 'up' ? position === 0 : position === rows.length - 1;
                });
            });
        } catch {
            Alpine.store('toast').push(failedMessage, 'error');
        }
    },
}));
window.Alpine = Alpine;
Alpine.start();
