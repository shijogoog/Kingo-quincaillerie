/**
 * Scripts principaux QuincaStore
 */

document.addEventListener('DOMContentLoaded', function () {

    // ===== TOGGLE SIDEBAR MOBILE =====
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar    = document.querySelector('.sidebar');
    if (menuToggle) {
        menuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });
    }
    document.addEventListener('click', function (e) {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && menuToggle && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });

    // ===== AUTO-HIDE ALERTS =====
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity    = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4500);
    });

    // ===== CONFIRMATION SUPPRESSION =====
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const id = 'confirmDeleteModal';
            if (!document.getElementById(id)) {
                document.body.insertAdjacentHTML('beforeend', `
                    <div class="modal fade" id="${id}" tabindex="-1">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmer</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">Voulez-vous vraiment supprimer cet élément ?</div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Annuler</button>
                                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Supprimer</button>
                                </div>
                            </div>
                        </div>
                    </div>`);
            }
            const modal = new bootstrap.Modal(document.getElementById(id));
            modal.show();
            document.getElementById('confirmDeleteBtn').onclick = () => {
                modal.hide();
                form.submit();
            };
        });
    });

    // ===== TOAST =====
    window.showToast = function (message, type = 'info') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const icons = { success: 'bi-check-circle-fill', danger: 'bi-exclamation-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<i class="bi ${icons[type] || icons.info}" style="flex-shrink:0"></i>
            <span style="flex:1;font-size:.875rem">${message}</span>
            <i class="bi bi-x toast-close"></i>`;
        container.appendChild(toast);
        toast.querySelector('.toast-close').addEventListener('click', () => toast.remove());
        setTimeout(() => { toast.style.transition = 'opacity .3s'; toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 4500);
    };

    // ===== RECHERCHE GLOBALE =====
    const searchInput = document.querySelector('.search-bar input');
    const searchBar   = document.querySelector('.search-bar');

    if (searchInput && searchBar) {
        let dropdown = null;
        let debounceTimer = null;

        // Créer dropdown
        function getDropdown() {
            if (!dropdown) {
                dropdown = document.createElement('div');
                dropdown.className = 'search-results-dropdown';
                searchBar.appendChild(dropdown);
            }
            return dropdown;
        }

        function closeDropdown() {
            if (dropdown) { dropdown.classList.remove('open'); dropdown.innerHTML = ''; }
        }

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            if (q.length < 2) { closeDropdown(); return; }
            debounceTimer = setTimeout(() => doSearch(q), 220);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeDropdown(); this.blur(); }
        });

        document.addEventListener('click', function (e) {
            if (!searchBar.contains(e.target)) closeDropdown();
        });

        async function doSearch(q) {
            const dd = getDropdown();
            dd.innerHTML = '<div class="search-no-result"><i class="bi bi-hourglass-split"></i> Recherche...</div>';
            dd.classList.add('open');

            try {
                const res  = await fetch('search_ajax.php?q=' + encodeURIComponent(q));
                const data = await res.json();
                renderResults(dd, data, q);
            } catch (err) {
                // Fallback : recherche côté client dans les tables visibles
                renderClientSideResults(dd, q);
            }
        }

        function renderResults(dd, data, q) {
            dd.innerHTML = '';
            let hasResults = false;

            // Articles
            if (data.articles && data.articles.length > 0) {
                hasResults = true;
                dd.insertAdjacentHTML('beforeend', '<div class="search-result-section"><i class="bi bi-box me-1"></i>Articles</div>');
                data.articles.forEach(a => {
                    const item = document.createElement('a');
                    item.className = 'search-result-item';
                    item.href = `modifier_article.php?id=${a.id}`;
                    item.innerHTML = `
                        <div class="search-result-icon" style="background:#eff6ff;color:#3b82f6"><i class="bi bi-box"></i></div>
                        <div>
                            <div class="search-result-name">${highlight(a.nom, q)}</div>
                            <div class="search-result-sub">${a.categorie || 'Sans catégorie'} · ${a.prix_vente_fmt}</div>
                        </div>
                        <span class="search-result-badge ${a.quantite == 0 ? 'badge-danger' : (a.quantite <= a.stock_minimum ? 'badge-warning' : 'badge-success')}">
                            ${a.quantite} u.
                        </span>`;
                    dd.appendChild(item);
                });
            }

            // Ventes
            if (data.ventes && data.ventes.length > 0) {
                hasResults = true;
                dd.insertAdjacentHTML('beforeend', '<div class="search-result-section"><i class="bi bi-cart3 me-1"></i>Ventes</div>');
                data.ventes.forEach(v => {
                    const item = document.createElement('a');
                    item.className = 'search-result-item';
                    item.href = `details_vente.php?id=${v.id}`;
                    item.innerHTML = `
                        <div class="search-result-icon" style="background:#f0fdf4;color:#16a34a"><i class="bi bi-cart3"></i></div>
                        <div>
                            <div class="search-result-name">${highlight(v.client_nom || 'Anonyme', q)} <small style="color:#94a3b8">#${v.id}</small></div>
                            <div class="search-result-sub">${v.date_fmt} · ${v.montant_fmt}</div>
                        </div>
                        <span class="search-result-badge ${v.statut === 'validée' ? 'badge-success' : 'badge-danger'}">${v.statut}</span>`;
                    dd.appendChild(item);
                });
            }

            // Fournisseurs
            if (data.fournisseurs && data.fournisseurs.length > 0) {
                hasResults = true;
                dd.insertAdjacentHTML('beforeend', '<div class="search-result-section"><i class="bi bi-truck me-1"></i>Fournisseurs</div>');
                data.fournisseurs.forEach(f => {
                    const item = document.createElement('a');
                    item.className = 'search-result-item';
                    item.href = `fournisseurs.php`;
                    item.innerHTML = `
                        <div class="search-result-icon" style="background:#fff7ed;color:#f97316"><i class="bi bi-truck"></i></div>
                        <div>
                            <div class="search-result-name">${highlight(f.nom, q)}</div>
                            <div class="search-result-sub">${f.telephone || ''}</div>
                        </div>`;
                    dd.appendChild(item);
                });
            }

            if (!hasResults) {
                dd.innerHTML = `<div class="search-no-result"><i class="bi bi-search me-2"></i>Aucun résultat pour "<strong>${q}</strong>"</div>`;
            } else {
                // Lien voir tout
                dd.insertAdjacentHTML('beforeend', `
                    <div style="padding:8px 12px;border-top:1px solid #f1f5f9;text-align:center">
                        <a href="articles.php" style="font-size:.78rem;color:var(--primary);text-decoration:none;font-weight:500">
                            Voir tous les articles <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>`);
            }
        }

        // Fallback recherche côté client sur les tables visibles de la page courante
        function renderClientSideResults(dd, q) {
            const rows  = document.querySelectorAll('table tbody tr');
            const found = [];
            rows.forEach(row => {
                if (row.textContent.toLowerCase().includes(q.toLowerCase())) {
                    found.push(row.textContent.replace(/\s+/g, ' ').trim().substring(0, 80));
                }
            });

            if (found.length > 0) {
                dd.innerHTML = '<div class="search-result-section">Résultats sur cette page</div>';
                found.slice(0, 6).forEach(text => {
                    dd.insertAdjacentHTML('beforeend', `<div class="search-result-item"><div class="search-result-name" style="font-size:.8rem">${text}</div></div>`);
                });
            } else {
                dd.innerHTML = `<div class="search-no-result">Aucun résultat pour "<strong>${q}</strong>"</div>`;
            }
        }

        function highlight(text, q) {
            if (!text) return '';
            const regex = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return text.replace(regex, '<mark style="background:#fef9c3;border-radius:2px;padding:0 1px">$1</mark>');
        }

        // Raccourci clavier Ctrl+K / Cmd+K
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }

    // ===== RECHERCHE LIVE SUR LES TABLES (pages sans AJAX) =====
    document.querySelectorAll('.table-search').forEach(input => {
        input.addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const wrapper = this.closest('.table-container') || this.closest('.card');
            if (!wrapper) return;
            wrapper.querySelectorAll('table tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        });
    });

    // ===== VALIDATION FORMULAIRES =====
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); }
            form.classList.add('was-validated');
        }, false);
    });

    // ===== TOOLTIPS BOOTSTRAP =====
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

});