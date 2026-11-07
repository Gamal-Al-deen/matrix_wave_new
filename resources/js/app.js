const onReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
    } else {
        callback();
    }
};

onReady(() => {
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileToggle = document.querySelector('[data-mobile-menu-toggle]');

    if (mobileMenu && mobileToggle) {
        const setMobileMenu = (open, returnFocus = false) => {
            mobileMenu.classList.toggle('hidden', !open);
            mobileToggle.setAttribute('aria-expanded', String(open));
            mobileToggle.setAttribute('aria-label', open ? 'إغلاق القائمة' : 'فتح القائمة');

            if (!open && returnFocus) {
                mobileToggle.focus();
            }
        };

        mobileToggle.addEventListener('click', () => {
            setMobileMenu(mobileMenu.classList.contains('hidden'));
        });

        mobileMenu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setMobileMenu(false));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !mobileMenu.classList.contains('hidden')) {
                setMobileMenu(false, true);
            }
        });
    }

    const adminSidebar = document.getElementById('admin-sidebar');
    const adminOverlay = document.querySelector('[data-admin-sidebar-overlay]');
    const adminOpen = document.querySelector('[data-admin-sidebar-toggle]');
    const adminClose = document.querySelector('[data-admin-sidebar-close]');
    const adminMain = document.querySelector('[data-admin-main]');
    const adminDesktop = window.matchMedia('(min-width: 1024px)');

    if (adminSidebar) {
        const setAdminSidebar = (open, returnFocus = false) => {
            const mobile = !adminDesktop.matches;

            adminSidebar.classList.toggle('translate-x-full', !open);
            adminSidebar.classList.toggle('invisible', !open && mobile);
            adminSidebar.setAttribute('aria-hidden', String(! open && mobile));
            adminSidebar.inert = ! open && mobile;
            adminOverlay?.classList.toggle('hidden', !open);
            adminOverlay?.setAttribute('aria-hidden', String(! open));
            adminOpen?.setAttribute('aria-expanded', String(open));
            document.body.classList.toggle('overflow-hidden', open && mobile);

            if (adminMain) {
                adminMain.inert = open && mobile;
            }

            if (open && mobile) {
                adminClose?.focus();
            } else if (!open && mobile && returnFocus) {
                adminOpen?.focus();
            }
        };

        adminOpen?.addEventListener('click', () => setAdminSidebar(true));
        adminClose?.addEventListener('click', () => setAdminSidebar(false, true));
        adminOverlay?.addEventListener('click', () => setAdminSidebar(false, true));
        adminSidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                if (!adminDesktop.matches) {
                    setAdminSidebar(false);
                }
            });
        });

        adminDesktop.addEventListener('change', (event) => {
            if (event.matches) {
                adminSidebar.classList.remove('invisible');
                adminSidebar.classList.remove('translate-x-full');
                adminSidebar.setAttribute('aria-hidden', 'false');
                adminSidebar.inert = false;
                adminOverlay?.classList.add('hidden');
                adminOverlay?.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
                adminOpen?.setAttribute('aria-expanded', 'false');

                if (adminMain) {
                    adminMain.inert = false;
                }
            } else {
                setAdminSidebar(false);
            }
        });

        if (adminDesktop.matches) {
            adminSidebar.setAttribute('aria-hidden', 'false');
        } else {
            setAdminSidebar(false);
        }

        document.addEventListener('keydown', (event) => {
            if (adminDesktop.matches) {
                return;
            }

            if (event.key === 'Escape' && !adminSidebar.classList.contains('translate-x-full')) {
                setAdminSidebar(false, true);
            }
        });
    }

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.getAttribute('data-modal-open'));

            if (dialog && typeof dialog.showModal === 'function') {
                dialog.showModal();
            }
        });
    });

    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.querySelectorAll('[data-modal-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.getAttribute('data-password-toggle'));

            if (!input) {
                return;
            }

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
        });
    });

    document.querySelectorAll('form[data-submit-once]:not([data-add-to-cart])').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');

            if (!button) {
                return;
            }

            button.disabled = true;
            button.classList.add('cursor-not-allowed', 'opacity-60');
            button.textContent = 'جارٍ المعالجة...';
        });
    });

    document.querySelectorAll('[data-alert-dismiss]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('[role="alert"]')?.remove();
        });
    });

    document.querySelectorAll('[data-print-invoice]').forEach((button) => {
        button.addEventListener('click', () => {
            window.print();
        });
    });

    document.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => {
        thumb.addEventListener('click', () => {
            const targetId = thumb.getAttribute('data-gallery-target');
            const main = targetId ? document.getElementById(targetId) : null;

            if (!main) {
                return;
            }

            const src = thumb.getAttribute('data-gallery-src');
            const alt = thumb.getAttribute('data-gallery-alt');

            if (src) {
                main.src = src;
            }

            if (alt) {
                main.alt = alt;
            }

            document
                .querySelectorAll(`[data-gallery-thumb][data-gallery-target="${targetId}"]`)
                .forEach((item) => item.setAttribute('aria-pressed', String(item === thumb)));
        });
    });

    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const slides = Array.from(carousel.querySelectorAll('[data-carousel-slide]'));

        if (slides.length === 0) {
            return;
        }

        const dots = Array.from(carousel.querySelectorAll('[data-carousel-dot]'));
        const previous = carousel.querySelector('[data-carousel-prev]');
        const next = carousel.querySelector('[data-carousel-next]');
        const toggle = carousel.querySelector('[data-carousel-toggle]');
        const playIcon = carousel.querySelector('[data-carousel-play-icon]');
        const pauseIcon = carousel.querySelector('[data-carousel-pause-icon]');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        const interval = Math.max(3000, Number.parseInt(carousel.getAttribute('data-interval') || '5000', 10));
        let current = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
        let timer = null;
        let userPaused = false;
        let pointerStart = null;
        let isHovered = false;
        let hasFocus = false;

        const updateToggle = () => {
            if (!toggle) {
                return;
            }

            toggle.setAttribute('aria-pressed', String(userPaused));
            toggle.setAttribute('aria-label', userPaused ? 'تشغيل التبديل التلقائي' : 'إيقاف التبديل التلقائي');
            playIcon?.classList.toggle('hidden', !userPaused);
            pauseIcon?.classList.toggle('hidden', userPaused);
        };

        const stopTimer = () => {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        };

        const startTimer = () => {
            stopTimer();

            if (slides.length < 2 || userPaused || reducedMotion.matches || document.hidden || isHovered || hasFocus) {
                return;
            }

            timer = window.setInterval(() => showSlide(current + 1), interval);
        };

        const restartTimer = () => {
            stopTimer();
            startTimer();
        };

        const showSlide = (index, manual = false) => {
            current = (index + slides.length) % slides.length;

            slides.forEach((slide, slideIndex) => {
                const active = slideIndex === current;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', String(! active));
                slide.inert = ! active;
            });

            dots.forEach((dot, dotIndex) => {
                const active = dotIndex === current;
                const indicator = dot.querySelector('[data-carousel-indicator]');

                if (indicator) {
                    indicator.classList.toggle('w-6', active);
                    indicator.classList.toggle('bg-brand-teal', active);
                    indicator.classList.toggle('w-2.5', ! active);
                    indicator.classList.toggle('bg-white/50', ! active);
                    indicator.classList.toggle('group-hover:bg-white/80', ! active);
                }

                dot.setAttribute('aria-current', String(active));
            });

            if (manual) {
                restartTimer();
            }
        };

        previous?.addEventListener('click', () => showSlide(current - 1, true));
        next?.addEventListener('click', () => showSlide(current + 1, true));

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => showSlide(index, true));
        });

        toggle?.addEventListener('click', () => {
            userPaused = ! userPaused;
            updateToggle();

            if (userPaused) {
                stopTimer();
            } else {
                startTimer();
            }
        });

        carousel.addEventListener('mouseenter', () => {
            isHovered = true;
            stopTimer();
        });
        carousel.addEventListener('mouseleave', () => {
            isHovered = false;
            startTimer();
        });
        carousel.addEventListener('focusin', () => {
            hasFocus = true;
            stopTimer();
        });
        carousel.addEventListener('focusout', (event) => {
            if (! carousel.contains(event.relatedTarget)) {
                hasFocus = false;
                startTimer();
            }
        });

        carousel.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                showSlide(current + 1, true);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                showSlide(current - 1, true);
            }
        });

        carousel.addEventListener('touchstart', (event) => {
            if (event.touches.length !== 1) {
                pointerStart = null;
                return;
            }

            pointerStart = {
                x: event.touches[0].clientX,
                y: event.touches[0].clientY,
            };
        }, { passive: true });

        carousel.addEventListener('touchend', (event) => {
            if (! pointerStart || event.changedTouches.length !== 1) {
                pointerStart = null;
                return;
            }

            const deltaX = event.changedTouches[0].clientX - pointerStart.x;
            const deltaY = event.changedTouches[0].clientY - pointerStart.y;
            pointerStart = null;

            if (Math.abs(deltaX) < 48 || Math.abs(deltaX) <= Math.abs(deltaY) * 1.4) {
                return;
            }

            showSlide(deltaX < 0 ? current + 1 : current - 1, true);
        }, { passive: true });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopTimer();
            } else {
                startTimer();
            }
        });

        const onMotionChange = () => {
            if (reducedMotion.matches) {
                stopTimer();
            } else {
                startTimer();
            }
        };

        if (typeof reducedMotion.addEventListener === 'function') {
            reducedMotion.addEventListener('change', onMotionChange);
        }

        updateToggle();
        showSlide(current);
        startTimer();
    });

    const notificationPoller = document.querySelector('[data-notification-poller]');

    if (notificationPoller) {
        const pollUrl = notificationPoller.getAttribute('data-url');
        const browserEnabled = notificationPoller.getAttribute('data-enabled') === '1';
        const storageKey = 'matrix-wave-seen-notifications';
        const maxSeen = 100;
        const seenSet = new Set();
        let initialized = false;

        try {
            const stored = JSON.parse(window.sessionStorage.getItem(storageKey) || '[]');

            if (Array.isArray(stored)) {
                stored.filter((id) => typeof id === 'string').forEach((id) => seenSet.add(id));
            }
        } catch (error) {
            // Ignore unavailable or malformed session storage.
        }

        const canShowBrowserNotification = () => (
            browserEnabled
            && 'Notification' in window
            && window.Notification.permission === 'granted'
        );

        const showBrowserNotification = (item) => {
            if (! item || ! item.id || seenSet.has(item.id)) {
                return;
            }

            // Mark it seen before checking the browser preference so enabling
            // the permission later never replays notifications from this session.
            seenSet.add(item.id);

            if (! canShowBrowserNotification()) {
                return;
            }

            try {
                const popup = new window.Notification(item.title || 'إشعار جديد', {
                    body: item.body || '',
                    tag: item.id,
                    lang: 'ar',
                    dir: 'rtl',
                });

                popup.onclick = () => {
                    window.focus();
                    popup.close();

                    if (item.url) {
                        window.location.href = item.url;
                    }
                };
            } catch (error) {
                // Keep the in-app notification even if the browser refuses the pop-up.
            }
        };

        const persistSeen = () => {
            const recent = Array.from(seenSet).slice(-maxSeen);

            seenSet.clear();
            recent.forEach((id) => seenSet.add(id));

            try {
                window.sessionStorage.setItem(storageKey, JSON.stringify(recent));
            } catch (error) {
                // Private browsing may block storage; polling still works.
            }
        };

        const refreshBadges = (data) => {
            const unread = Math.max(0, Number(data.unread_count) || 0);

            document.querySelectorAll('[data-notification-badge]').forEach((badge) => {
                badge.textContent = unread > 99 ? '99+' : String(unread);
                badge.classList.toggle('hidden', unread === 0);
            });

            if (typeof data.pending_orders === 'number') {
                const pending = Math.max(0, data.pending_orders);

                document.querySelectorAll('[data-admin-orders-badge]').forEach((badge) => {
                    badge.textContent = pending > 99 ? '99+' : String(pending);
                    badge.classList.toggle('hidden', pending === 0);
                });
            }
        };

        const pollNotifications = async () => {
            try {
                const response = await fetch(pollUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    return;
                }

                const data = await response.json();
                const items = Array.isArray(data.notifications) ? data.notifications : [];

                refreshBadges(data);

                if (initialized) {
                    items.forEach(showBrowserNotification);
                } else {
                    items.forEach((item) => {
                        if (item && item.id) {
                            seenSet.add(item.id);
                        }
                    });
                    initialized = true;
                }

                persistSeen();
            } catch (error) {
                // Server-rendered badges remain usable when polling fails.
            }
        };

        window.setInterval(pollNotifications, 60000);
        pollNotifications();
    }

    document.querySelectorAll('[data-browser-notifications-toggle]').forEach((toggle) => {
        const status = document.getElementById('browser-notification-status');

        const setStatus = (message, tone = 'muted') => {
            if (! status) {
                return;
            }

            status.textContent = message;
            status.classList.toggle('text-danger', tone === 'danger');
            status.classList.toggle('text-success', tone === 'success');
            status.classList.toggle('text-slate-500', tone === 'muted');
        };

        const updateStatus = () => {
            if (! ('Notification' in window)) {
                setStatus('متصفحك لا يدعم إشعارات المتصفح. ستبقى الإشعارات داخل الموقع متاحة.', 'danger');
                return;
            }

            if (window.Notification.permission === 'granted') {
                setStatus(
                    toggle.checked
                        ? 'إشعارات المتصفح مفعّلة ومسموح بها.'
                        : 'إشعارات المتصفح متوقفة. الإشعارات داخل الموقع تبقى متاحة.',
                    toggle.checked ? 'success' : 'muted',
                );
            } else if (window.Notification.permission === 'denied') {
                setStatus('المتصفح رفض إذن الإشعارات. يمكنك تغييره من إعدادات المتصفح. الإشعارات داخل الموقع تبقى متاحة.', 'danger');
            } else {
                setStatus('فعّل الخيار ثم اسمح للمتصفح بإظهار الإشعارات عند الطلب.', 'muted');
            }
        };

        toggle.addEventListener('change', async () => {
            if (toggle.checked && 'Notification' in window && window.Notification.permission === 'default') {
                try {
                    await window.Notification.requestPermission();
                } catch (error) {
                    // The saved preference still works once permission is granted.
                }
            }

            updateStatus();
        });

        updateStatus();
    });

    /*
     * Temporary toast notifications.
     *
     * Server-rendered flash messages arrive inside [data-toast] nodes so they
     * stay visible even before JavaScript runs. This module only adds timers,
     * pause/resume behaviour, dismissal and a small API for future messages.
     */
    const toastRegion = document.querySelector('[data-toast-region]');
    const toastStack = toastRegion?.querySelector('[data-toast-stack]');
    const toastTemplate = toastRegion?.querySelector('template[data-toast-template]');
    const toastTimers = new WeakMap();
    const recentToasts = new Map();
    const maxToasts = 4;
    const toastMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const toastIconMarkup = (type) => {
        const paths = {
            success: '<path d="m5 12 4 4L19 6"></path>',
            error: '<circle cx="12" cy="12" r="9"></circle><path d="m5.6 5.6 12.8 12.8"></path>',
            warning: '<path d="M12 3 2.8 20h18.4z"></path><path d="M12 9v5M12 17.5v.01"></path>',
            info: '<circle cx="12" cy="12" r="9"></circle><path d="M12 10.5V17M12 7.5v.01"></path>',
        };

        return `<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${paths[type] || paths.info}</svg>`;
    };

    const clearToastTimer = (toast) => {
        const timer = toastTimers.get(toast);

        if (timer?.id) {
            window.clearTimeout(timer.id);
        }

        toastTimers.delete(toast);
    };

    const dismissToast = (toast) => {
        if (! toast || toast.dataset.leaving === '1') {
            return;
        }

        toast.dataset.leaving = '1';
        clearToastTimer(toast);

        if (toastMotion.matches) {
            toast.remove();
            return;
        }

        toast.classList.add('is-leaving');
        window.setTimeout(() => toast.remove(), 220);
    };

    const scheduleToast = (toast, duration) => {
        clearToastTimer(toast);

        const timer = {
            id: window.setTimeout(() => dismissToast(toast), duration),
            duration,
            remaining: duration,
            startedAt: Date.now(),
        };

        toastTimers.set(toast, timer);
    };

    const pauseToast = (toast) => {
        const timer = toastTimers.get(toast);

        if (! timer || ! timer.id) {
            return;
        }

        window.clearTimeout(timer.id);
        timer.remaining = Math.max(0, timer.remaining - (Date.now() - timer.startedAt));
        timer.id = null;
        toastTimers.set(toast, timer);
    };

    const resumeToast = (toast) => {
        const timer = toastTimers.get(toast);

        if (! timer || timer.id) {
            return;
        }

        timer.startedAt = Date.now();
        timer.id = window.setTimeout(() => dismissToast(toast), timer.remaining);
        toastTimers.set(toast, timer);
    };

    const setupToast = (toast, type, duration) => {
        if (! toast || toast.dataset.toastReady === '1') {
            return;
        }

        toast.dataset.toastReady = '1';
        toast.dataset.type = type;
        toast.classList.add(`toast--${type}`);
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

        const icon = toast.querySelector('.toast__icon');

        if (icon && ! icon.firstElementChild) {
            icon.innerHTML = toastIconMarkup(type);
        }

        toast.querySelector('[data-toast-close]')?.addEventListener('click', () => dismissToast(toast));
        toast.addEventListener('mouseenter', () => pauseToast(toast));
        toast.addEventListener('mouseleave', () => resumeToast(toast));
        toast.addEventListener('focusin', () => pauseToast(toast));
        toast.addEventListener('focusout', (event) => {
            if (! toast.contains(event.relatedTarget)) {
                resumeToast(toast);
            }
        });

        scheduleToast(toast, duration);
    };

    const showToast = (type, message, options = {}) => {
        if (! toastStack || ! toastTemplate || typeof message !== 'string' || message.trim() === '') {
            return null;
        }

        const safeType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
        const defaultDuration = safeType === 'error' || safeType === 'warning' ? 6000 : 4000;
        const requestedDuration = Number(options.duration);
        const duration = Number.isFinite(requestedDuration) && requestedDuration > 0
            ? Math.max(1000, requestedDuration)
            : defaultDuration;

        const key = `${safeType}|${message.trim()}`;
        const now = Date.now();
        const previous = recentToasts.get(key);

        if (previous && now - previous < 800) {
            return null;
        }

        recentToasts.set(key, now);
        recentToasts.forEach((timestamp, storedKey) => {
            if (now - timestamp > 5000) {
                recentToasts.delete(storedKey);
            }
        });

        const toast = toastTemplate.content.firstElementChild?.cloneNode(true);

        if (! toast) {
            return null;
        }

        toast.dataset.duration = String(duration);
        toast.querySelector('.toast__message').textContent = message.trim();
        setupToast(toast, safeType, duration);
        toastStack.appendChild(toast);

        const visibleToasts = Array.from(toastStack.querySelectorAll('[data-toast]:not([data-leaving="1"])'));

        if (visibleToasts.length > maxToasts) {
            visibleToasts.slice(0, visibleToasts.length - maxToasts).forEach(dismissToast);
        }

        return toast;
    };

    if (toastStack) {
        toastStack.querySelectorAll('[data-toast]').forEach((toast) => {
            const type = ['success', 'error', 'warning', 'info'].includes(toast.dataset.type)
                ? toast.dataset.type
                : 'info';
            const parsedDuration = Number.parseInt(toast.dataset.duration || '', 10);
            const duration = Number.isFinite(parsedDuration) && parsedDuration > 0 ? parsedDuration : 4000;

            setupToast(toast, type, duration);
        });

        document.addEventListener('visibilitychange', () => {
            toastStack.querySelectorAll('[data-toast]').forEach((toast) => {
                if (document.hidden) {
                    pauseToast(toast);
                } else {
                    resumeToast(toast);
                }
            });
        });
    }

    window.MatrixWaveToast = {
        show: showToast,
        success: (message, options) => showToast('success', message, options),
        error: (message, options) => showToast('error', message, options),
        warning: (message, options) => showToast('warning', message, options),
        info: (message, options) => showToast('info', message, options),
    };

    document.addEventListener('matrix-wave:toast', (event) => {
        const detail = event.detail || {};

        showToast(detail.type || 'info', detail.message, { duration: detail.duration });
    });

    /*
     * Add-to-cart confirmation: only animate after the server accepted the
     * request, then trust the server-provided cart count.
     */
    const cartLink = document.querySelector('[data-cart-link]');
    const cartBadge = cartLink?.querySelector('[data-cart-count]');
    const cartMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const cartAriaLabel = (count) => {
        if (! cartLink) {
            return '';
        }

        const singular = cartLink.getAttribute('data-cart-label-singular') || 'سلة المشتريات — {count} منتج';
        const plural = cartLink.getAttribute('data-cart-label-plural') || 'سلة المشتريات — {count} منتجات';

        return (count === 1 ? singular : plural).replace('{count}', String(count));
    };

    const applyCartCount = (count) => {
        const safeCount = Math.max(0, Number(count) || 0);

        if (cartBadge) {
            cartBadge.textContent = String(safeCount);
        }

        cartLink?.setAttribute('aria-label', cartAriaLabel(safeCount));
    };

    const pulseCartIcon = () => {
        if (! cartLink) {
            return;
        }

        cartLink.classList.remove('is-cart-receiving');
        void cartLink.offsetWidth;
        cartLink.classList.add('is-cart-receiving');
        window.setTimeout(() => cartLink.classList.remove('is-cart-receiving'), 900);

        if (cartBadge) {
            cartBadge.classList.remove('is-bumping');
            void cartBadge.offsetWidth;
            cartBadge.classList.add('is-bumping');
            window.setTimeout(() => cartBadge.classList.remove('is-bumping'), 900);
        }
    };

    const productImageFor = (form) => {
        const direct = form.querySelector('[data-cart-product-image]');

        if (direct) {
            return direct;
        }

        const card = form.closest('article');

        if (card) {
            const image = card.querySelector('[data-cart-product-image]');

            if (image) {
                return image;
            }
        }

        const section = form.closest('section');

        if (section) {
            const image = section.querySelector('[data-cart-product-image]');

            if (image) {
                return image;
            }
        }

        return document.getElementById('product-gallery-main');
    };

    const flyProductImage = (image, cartTarget) => {
        if (! image || ! cartTarget || cartMotion.matches || document.hidden) {
            return false;
        }

        const source = image.currentSrc || image.src;

        if (! source || ! image.complete || image.naturalWidth === 0) {
            return false;
        }

        const start = image.getBoundingClientRect();
        const end = cartTarget.getBoundingClientRect();

        if (start.width < 8 || start.height < 8 || end.width < 8 || end.height < 8) {
            return false;
        }

        if (end.bottom < 0 || end.top > window.innerHeight) {
            return false;
        }

        const clone = document.createElement('img');
        clone.src = source;
        clone.alt = '';
        clone.setAttribute('aria-hidden', 'true');
        clone.className = 'cart-fly-image';
        clone.style.left = `${start.left}px`;
        clone.style.top = `${start.top}px`;
        clone.style.width = `${start.width}px`;
        clone.style.height = `${start.height}px`;

        const radius = window.getComputedStyle(image).borderRadius;

        if (radius) {
            clone.style.borderRadius = radius;
        }

        document.body.appendChild(clone);

        const startCenterX = start.left + start.width / 2;
        const startCenterY = start.top + start.height / 2;
        const endCenterX = end.left + end.width / 2;
        const endCenterY = end.top + end.height / 2;
        const deltaX = endCenterX - startCenterX;
        const deltaY = endCenterY - startCenterY;
        const arc = -Math.min(90, Math.max(36, Math.abs(deltaX) * 0.18 + 36));
        const removeClone = () => clone.remove();

        if (typeof clone.animate !== 'function') {
            clone.style.transition = 'transform 650ms cubic-bezier(0.22, 0.61, 0.36, 1), opacity 650ms ease';

            window.requestAnimationFrame(() => {
                clone.style.transform = `translate(${deltaX}px, ${deltaY}px) scale(0.12)`;
                clone.style.opacity = '0.15';
            });

            window.setTimeout(removeClone, 780);

            return true;
        }

        const animation = clone.animate([
            { transform: 'translate(0, 0) scale(1)', opacity: 1 },
            {
                transform: `translate(${deltaX * 0.52}px, ${deltaY * 0.52 + arc}px) scale(0.55)`,
                opacity: 0.9,
                offset: 0.55,
            },
            { transform: `translate(${deltaX}px, ${deltaY}px) scale(0.12)`, opacity: 0.15 },
        ], {
            duration: 650,
            easing: 'cubic-bezier(0.22, 0.61, 0.36, 1)',
            fill: 'forwards',
        });

        animation.finished.then(removeClone).catch(removeClone);
        window.setTimeout(removeClone, 900);

        return true;
    };

    document.querySelectorAll('form[data-add-to-cart]').forEach((form) => {
        const button = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', async (event) => {
            if (form.dataset.busy === '1') {
                event.preventDefault();
                return;
            }

            if (typeof window.fetch !== 'function' || typeof window.FormData !== 'function') {
                return;
            }

            event.preventDefault();

            const image = productImageFor(form);
            const originalButtonHtml = button ? button.innerHTML : '';
            const originalAriaBusy = button?.getAttribute('aria-busy');

            form.dataset.busy = '1';

            if (button) {
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.classList.add('cursor-not-allowed', 'opacity-60');
            }

            try {
                const response = await window.fetch(form.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: new window.FormData(form),
                    credentials: 'same-origin',
                });

                let data = null;

                try {
                    data = await response.json();
                } catch (error) {
                    data = null;
                }

                if (! response.ok || ! data || data.success !== true) {
                    const firstError = data?.errors && typeof data.errors === 'object'
                        ? Object.values(data.errors)
                            .flat()
                            .find((message) => typeof message === 'string' && message.trim() !== '')
                        : null;
                    const message = firstError
                        || data?.message
                        || (response.status === 419
                            ? 'انتهت الجلسة. حدّث الصفحة وحاول مرة أخرى.'
                            : 'تعذر إضافة المنتج إلى السلة. حاول مرة أخرى.');

                    showToast('error', message, { duration: 6000 });

                    return;
                }

                const count = data?.cart_count;

                if (count !== undefined && count !== null && Number.isFinite(Number(count))) {
                    applyCartCount(count);
                }

                const started = flyProductImage(image, cartLink);

                if (started) {
                    window.setTimeout(pulseCartIcon, 620);
                } else {
                    pulseCartIcon();
                }

                showToast('success', data?.message || 'تمت إضافة المنتج إلى السلة', { duration: 4000 });
            } catch (error) {
                showToast('error', 'تعذر إضافة المنتج إلى السلة. تحقق من اتصالك وحاول مرة أخرى.', { duration: 6000 });
            } finally {
                form.dataset.busy = '0';

                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalButtonHtml;

                    if (originalAriaBusy === null) {
                        button.removeAttribute('aria-busy');
                    } else {
                        button.setAttribute('aria-busy', originalAriaBusy);
                    }

                    button.classList.remove('cursor-not-allowed', 'opacity-60');
                }
            }
        });
    });
});
