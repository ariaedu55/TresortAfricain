/**
 * Autocomplétion barre de recherche navigation
 * Clic suggestion → page produit / catégorie directement
 */
(function () {
    'use strict';

    var form = document.getElementById('nav-search-form');
    var input = document.getElementById('nav-search');
    var panel = document.getElementById('nav-search-suggestions');

    if (!form || !input || !panel) {
        return;
    }

    var debounceTimer = null;
    var activeIndex = -1;
    var currentItems = [];
    var lastQuery = '';
    var abortController = null;

    function formatPrice(value) {
        var n = Math.round(Number(value) || 0);
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' FCFA';
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escapeAttr(str) {
        return escapeHtml(str).replace(/`/g, '&#96;');
    }

    function positionPanel() {
        if (window.matchMedia('(max-width: 768px)').matches && !panel.hidden) {
            var rect = input.getBoundingClientRect();
            var formRect = form.getBoundingClientRect();
            panel.style.top = (Math.max(rect.bottom, formRect.bottom) + 8) + 'px';
            panel.style.left = '12px';
            panel.style.right = '12px';
            panel.style.width = 'auto';
        } else {
            panel.style.top = '';
            panel.style.left = '';
            panel.style.right = '';
            panel.style.width = '';
        }
    }

    function hidePanel() {
        panel.innerHTML = '';
        panel.hidden = true;
        panel.setAttribute('aria-hidden', 'true');
        activeIndex = -1;
        currentItems = [];
        input.setAttribute('aria-expanded', 'false');
        if (form.parentElement) {
            form.parentElement.classList.remove('is-suggesting');
        }
        panel.style.top = '';
        panel.style.left = '';
        panel.style.right = '';
        panel.style.width = '';
    }

    function showPanel() {
        panel.hidden = false;
        panel.setAttribute('aria-hidden', 'false');
        input.setAttribute('aria-expanded', 'true');
        if (form.parentElement) {
            form.parentElement.classList.add('is-suggesting');
        }
        positionPanel();
    }

    function setActive(index) {
        var nodes = panel.querySelectorAll('.nav-search-item');
        nodes.forEach(function (node, i) {
            node.classList.toggle('is-active', i === index);
            if (i === index) {
                node.setAttribute('aria-selected', 'true');
            } else {
                node.removeAttribute('aria-selected');
            }
        });
        activeIndex = index;
    }

    function goToItem(item) {
        if (!item || !item.url) {
            return;
        }
        window.location.href = item.url;
    }

    function getProductItems() {
        return currentItems.filter(function (item) {
            return item && item.url && String(item.url).indexOf('/produit.php') !== -1;
        });
    }

    function render(data, query) {
        var produits = Array.isArray(data.produits) ? data.produits : [];
        var categories = Array.isArray(data.categories) ? data.categories : [];
        currentItems = [];

        if (!produits.length && !categories.length) {
            panel.innerHTML = '<div class="nav-search-empty">Aucun résultat pour « ' + escapeHtml(query) + ' »</div>'
                + '<a class="nav-search-see-all" href="/index.php?recherche=' + encodeURIComponent(query) + '#resultats-recherche">'
                + 'Voir tous les produits</a>';
            showPanel();
            return;
        }

        var html = '';

        if (categories.length) {
            html += '<div class="nav-search-group-label">Catégories</div>';
            categories.forEach(function (cat) {
                var idx = currentItems.length;
                currentItems.push(cat);
                html += '<a href="' + escapeAttr(cat.url) + '" class="nav-search-item nav-search-item--cat" data-index="' + idx + '" role="option">'
                    + '<span class="nav-search-thumb"><img src="' + escapeAttr(cat.image_url) + '" alt="" loading="lazy" onerror="this.src=\'/image/logo-brand.png\'"></span>'
                    + '<span class="nav-search-meta">'
                    + '<span class="nav-search-name">' + escapeHtml(cat.nom) + '</span>'
                    + '<span class="nav-search-sub">Catégorie</span>'
                    + '</span>'
                    + '<i class="fa-solid fa-chevron-right nav-search-chevron" aria-hidden="true"></i>'
                    + '</a>';
            });
        }

        if (produits.length) {
            html += '<div class="nav-search-group-label">Produits</div>';
            produits.forEach(function (prod) {
                var idx = currentItems.length;
                currentItems.push(prod);
                var sub = [];
                if (prod.categorie_nom) {
                    sub.push(prod.categorie_nom);
                }
                if (prod.poids) {
                    sub.push(prod.poids);
                }
                html += '<a href="' + escapeAttr(prod.url) + '" class="nav-search-item" data-index="' + idx + '" role="option">'
                    + '<span class="nav-search-thumb"><img src="' + escapeAttr(prod.image_url) + '" alt="" loading="lazy" onerror="this.src=\'/image/produit1.jpg\'"></span>'
                    + '<span class="nav-search-meta">'
                    + '<span class="nav-search-name">' + escapeHtml(prod.nom) + '</span>'
                    + '<span class="nav-search-sub">' + escapeHtml(sub.join(' · ')) + '</span>'
                    + '</span>'
                    + '<span class="nav-search-price' + (prod.has_promotion ? ' is-promo' : '') + '">'
                    + escapeHtml(formatPrice(prod.prix_affichage))
                    + '</span>'
                    + '</a>';
            });
        }

        html += '<a class="nav-search-see-all" href="/index.php?recherche=' + encodeURIComponent(query) + '#resultats-recherche">'
            + 'Voir tous les résultats pour « ' + escapeHtml(query) + ' »'
            + '</a>';

        panel.innerHTML = html;
        showPanel();
        activeIndex = -1;

        panel.querySelectorAll('.nav-search-item').forEach(function (link) {
            link.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            link.addEventListener('click', function (e) {
                var idx = parseInt(link.getAttribute('data-index'), 10);
                var item = currentItems[idx];
                if (item && item.url) {
                    e.preventDefault();
                    window.location.assign(item.url);
                }
            });
        });
    }

    function fetchSuggestions(query) {
        if (abortController && typeof abortController.abort === 'function') {
            abortController.abort();
        }

        if (typeof AbortController === 'function') {
            abortController = new AbortController();
        } else {
            abortController = null;
        }

        var url = '/api/search_suggestions.php?q=' + encodeURIComponent(query) + '&limit=8';
        var opts = abortController ? { signal: abortController.signal } : {};

        fetch(url, opts)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (query !== lastQuery) {
                    return;
                }
                if (!data || data.success === false) {
                    hidePanel();
                    return;
                }
                render(data, query);
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') {
                    return;
                }
                hidePanel();
            });
    }

    function onInput() {
        var query = input.value.trim();
        lastQuery = query;
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            hidePanel();
            return;
        }

        debounceTimer = setTimeout(function () {
            fetchSuggestions(query);
        }, 220);
    }

    input.setAttribute('autocomplete', 'off');
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', 'nav-search-suggestions');
    input.setAttribute('aria-expanded', 'false');

    input.addEventListener('input', onInput);

    input.addEventListener('focus', function () {
        var query = input.value.trim();
        if (query.length >= 2 && panel.hidden) {
            lastQuery = query;
            fetchSuggestions(query);
        }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            hidePanel();
            return;
        }

        if (panel.hidden || !currentItems.length) {
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(activeIndex < currentItems.length - 1 ? activeIndex + 1 : 0);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(activeIndex > 0 ? activeIndex - 1 : currentItems.length - 1);
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0 && currentItems[activeIndex]) {
                e.preventDefault();
                goToItem(currentItems[activeIndex]);
                return;
            }
            var products = getProductItems();
            if (products.length === 1) {
                e.preventDefault();
                goToItem(products[0]);
            }
        }
    });

    form.addEventListener('submit', function (e) {
        if (activeIndex >= 0 && currentItems[activeIndex] && currentItems[activeIndex].url) {
            e.preventDefault();
            goToItem(currentItems[activeIndex]);
            return;
        }
        var products = getProductItems();
        if (products.length === 1) {
            e.preventDefault();
            goToItem(products[0]);
        }
    });

    document.addEventListener('click', function (e) {
        if (!form.contains(e.target) && !panel.contains(e.target)) {
            hidePanel();
        }
    });

    window.addEventListener('resize', function () {
        if (!panel.hidden) {
            positionPanel();
        }
    });

    window.addEventListener('scroll', function () {
        if (!panel.hidden) {
            positionPanel();
        }
    }, { passive: true });
})();
