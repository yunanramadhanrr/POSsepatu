// Toggle dark / light mode dan simpan preferensi di sessionStorage (per tab, tidak persist antar device).
document.addEventListener('DOMContentLoaded', function () {
    const root = document.documentElement;
    const toggleBtn = document.getElementById('themeToggle');
    const saved = sessionStorage.getItem('theme');

    if (saved) {
        root.setAttribute('data-bs-theme', saved);
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const current = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-bs-theme', current);
            sessionStorage.setItem('theme', current);
        });
    }

    // Sidebar collapsible untuk layar kecil (mobile/tablet)
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const openBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');

    function openSidebar() {
        sidebar?.classList.add('sidebar-open');
        backdrop?.classList.add('show');
    }
    function closeSidebar() {
        sidebar?.classList.remove('sidebar-open');
        backdrop?.classList.remove('show');
    }

    openBtn?.addEventListener('click', openSidebar);
    closeBtn?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);
});
