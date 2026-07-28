(function () {
  'use strict';

  var DESKTOP_QUERY = '(min-width: 1024px)';
  var desktopMedia = window.matchMedia(DESKTOP_QUERY);
  var config = window.mnsk7HeaderConfig || {};

  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback, { once: true });
      return;
    }
    callback();
  }

  ready(function () {
    var body = document.body;
    var header = document.getElementById('masthead');
    var nav = header ? header.querySelector('.mnsk7-header__nav') : null;
    var menu = document.getElementById('mnsk7-primary-menu');
    var menuToggle = header ? header.querySelector('.mnsk7-header__menu-toggle') : null;
    var menuClose = menu ? menu.querySelector('.mnsk7-drawer__close') : null;
    var searchToggle = header ? header.querySelector('.mnsk7-header__search-toggle') : null;
    var search = document.getElementById('mnsk7-header-search');
    var searchInput = document.getElementById('mnsk7-header-search-input');
    var cart = header ? header.querySelector('.mnsk7-header__cart') : null;
    var cartTrigger = cart ? cart.querySelector('.mnsk7-header__cart-trigger') : null;
    var cartDropdown = document.getElementById('mnsk7-header-cart-dropdown');
    var promo = document.getElementById('mnsk7-promo-bar');
    var promoClose = promo ? promo.querySelector('.mnsk7-promo-bar__close') : null;
    var state = 'closed';
    var previousFocus = null;

    function isDesktop() {
      return desktopMedia.matches;
    }

    function setExpanded(control, expanded) {
      if (!control) return;
      control.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      var label = control.getAttribute(expanded ? 'data-close-label' : 'data-open-label');
      if (label) control.setAttribute('aria-label', label);
    }

    function resetAccordions() {
      if (!menu) return;
      menu.querySelectorAll('.is-mobile-expanded').forEach(function (item) {
        item.classList.remove('is-mobile-expanded');
      });
      menu.querySelectorAll('.mnsk7-drawer__submenu-toggle').forEach(function (button) {
        button.setAttribute('aria-expanded', 'false');
      });
      var shopLink = menu.querySelector('.mnsk7-menu-item-sklep');
      if (shopLink) shopLink.setAttribute('aria-expanded', 'false');
    }

    function closeMegamenu() {
      if (!menu) return;
      menu.querySelectorAll('.mnsk7-megamenu-open').forEach(function (item) {
        item.classList.remove('mnsk7-megamenu-open');
      });
      var shopLink = menu.querySelector('.mnsk7-menu-item-sklep');
      if (shopLink) shopLink.setAttribute('aria-expanded', 'false');
    }

    function renderState(nextState, options) {
      options = options || {};
      state = nextState;

      var menuOpen = !isDesktop() && state === 'menu';
      var searchOpen = !isDesktop() && state === 'search';
      var cartOpen = isDesktop() && state === 'cart';

      if (nav) nav.classList.toggle('is-open', menuOpen);
      body.classList.toggle('mnsk7-menu-open', menuOpen);
      body.classList.toggle('mnsk7-search-open', searchOpen);
      setExpanded(menuToggle, menuOpen);
      setExpanded(searchToggle, searchOpen);

      if (search) {
        search.hidden = isDesktop() ? false : !searchOpen;
        search.setAttribute('aria-hidden', searchOpen || isDesktop() ? 'false' : 'true');
      }

      if (cart) cart.classList.toggle('is-open', cartOpen);
      if (cartTrigger) cartTrigger.setAttribute('aria-expanded', cartOpen ? 'true' : 'false');
      if (cartDropdown) {
        cartDropdown.hidden = !cartOpen;
        cartDropdown.setAttribute('aria-hidden', cartOpen ? 'false' : 'true');
      }

      if (!menuOpen) resetAccordions();
      if (!isDesktop()) closeMegamenu();

      if (menuOpen || searchOpen || cartOpen) {
        previousFocus = document.activeElement;
      } else if (options.restoreFocus && previousFocus && typeof previousFocus.focus === 'function') {
        previousFocus.focus();
        previousFocus = null;
      }
    }

    function toggleState(name) {
      renderState(state === name ? 'closed' : name);
    }

    if (menuToggle) {
      menuToggle.addEventListener('click', function () {
        if (isDesktop()) return;
        toggleState('menu');
        if (state === 'menu' && menuClose) menuClose.focus();
      });
    }

    if (menuClose) {
      menuClose.addEventListener('click', function () {
        renderState('closed', { restoreFocus: true });
      });
    }

    if (searchToggle) {
      searchToggle.addEventListener('click', function () {
        if (isDesktop()) return;
        toggleState('search');
        if (state === 'search' && searchInput) searchInput.focus();
      });
    }

    if (cartTrigger) {
      cartTrigger.addEventListener('click', function (event) {
        if (!isDesktop()) return;
        event.preventDefault();
        toggleState('cart');
        if (state === 'cart' && cartDropdown) cartDropdown.focus();
      });
    }

    if (menu) {
      menu.addEventListener('click', function (event) {
        var button = event.target.closest('.mnsk7-drawer__submenu-toggle');
        if (!button || isDesktop()) return;

        var owner = button.parentElement;
        if (!owner) return;
        var expanded = button.getAttribute('aria-expanded') === 'true';

        Array.prototype.forEach.call(owner.parentElement.children, function (sibling) {
          if (sibling === owner || !sibling.classList) return;
          sibling.classList.remove('is-mobile-expanded');
          var siblingButton = sibling.querySelector(':scope > .mnsk7-drawer__submenu-toggle');
          if (siblingButton) siblingButton.setAttribute('aria-expanded', 'false');
        });

        owner.classList.toggle('is-mobile-expanded', !expanded);
        button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        var ownerLink = owner.querySelector(':scope > a[aria-haspopup="true"]');
        if (ownerLink) ownerLink.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      });
    }

    document.addEventListener('click', function (event) {
      if (state === 'search' && header && !header.contains(event.target)) {
        renderState('closed');
        return;
      }
      if (state === 'cart' && cart && !cart.contains(event.target)) {
        renderState('closed');
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && state !== 'closed') {
        event.preventDefault();
        renderState('closed', { restoreFocus: true });
        return;
      }

      if (event.key !== 'Tab' || state !== 'menu' || isDesktop() || !menu) return;
      var focusable = menu.querySelectorAll('a[href], button:not([disabled])');
      if (!focusable.length) return;
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    function handleBreakpointChange() {
      renderState('closed');
      closeMegamenu();
    }

    if (typeof desktopMedia.addEventListener === 'function') {
      desktopMedia.addEventListener('change', handleBreakpointChange);
    } else {
      desktopMedia.addListener(handleBreakpointChange);
    }

    function pluralizeSubcategories(count) {
      if (count === 1) return config.subcategoryOne || 'podkategoria';
      var last = count % 10;
      var lastTwo = count % 100;
      if (last >= 2 && last <= 4 && !(lastTwo >= 12 && lastTwo <= 14)) {
        return config.subcategoryFew || 'podkategorie';
      }
      return config.subcategoryMany || 'podkategorii';
    }

    function initDesktopMegamenu() {
      if (!menu) return;
      var shopItem = menu.querySelector('.mnsk7-megamenu-parent');
      var panel = shopItem ? shopItem.querySelector(':scope > .mnsk7-megamenu') : null;
      var shopLink = shopItem ? shopItem.querySelector(':scope > .mnsk7-menu-item-sklep') : null;
      var cards = panel ? panel.querySelector('.mnsk7-megamenu__cards') : null;
      var cardsList = cards ? cards.querySelector(':scope > .mnsk7-megamenu__cards-list') : null;
      var columns = cardsList ? Array.prototype.slice.call(cardsList.querySelectorAll(':scope > .mnsk7-megamenu__col')) : [];
      var paneHeader = cards ? cards.querySelector(':scope > .mnsk7-megamenu__pane-header') : null;
      var paneTitle = paneHeader ? paneHeader.querySelector('.mnsk7-megamenu__pane-header-title') : null;
      var paneCount = paneHeader ? paneHeader.querySelector('.mnsk7-megamenu__pane-header-all') : null;
      var cta = cards ? cards.querySelector(':scope > .mnsk7-megamenu__cta') : null;
      var ctaLink = cta ? cta.querySelector('.mnsk7-megamenu__cta-link') : null;
      var openTimer;
      var closeTimer;
      var switchTimer;

      if (!shopItem || !panel || !columns.length) return;

      columns.forEach(function (column) {
        var title = column.querySelector(':scope > .mnsk7-megamenu__col-title');
        if (title && title.tagName !== 'A') title.setAttribute('tabindex', '0');
      });

      function activate(column) {
        if (!column) return;
        columns.forEach(function (item) {
          item.classList.toggle('is-active', item === column);
        });

        var title = column.querySelector(':scope > .mnsk7-megamenu__col-title');
        var list = column.querySelector(':scope > .mnsk7-megamenu__list');
        var name = title ? (title.textContent || '').trim() : '';
        var href = title && title.getAttribute ? title.getAttribute('href') : '';
        var count = list ? list.querySelectorAll(':scope > li').length : 0;

        if (paneHeader) {
          paneHeader.hidden = !count;
          if (count && href) paneHeader.setAttribute('href', href);
          else paneHeader.removeAttribute('href');
        }
        if (paneTitle) paneTitle.textContent = name;
        if (paneCount) paneCount.textContent = count ? count + ' ' + pluralizeSubcategories(count) : '';

        if (cta && ctaLink && href) {
          cta.hidden = false;
          cta.classList.toggle('mnsk7-megamenu__cta--leaf', !count);
          ctaLink.setAttribute('href', href);
          ctaLink.textContent = (config.viewAllPrefix || 'Zobacz wszystkie produkty w') + ' „' + name + '”';
        } else if (cta) {
          cta.hidden = true;
        }
      }

      function activateDefault() {
        var active = cardsList.querySelector('.mnsk7-megamenu__col.is-active');
        if (active) return;
        var firstWithChildren = columns.find(function (column) {
          return column.querySelector(':scope > .mnsk7-megamenu__list');
        });
        activate(firstWithChildren || columns[0]);
      }

      function setOpen(open) {
        if (!isDesktop()) open = false;
        shopItem.classList.toggle('mnsk7-megamenu-open', open);
        if (shopLink) shopLink.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) activateDefault();
      }

      shopItem.addEventListener('mouseenter', function () {
        if (!isDesktop()) return;
        window.clearTimeout(closeTimer);
        openTimer = window.setTimeout(function () {
          setOpen(true);
        }, 300);
      });

      shopItem.addEventListener('mouseleave', function () {
        window.clearTimeout(openTimer);
        closeTimer = window.setTimeout(function () {
          setOpen(false);
        }, 150);
      });

      shopItem.addEventListener('focusin', function () {
        if (!isDesktop()) return;
        window.clearTimeout(closeTimer);
        setOpen(true);
      });

      shopItem.addEventListener('focusout', function () {
        window.setTimeout(function () {
          if (isDesktop() && !shopItem.contains(document.activeElement)) setOpen(false);
        }, 0);
      });

      columns.forEach(function (column) {
        var title = column.querySelector(':scope > .mnsk7-megamenu__col-title');
        column.addEventListener('mouseenter', function () {
          if (!isDesktop() || column.classList.contains('is-active')) return;
          window.clearTimeout(switchTimer);
          switchTimer = window.setTimeout(function () {
            activate(column);
          }, 120);
        });
        column.addEventListener('mouseleave', function () {
          window.clearTimeout(switchTimer);
        });
        if (title) {
          title.addEventListener('focus', function () {
            if (isDesktop()) activate(column);
          });
        }
      });

      cardsList.addEventListener('keydown', function (event) {
        if (!isDesktop()) return;
        var titles = columns.map(function (column) {
          return column.querySelector(':scope > .mnsk7-megamenu__col-title');
        }).filter(Boolean);
        var index = titles.indexOf(document.activeElement);

        if (event.key === 'ArrowDown' && index > -1) {
          event.preventDefault();
          titles[(index + 1) % titles.length].focus();
        } else if (event.key === 'ArrowUp' && index > -1) {
          event.preventDefault();
          titles[(index - 1 + titles.length) % titles.length].focus();
        } else if (event.key === 'ArrowRight') {
          var active = cardsList.querySelector('.mnsk7-megamenu__col.is-active');
          var target = active ? active.querySelector('.mnsk7-megamenu__list a') : null;
          if (!target && cta && !cta.hidden) target = ctaLink;
          if (target) {
            event.preventDefault();
            target.focus();
          }
        } else if (event.key === 'ArrowLeft') {
          var activeColumn = cardsList.querySelector('.mnsk7-megamenu__col.is-active');
          var activeTitle = activeColumn ? activeColumn.querySelector(':scope > .mnsk7-megamenu__col-title') : null;
          if (activeTitle) {
            event.preventDefault();
            activeTitle.focus();
          }
        }
      });

      document.addEventListener('click', function (event) {
        if (isDesktop() && !shopItem.contains(event.target)) setOpen(false);
      });
    }

    initDesktopMegamenu();

    function syncPromo() {
      if (!promo || promo.hidden) {
        body.classList.remove('mnsk7-has-promo');
        body.style.removeProperty('--mnsk7-promo-h');
        return;
      }
      body.classList.add('mnsk7-has-promo');
      body.style.setProperty('--mnsk7-promo-h', promo.getBoundingClientRect().height + 'px');
    }

    if (promo) {
      try {
        if (window.sessionStorage.getItem('mnsk7-promo-dismissed') === '1') promo.hidden = true;
      } catch (error) {
        // Storage may be unavailable; the promo remains visible.
      }
    }
    syncPromo();

    if (promoClose) {
      promoClose.addEventListener('click', function () {
        promo.hidden = true;
        try {
          window.sessionStorage.setItem('mnsk7-promo-dismissed', '1');
        } catch (error) {
          // Storage may be unavailable; hiding still works for the current page.
        }
        syncPromo();
      });
    }

    window.addEventListener('resize', syncPromo, { passive: true });

    function syncScrolledHeader() {
      if (header) header.classList.toggle('mnsk7-header--scrolled', window.scrollY > 24);
    }

    window.addEventListener('scroll', syncScrolledHeader, { passive: true });
    syncScrolledHeader();

    if (window.jQuery) {
      window.jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart', function () {
        renderState('closed');
      });
    }

    window.addEventListener('pageshow', function () {
      renderState('closed');
      syncPromo();
      syncScrolledHeader();
    });

    renderState('closed');
  });
})();
