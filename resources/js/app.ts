import './bootstrap';

const toggleClass = (element: Element | null, className: string, force?: boolean) => {
    element?.classList.toggle(className, force);
};

const setupNavbar = () => {
    const navbar = document.querySelector<HTMLElement>('[data-navbar]');
    const button = document.querySelector<HTMLButtonElement>('[data-mobile-menu-button]');
    const menu = document.querySelector<HTMLElement>('[data-mobile-menu]');
    const openIcon = document.querySelector<HTMLElement>('[data-menu-open-icon]');
    const closeIcon = document.querySelector<HTMLElement>('[data-menu-close-icon]');

    const setMenuOpen = (open: boolean) => {
        toggleClass(menu, 'hidden', !open);
        toggleClass(openIcon, 'hidden', open);
        toggleClass(closeIcon, 'hidden', !open);
        button?.setAttribute('aria-expanded', String(open));
    };

    button?.addEventListener('click', () => setMenuOpen(menu?.classList.contains('hidden') ?? true));

    const onScroll = () => {
        const compact = window.scrollY > 20;
        navbar?.classList.toggle('py-2', compact);
        navbar?.classList.toggle('py-4', !compact);
        navbar?.classList.toggle('shadow-md', compact);
    };

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
};

const setupPortfolio = () => {
    const root = document.querySelector<HTMLElement>('[data-portfolio]');
    if (!root) return;

    const filters = [...root.querySelectorAll<HTMLButtonElement>('[data-filter]')];
    const cards = [...root.querySelectorAll<HTMLElement>('[data-portfolio-card]')];
    const loadMore = root.querySelector<HTMLButtonElement>('[data-load-more]');
    const modal = document.querySelector<HTMLElement>('[data-project-modal]');
    const modalImage = document.querySelector<HTMLImageElement>('[data-modal-image]');
    const modalCategory = document.querySelector<HTMLElement>('[data-modal-category]');
    const modalClient = document.querySelector<HTMLElement>('[data-modal-client]');
    const modalDate = document.querySelector<HTMLElement>('[data-modal-date]');
    const modalMeta = document.querySelector<HTMLElement>('[data-modal-meta]');
    const modalTitle = document.querySelector<HTMLElement>('[data-modal-title]');
    const modalDescription = document.querySelector<HTMLElement>('[data-modal-description]');
    const modalServices = document.querySelector<HTMLElement>('[data-modal-services]');
    const modalGallery = document.querySelector<HTMLElement>('[data-modal-gallery]');
    const closeButtons = document.querySelectorAll('[data-modal-close]');
    let activeFilter = 'All';
    let visibleCount = 6;

    const visibleCards = () => cards.filter((card) => activeFilter === 'All' || card.dataset.category === activeFilter);

    const render = () => {
        const matching = visibleCards();
        cards.forEach((card) => card.classList.add('hidden'));
        matching.slice(0, visibleCount).forEach((card) => card.classList.remove('hidden'));
        loadMore?.classList.toggle('hidden', visibleCount >= matching.length);
    };

    filters.forEach((filter) => {
        filter.addEventListener('click', () => {
            activeFilter = filter.dataset.filter ?? 'All';
            visibleCount = 6;
            filters.forEach((item) => {
                item.classList.remove('text-primary', 'after:absolute', 'after:inset-x-0', 'after:bottom-0', 'after:h-0.5', 'after:bg-primary');
                item.classList.add('text-slate-600');
            });
            filter.classList.add('text-primary', 'after:absolute', 'after:inset-x-0', 'after:bottom-0', 'after:h-0.5', 'after:bg-primary');
            filter.classList.remove('text-slate-600');
            render();
        });
    });

    loadMore?.addEventListener('click', () => {
        visibleCount += 6;
        render();
    });

    cards.forEach((card) => {
        card.addEventListener('click', () => {
            const project = JSON.parse(card.dataset.project ?? '{}');
            if (modalImage) {
                modalImage.src = project.image ?? '';
                modalImage.alt = project.title ?? '';
            }
            if (modalCategory) modalCategory.textContent = project.category ?? '';
            if (modalClient) modalClient.textContent = project.client ?? '';
            if (modalDate) modalDate.textContent = project.date ?? '';
            if (modalMeta) modalMeta.textContent = project.category ?? '';
            if (modalTitle) modalTitle.textContent = project.title ?? '';
            if (modalDescription) modalDescription.textContent = project.description ?? '';
            if (modalServices) {
                modalServices.innerHTML = '';
                (project.services ?? []).forEach((service: string) => {
                    const badge = document.createElement('span');
                    badge.className = 'rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-medium text-slate-600 shadow-sm';
                    badge.textContent = service;
                    modalServices.appendChild(badge);
                });
            }
            if (modalGallery) {
                modalGallery.innerHTML = '';
                const gallery = project.gallery ?? [];
                gallery.forEach((url: string, index: number) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'group aspect-[4/3] overflow-hidden rounded-lg bg-slate-100 ring-primary transition hover:ring-2 focus:outline-none focus:ring-2';
                    button.setAttribute('aria-label', `Show ${project.title ?? 'project'} gallery image ${index + 1}`);

                    const image = document.createElement('img');
                    image.src = url;
                    image.alt = `${project.title ?? 'Project'} gallery image ${index + 1}`;
                    image.className = 'h-full w-full object-cover transition duration-500 group-hover:scale-105';
                    button.appendChild(image);

                    button.addEventListener('click', () => {
                        if (modalImage) {
                            modalImage.src = url;
                            modalImage.alt = image.alt;
                        }
                    });
                    modalGallery.appendChild(button);
                });
            }
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        });
    });

    const closeModal = () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };

    closeButtons.forEach((button) => button.addEventListener('click', closeModal));
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    render();
};

const setupGallery = () => {
    const lightbox = document.querySelector<HTMLElement>('[data-lightbox]');
    const lightboxImage = document.querySelector<HTMLImageElement>('[data-lightbox-image]');
    const close = document.querySelector<HTMLButtonElement>('[data-lightbox-close]');

    document.querySelectorAll<HTMLButtonElement>('[data-gallery-image]').forEach((button) => {
        button.addEventListener('click', () => {
            if (lightboxImage) lightboxImage.src = button.dataset.galleryImage ?? '';
            lightbox?.classList.remove('hidden');
            lightbox?.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        });
    });

    const closeLightbox = () => {
        lightbox?.classList.add('hidden');
        lightbox?.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };

    close?.addEventListener('click', closeLightbox);
    lightbox?.addEventListener('click', (event) => {
        if (event.target === lightbox) closeLightbox();
    });
};

const setupCheckoutSummary = () => {
    const quantity = document.querySelector<HTMLInputElement>('[data-checkout-quantity]');
    const total = document.querySelector<HTMLElement>('[data-checkout-total]');
    if (!quantity || !total) return;

    const unitPrice = Number(quantity.dataset.unitPrice ?? 0);
    const formatter = new Intl.NumberFormat('en-KE');

    const render = () => {
        const amount = Math.max(Number(quantity.value || 1), 1) * unitPrice;
        total.textContent = `KES ${formatter.format(amount)}`;
    };

    quantity.addEventListener('input', render);
    render();
};

const setupBrandCarousel = () => {
    const root = document.querySelector<HTMLElement>('[data-brand-carousel]');
    const track = document.querySelector<HTMLElement>('[data-brand-track]');
    const previous = document.querySelector<HTMLButtonElement>('[data-brand-prev]');
    const next = document.querySelector<HTMLButtonElement>('[data-brand-next]');
    if (!root || !track || !previous || !next) return;

    const scrollBy = () => Math.max(track.clientWidth * 0.8, 260);
    previous.addEventListener('click', () => track.scrollBy({ left: -scrollBy(), behavior: 'smooth' }));
    next.addEventListener('click', () => track.scrollBy({ left: scrollBy(), behavior: 'smooth' }));
};

document.addEventListener('DOMContentLoaded', () => {
    setupNavbar();
    setupPortfolio();
    setupGallery();
    setupCheckoutSummary();
    setupBrandCarousel();
});
