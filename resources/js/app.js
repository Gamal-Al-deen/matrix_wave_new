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

    document.querySelectorAll('[data-submit-once]').forEach((form) => {
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
});
