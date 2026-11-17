/**
 * Produits populaires — navigation mobile dynamique (onglets, dots, swipe)
 */
(function () {
    'use strict';

    var wrap = document.getElementById('pop-cat-wrap');
    if (!wrap) {
        return;
    }

    var tabsScroll = wrap.querySelector('.pop-cat-tabs-scroll');
    var tabs = wrap.querySelectorAll('.pop-cat-tab');
    var dots = wrap.querySelectorAll('.pop-cat-dot');
    var columns = wrap.querySelectorAll('.pop-cat-column');
    var viewport = wrap.querySelector('.pop-cat-panel-viewport');
    var mobileQuery = window.matchMedia('(max-width: 992px)');
    var currentIndex = 0;
    var touchStartX = 0;
    var touchStartY = 0;
    var isSwiping = false;

    function isMobileTabs() {
        return mobileQuery.matches;
    }

    function getIndexFromAttr(el) {
        return parseInt(el.getAttribute('data-pop-index'), 10) || 0;
    }

    function scrollTabIntoView(tab) {
        if (!tabsScroll || !tab || !isMobileTabs()) {
            return;
        }
        var targetLeft = tab.offsetLeft - (tabsScroll.clientWidth - tab.offsetWidth) / 2;
        tabsScroll.scrollTo({
            left: Math.max(0, targetLeft),
            behavior: 'smooth'
        });
    }

    function activateCategory(idx, direction) {
        idx = String(idx);
        var numIdx = parseInt(idx, 10);

        tabs.forEach(function (t) {
            var active = t.getAttribute('data-pop-index') === idx;
            t.classList.toggle('is-active', active);
            t.setAttribute('aria-selected', active ? 'true' : 'false');
            if (active) {
                scrollTabIntoView(t);
            }
        });

        dots.forEach(function (d) {
            var active = d.getAttribute('data-pop-index') === idx;
            d.classList.toggle('is-active', active);
            d.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        columns.forEach(function (column) {
            var active = column.getAttribute('data-pop-index') === idx;
            column.classList.remove('is-entering', 'is-entering-left', 'is-entering-right');

            if (isMobileTabs()) {
                if (active) {
                    column.removeAttribute('hidden');
                    column.classList.add('is-active');
                    if (direction === 'next') {
                        column.classList.add('is-entering-right');
                    } else if (direction === 'prev') {
                        column.classList.add('is-entering-left');
                    } else {
                        column.classList.add('is-entering');
                    }
                    window.setTimeout(function () {
                        column.classList.remove('is-entering', 'is-entering-left', 'is-entering-right');
                    }, 360);
                } else {
                    column.classList.remove('is-active');
                    column.setAttribute('hidden', 'hidden');
                }
            } else {
                column.classList.add('is-active');
                column.removeAttribute('hidden');
            }
        });

        currentIndex = numIdx;
    }

    function goNext() {
        if (tabs.length <= 1) {
            return;
        }
        var next = (currentIndex + 1) % tabs.length;
        activateCategory(String(next), 'next');
    }

    function goPrev() {
        if (tabs.length <= 1) {
            return;
        }
        var prev = (currentIndex - 1 + tabs.length) % tabs.length;
        activateCategory(String(prev), 'prev');
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var idx = getIndexFromAttr(tab);
            var dir = idx > currentIndex ? 'next' : (idx < currentIndex ? 'prev' : null);
            activateCategory(String(idx), dir);
        });
    });

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            var idx = getIndexFromAttr(dot);
            var dir = idx > currentIndex ? 'next' : (idx < currentIndex ? 'prev' : null);
            activateCategory(String(idx), dir);
        });
    });

    if (viewport) {
        viewport.addEventListener('touchstart', function (e) {
            if (!isMobileTabs() || e.touches.length !== 1) {
                return;
            }
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            isSwiping = false;
        }, { passive: true });

        viewport.addEventListener('touchmove', function (e) {
            if (!isMobileTabs()) {
                return;
            }
            var dx = Math.abs(e.touches[0].clientX - touchStartX);
            var dy = Math.abs(e.touches[0].clientY - touchStartY);
            if (dx > dy && dx > 12) {
                isSwiping = true;
            }
        }, { passive: true });

        viewport.addEventListener('touchend', function (e) {
            if (!isMobileTabs() || !isSwiping) {
                return;
            }
            var endX = e.changedTouches[0].clientX;
            var diff = touchStartX - endX;
            if (Math.abs(diff) < 48) {
                return;
            }
            if (diff > 0) {
                goNext();
            } else {
                goPrev();
            }
        }, { passive: true });
    }

    function syncLayout() {
        if (!isMobileTabs()) {
            columns.forEach(function (column) {
                column.removeAttribute('hidden');
                column.classList.add('is-active');
                column.classList.remove('is-entering', 'is-entering-left', 'is-entering-right');
            });
            return;
        }
        var activeTab = wrap.querySelector('.pop-cat-tab.is-active');
        var idx = activeTab ? activeTab.getAttribute('data-pop-index') : '0';
        currentIndex = parseInt(idx, 10);
        activateCategory(idx);
    }

    syncLayout();

    if (typeof mobileQuery.addEventListener === 'function') {
        mobileQuery.addEventListener('change', syncLayout);
    } else if (typeof mobileQuery.addListener === 'function') {
        mobileQuery.addListener(syncLayout);
    }
})();
