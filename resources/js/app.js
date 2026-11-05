/**
 * app.js — Sprint 3
 * Dynamic theme, skeleton loading, animasi, search UX
 */

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. DYNAMIC BACKGROUND THEME
    // Ambil tema dari data attribute yang di-set server-side
    // ============================================================
    const themeClass = document.body.dataset.theme;
    if (themeClass) {
        // Kecil delay agar transisi CSS terpicu dengan smooth
        requestAnimationFrame(() => {
            document.body.classList.add(themeClass);
        });
    }

    // ============================================================
    // 2. SEARCH FORM — loading state & UX
    // ============================================================
    const form        = document.getElementById('search-form');
    const btn         = document.getElementById('search-btn');
    const spinner     = document.getElementById('loading-spinner');
    const searchLabel = document.getElementById('search-label');
    const cityInput   = document.getElementById('city-input');

    if (form && btn) {
        form.addEventListener('submit', () => {
            if (!cityInput?.value.trim()) return;

            // Tampilkan skeleton overlay & loading state tombol
            showSkeletonOverlay();
            spinner?.classList.remove('hidden');
            if (searchLabel) searchLabel.textContent = 'Mencari...';
            btn.disabled = true;
        });
    }

    // Auto-focus input jika belum ada data
    if (cityInput && !cityInput.value) {
        cityInput.focus();
    }

    // ============================================================
    // 3. SKELETON OVERLAY
    // Muncul saat form di-submit, hilang saat halaman baru load
    // ============================================================
    function showSkeletonOverlay() {
        const overlay = document.getElementById('skeleton-overlay');
        if (overlay) {
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
        }
    }

    // ============================================================
    // 4. ENTRANCE ANIMATION — semua elemen .weather-enter
    // Kecilkan opacity dulu agar animasi terlihat
    // ============================================================
    const animatedEls = document.querySelectorAll('.weather-enter');
    animatedEls.forEach(el => {
        el.style.opacity = '0';
    });

    // Trigger setelah layout paint
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            animatedEls.forEach(el => {
                el.style.opacity = '';
            });
        });
    });

    // ============================================================
    // 5. HOURLY SCROLL — scroll ke jam sekarang secara otomatis
    //    Jam diambil dari timezone Asia/Jakarta (WIB) via Intl,
    //    BUKAN dari new Date().getHours() yang bergantung timezone browser.
    // ============================================================
    const hourlyContainer = document.getElementById('hourly-scroll');
    if (hourlyContainer) {
        // Ambil jam saat ini dalam WIB menggunakan Intl.DateTimeFormat
        const wibHour = parseInt(
            new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour:     'numeric',
                hour12:   false,
            }).format(new Date()),
            10
        );

        const activeCard = hourlyContainer.querySelector(`[data-hour="${wibHour}"]`);
        if (activeCard) {
            hourlyContainer.scrollLeft = activeCard.offsetLeft - 16;
        }
    }

    // ============================================================
    // 6. WIND DIRECTION ARROW — rotate sesuai derajat angin
    // ============================================================
    const windArrow = document.getElementById('wind-arrow');
    if (windArrow) {
        const degree = parseInt(windArrow.dataset.degree ?? '0', 10);
        windArrow.style.transform = `rotate(${degree}deg)`;
    }

    // ============================================================
    // 7. SUGGESTION PILLS — tampilkan quick search
    // ============================================================
    const suggestions = document.querySelectorAll('[data-suggestion]');
    suggestions.forEach(btn => {
        btn.addEventListener('click', () => {
            if (cityInput) {
                cityInput.value = btn.dataset.suggestion;
                cityInput.focus();
            }
        });
    });

    // ============================================================
    // 8. NAVBAR CLOCK — update jam setiap detik
    //    Menggunakan Intl.DateTimeFormat dengan timezone Asia/Jakarta
    //    sehingga TIDAK bergantung pada timezone Windows/server sama sekali.
    // ============================================================
    const clockEl = document.getElementById('navbar-clock');

    if (clockEl) {
        // Formatter jam:menit menggunakan timezone WIB
        const timeFormatter = new Intl.DateTimeFormat('id-ID', {
            timeZone: 'Asia/Jakarta',
            hour:     '2-digit',
            minute:   '2-digit',
            hour12:   false,
        });

        function updateClock() {
            const now  = new Date();
            const time = timeFormatter.format(now);
            clockEl.textContent = `${time} WIB`;
        }

        // Update langsung & jadwalkan tiap detik
        updateClock();
        setInterval(updateClock, 1000);
    }

    // ============================================================
    // 9. PASSWORD VISIBILITY TOGGLE
    //    Mengelola show/hide untuk semua field password di halaman
    //    auth (login & register). Setiap toggle bekerja independen.
    //
    //    Cara kerja:
    //    - Tiap tombol memiliki data-password-toggle="<field-id>"
    //    - Klik: ambil field via ID → toggle type antara password/text
    //    - Update ikon dan aria-label sesuai state baru
    //    - Password TIDAK disimpan di mana pun — hanya type yang berubah
    // ============================================================
    document.querySelectorAll('[data-password-toggle]').forEach(toggleBtn => {
        const fieldId   = toggleBtn.dataset.passwordToggle;
        const field     = document.getElementById(fieldId);
        const iconShow  = toggleBtn.querySelector('[data-icon-show]');
        const iconHide  = toggleBtn.querySelector('[data-icon-hide]');

        if (! field || ! iconShow || ! iconHide) return;

        toggleBtn.addEventListener('click', () => {
            const isHidden = field.type === 'password';

            // Toggle type input
            field.type = isHidden ? 'text' : 'password';

            // Toggle ikon: eye ↔ eye-slash
            iconShow.classList.toggle('hidden', isHidden);
            iconHide.classList.toggle('hidden', ! isHidden);

            // Update aria-label dan title agar screen reader tahu state saat ini
            const newLabel = isHidden ? 'Sembunyikan password' : 'Tampilkan password';
            toggleBtn.setAttribute('aria-label', newLabel);
            toggleBtn.setAttribute('title', newLabel);

            // Kembalikan fokus ke input setelah toggle
            // agar user bisa langsung lanjut mengetik tanpa klik ulang
            field.focus();
        });
    });

    // ============================================================
    // 10. FAVORITE BUTTON — prevent double-submit & auto-dismiss flash
    //
    //     Cara kerja:
    //     - [data-fav-form] submit → disable tombol [data-fav-submit] agar
    //       tidak bisa diklik dua kali sebelum halaman reload (PRG pattern)
    //     - Flash notification ([data-fav-flash]) auto-dismiss setelah 4 detik
    //       dengan fade-out CSS transition
    // ============================================================

    // Prevent double-submit pada semua form favorit
    document.querySelectorAll('[data-fav-form]').forEach(form => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('[data-fav-submit]');
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor  = 'not-allowed';
            }
        });
    });

    // Auto-dismiss flash notification dari favorite actions
    const favFlash = document.getElementById('fav-flash');
    if (favFlash) {
        // Mulai fade-out setelah 3 detik
        setTimeout(() => {
            favFlash.style.transition = 'opacity 0.5s ease';
            favFlash.style.opacity = '0';

            // Hapus dari DOM setelah transisi selesai
            setTimeout(() => favFlash.remove(), 500);
        }, 3000);
    }

});
