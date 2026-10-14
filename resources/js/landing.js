/**
 * Landing page behaviour: scroll reveals, sticky nav, the phone story
 * (played only while visible) and the small interactive feature demos.
 */
import Alpine from 'alpinejs';

const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

/* Scroll reveal ([data-reveal] → .is-in), with a small stagger. */
const revealer = new IntersectionObserver(
    (entries) => {
        let delay = 0;
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            entry.target.style.setProperty('--reveal-delay', `${delay}ms`);
            entry.target.classList.add('is-in');
            revealer.unobserve(entry.target);
            delay = Math.min(delay + 90, 360);
        }
    },
    { rootMargin: '0px 0px -8% 0px' },
);
document.querySelectorAll('[data-reveal]').forEach((el) => revealer.observe(el));

/* The phone story and marquee only animate while on screen (battery!). */
const player = new IntersectionObserver((entries) => {
    for (const entry of entries) entry.target.classList.toggle('is-playing', entry.isIntersecting);
});
document.querySelectorAll('.story').forEach((el) => player.observe(el));

/* The hero phone leans toward the pointer (desktop only). */
const tiltEl = document.querySelector('[data-tilt]');
if (tiltEl && !reducedMotion && matchMedia('(hover: hover)').matches) {
    addEventListener('pointermove', (e) => {
        tiltEl.style.setProperty('--tx', ((e.clientX / innerWidth) * 2 - 1).toFixed(3));
        tiltEl.style.setProperty('--ty', ((e.clientY / innerHeight) * 2 - 1).toFixed(3));
    }, { passive: true });
}

/* Navigation gets a background once the page is scrolled. */
Alpine.data('landingNav', () => ({
    scrolled: false,
    init() {
        const update = () => (this.scrolled = window.scrollY > 12);
        update();
        window.addEventListener('scroll', update, { passive: true });
    },
}));

/* Feature card: one product name, cycling through the three languages. */
Alpine.data('languageCycle', (names) => ({
    names,
    index: 0,
    init() {
        if (reducedMotion) return;
        this.timer = setInterval(() => (this.index = (this.index + 1) % this.names.length), 2400);
    },
    destroy() {
        clearInterval(this.timer);
    },
    get current() {
        return this.names[this.index];
    },
}));

/* Feature card: live brand-color preview. */
Alpine.data('brandDemo', (colors) => ({
    colors,
    color: colors[0],
}));

window.Alpine = Alpine;
Alpine.start();
