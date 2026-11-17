/**
 * Partage produit (menu créatif) + favoris dynamiques
 */
(function() {
    var config = window.ProductCardConfig || {};
    var isLoggedIn = !!config.isLoggedIn;
    var loginBaseUrl = config.loginUrl || '/user/connexion.php';
    var brandName = config.brandName || 'Trésor Africa';

    var activeShareSheet = null;
    var activeLoginHint = null;

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function getConfig() {
        config = window.ProductCardConfig || config;
        isLoggedIn = !!config.isLoggedIn;
        return config;
    }

    function showToast(message, type) {
        var toast = document.getElementById('product-action-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'product-action-toast';
            toast.className = 'product-action-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.className = 'product-action-toast is-visible' + (type ? ' product-action-toast--' + type : '');
        window.clearTimeout(showToast._timer);
        showToast._timer = window.setTimeout(function() {
            toast.classList.remove('is-visible');
        }, 2800);
    }

    function closeShareSheet() {
        if (!activeShareSheet) {
            return;
        }
        activeShareSheet.classList.remove('is-open');
        window.setTimeout(function() {
            if (activeShareSheet && activeShareSheet.parentNode) {
                activeShareSheet.parentNode.removeChild(activeShareSheet);
            }
            activeShareSheet = null;
        }, 220);
        document.removeEventListener('keydown', onShareEscape);
    }

    function closeLoginHint() {
        if (!activeLoginHint) {
            return;
        }
        activeLoginHint.classList.remove('is-open');
        window.setTimeout(function() {
            if (activeLoginHint && activeLoginHint.parentNode) {
                activeLoginHint.parentNode.removeChild(activeLoginHint);
            }
            activeLoginHint = null;
        }, 200);
    }

    function onShareEscape(event) {
        if (event.key === 'Escape') {
            closeShareSheet();
            closeLoginHint();
        }
    }

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function(resolve, reject) {
            var input = document.createElement('textarea');
            input.value = text;
            input.setAttribute('readonly', '');
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.select();
            try {
                document.execCommand('copy');
                resolve();
            } catch (err) {
                reject(err);
            } finally {
                document.body.removeChild(input);
            }
        });
    }

    function positionFloatingPanel(panel, anchorBtn) {
        var rect = anchorBtn.getBoundingClientRect();
        var panelRect = panel.getBoundingClientRect();
        var top = rect.bottom + 10;
        var left = rect.left + (rect.width / 2) - (panelRect.width / 2);

        if (left + panelRect.width > window.innerWidth - 12) {
            left = window.innerWidth - panelRect.width - 12;
        }
        if (left < 12) {
            left = 12;
        }

        if (top + panelRect.height > window.innerHeight - 12) {
            top = rect.top - panelRect.height - 10;
        }
        if (top < 12) {
            top = 12;
        }

        panel.style.top = top + 'px';
        panel.style.left = left + 'px';
    }

    function buildShareLinks(url, title) {
        var message = title + ' — ' + brandName + '\n' + url;
        return [
            {
                id: 'whatsapp',
                label: 'WhatsApp',
                icon: 'fa-brands fa-whatsapp',
                className: 'product-share-opt--whatsapp',
                href: 'https://wa.me/?text=' + encodeURIComponent(message)
            },
            {
                id: 'facebook',
                label: 'Facebook',
                icon: 'fa-brands fa-facebook-f',
                className: 'product-share-opt--facebook',
                href: 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url)
            },
            {
                id: 'twitter',
                label: 'X / Twitter',
                icon: 'fa-brands fa-x-twitter',
                className: 'product-share-opt--twitter',
                href: 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(title + ' — ' + brandName) + '&url=' + encodeURIComponent(url)
            },
            {
                id: 'email',
                label: 'E-mail',
                icon: 'fa-solid fa-envelope',
                className: 'product-share-opt--email',
                href: 'mailto:?subject=' + encodeURIComponent(title) + '&body=' + encodeURIComponent(message)
            }
        ];
    }

    function openShareSheet(anchorBtn, url, title) {
        closeShareSheet();
        closeLoginHint();

        var sheet = document.createElement('div');
        sheet.className = 'product-share-sheet';
        sheet.setAttribute('role', 'dialog');
        sheet.setAttribute('aria-label', 'Partager ce produit');

        var links = buildShareLinks(url, title);
        var optionsHtml = links.map(function(link) {
            return '<a href="' + escapeHtml(link.href) + '" class="product-share-opt ' + link.className + '"'
                + ' target="_blank" rel="noopener noreferrer">'
                + '<span class="product-share-opt-icon"><i class="' + link.icon + '"></i></span>'
                + '<span class="product-share-opt-label">' + escapeHtml(link.label) + '</span>'
                + '</a>';
        }).join('');

        sheet.innerHTML =
            '<div class="product-share-sheet-inner">'
            + '<div class="product-share-sheet-header">'
            + '<div><span class="product-share-sheet-kicker">Partager</span>'
            + '<strong class="product-share-sheet-title">' + escapeHtml(title) + '</strong></div>'
            + '<button type="button" class="product-share-sheet-close" aria-label="Fermer"><i class="fa-solid fa-xmark"></i></button>'
            + '</div>'
            + '<div class="product-share-sheet-grid">' + optionsHtml
            + '<button type="button" class="product-share-opt product-share-opt--copy" data-copy-url="' + escapeHtml(url) + '">'
            + '<span class="product-share-opt-icon"><i class="fa-solid fa-link"></i></span>'
            + '<span class="product-share-opt-label">Copier le lien</span>'
            + '</button>'
            + '</div>'
            + '</div>';

        document.body.appendChild(sheet);
        activeShareSheet = sheet;

        requestAnimationFrame(function() {
            positionFloatingPanel(sheet, anchorBtn);
            sheet.classList.add('is-open');
        });

        sheet.querySelector('.product-share-sheet-close').addEventListener('click', closeShareSheet);

        sheet.querySelector('.product-share-opt--copy').addEventListener('click', function() {
            var copyBtn = this;
            var copyUrl = copyBtn.getAttribute('data-copy-url') || url;
            copyText(copyUrl).then(function() {
                copyBtn.classList.add('is-copied');
                copyBtn.querySelector('.product-share-opt-label').textContent = 'Lien copié !';
                showToast('Lien copié dans le presse-papiers', 'success');
                window.setTimeout(function() {
                    copyBtn.classList.remove('is-copied');
                    copyBtn.querySelector('.product-share-opt-label').textContent = 'Copier le lien';
                }, 2000);
            }).catch(function() {
                showToast(copyUrl, 'info');
            });
        });

        document.addEventListener('keydown', onShareEscape);
    }

    function openLoginHint(anchorBtn) {
        closeLoginHint();
        closeShareSheet();

        var hint = document.createElement('div');
        hint.className = 'product-favori-login-hint';
        hint.setAttribute('role', 'dialog');
        hint.innerHTML =
            '<div class="product-favori-login-hint-inner">'
            + '<span class="product-favori-login-icon"><i class="fa-solid fa-heart"></i></span>'
            + '<p class="product-favori-login-text">Connectez-vous pour sauvegarder vos coups de cœur</p>'
            + '<div class="product-favori-login-actions">'
            + '<a href="' + escapeHtml(loginBaseUrl + '?redirect=' + encodeURIComponent(window.location.pathname + window.location.search)) + '" class="product-favori-login-btn">Se connecter</a>'
            + '<button type="button" class="product-favori-login-dismiss">Plus tard</button>'
            + '</div></div>';

        document.body.appendChild(hint);
        activeLoginHint = hint;

        requestAnimationFrame(function() {
            positionFloatingPanel(hint, anchorBtn);
            hint.classList.add('is-open');
        });

        hint.querySelector('.product-favori-login-dismiss').addEventListener('click', closeLoginHint);
        document.addEventListener('keydown', onShareEscape);
    }

    function setFavoriState(btn, isFavori) {
        var icon = btn.querySelector('i');
        btn.classList.toggle('is-favori', isFavori);
        btn.setAttribute('aria-pressed', isFavori ? 'true' : 'false');
        btn.setAttribute('title', isFavori ? 'Retirer des favoris' : 'Ajouter aux favoris');
        btn.setAttribute('aria-label', isFavori ? 'Retirer des favoris' : 'Ajouter aux favoris');
        if (icon) {
            icon.className = (isFavori ? 'fa-solid' : 'fa-regular') + ' fa-heart';
        }
    }

    function pulseFavori(btn) {
        btn.classList.add('is-pulsing');
        window.setTimeout(function() {
            btn.classList.remove('is-pulsing');
        }, 520);
    }

    function toggleFavori(btn) {
        getConfig();
        if (!isLoggedIn || btn.classList.contains('needs-login')) {
            openLoginHint(btn);
            return;
        }

        if (btn.classList.contains('is-loading')) {
            return;
        }

        var produitId = btn.getAttribute('data-produit-id');
        var returnUrl = btn.getAttribute('data-return-url') || window.location.pathname + window.location.search;

        btn.classList.add('is-loading');

        fetch('/toggle-favori.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
            body: 'produit_id=' + encodeURIComponent(produitId) + '&return_url=' + encodeURIComponent(returnUrl)
        })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function(result) {
                btn.classList.remove('is-loading');

                if (result.data && result.data.error === 'login_required') {
                    openLoginHint(btn);
                    return;
                }

                if (!result.ok || !result.data || !result.data.ok) {
                    showToast('Impossible de mettre à jour les favoris', 'error');
                    return;
                }

                setFavoriState(btn, !!result.data.is_favori);
                pulseFavori(btn);
                showToast(result.data.message || 'Favori mis à jour', 'success');
            })
            .catch(function() {
                btn.classList.remove('is-loading');
                showToast('Erreur réseau, réessayez', 'error');
            });
    }

    window.buildProductCardActionsHtml = function(produitId, produitNom, shareUrl, isFavori, returnUrl, userLoggedIn) {
        getConfig();
        var loggedIn = typeof userLoggedIn === 'boolean' ? userLoggedIn : isLoggedIn;
        var heartIcon = isFavori ? 'fa-solid' : 'fa-regular';
        var favoriClass = isFavori ? ' is-favori' : '';
        var loginClass = loggedIn ? '' : ' needs-login';
        var favoriLabel = isFavori ? 'Retirer des favoris' : 'Ajouter aux favoris';
        var safeNom = escapeHtml(produitNom || 'Produit');
        var safeUrl = escapeHtml(shareUrl || ('/produit.php?id=' + produitId));
        var safeReturn = escapeHtml(returnUrl || window.location.pathname + window.location.search);

        return '<div class="product-card-actions" data-produit-id="' + produitId + '">'
            + '<button type="button" class="btn-product-action btn-product-favori' + favoriClass + loginClass + '"'
            + ' title="' + favoriLabel + '" aria-label="' + favoriLabel + '"'
            + ' aria-pressed="' + (isFavori ? 'true' : 'false') + '"'
            + ' data-produit-id="' + produitId + '" data-return-url="' + safeReturn + '">'
            + '<i class="' + heartIcon + ' fa-heart"></i></button>'
            + '<button type="button" class="btn-product-action btn-product-share" title="Partager ce produit" aria-label="Partager ce produit"'
            + ' data-share-url="' + safeUrl + '" data-share-title="' + safeNom + '">'
            + '<i class="fa-solid fa-share-nodes"></i></button></div>';
    };

    document.addEventListener('click', function(event) {
        var shareBtn = event.target.closest('.btn-product-share');
        if (shareBtn) {
            event.preventDefault();
            event.stopPropagation();

            var url = shareBtn.getAttribute('data-share-url') || window.location.href;
            var title = shareBtn.getAttribute('data-share-title') || document.title;

            if (activeShareSheet && activeShareSheet.classList.contains('is-open')) {
                closeShareSheet();
                return;
            }

            openShareSheet(shareBtn, url, title);
            return;
        }

        var favoriBtn = event.target.closest('.btn-product-favori');
        if (favoriBtn) {
            event.preventDefault();
            event.stopPropagation();
            toggleFavori(favoriBtn);
            return;
        }

        if (activeShareSheet && !event.target.closest('.product-share-sheet')) {
            closeShareSheet();
        }
        if (activeLoginHint && !event.target.closest('.product-favori-login-hint') && !event.target.closest('.btn-product-favori')) {
            closeLoginHint();
        }
    });

    document.addEventListener('submit', function(event) {
        var form = event.target.closest('.product-favori-form');
        if (form) {
            event.preventDefault();
            event.stopPropagation();
            var btn = form.querySelector('.btn-product-favori');
            if (btn) {
                toggleFavori(btn);
            }
        }
    });
})();
