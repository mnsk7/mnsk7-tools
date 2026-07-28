(function () {
  'use strict';

  var desktopMedia = window.matchMedia('(min-width: 1024px)');

  function init() {
    var body = document.body;
    var header = document.getElementById('masthead');
    if (!header || !header.classList.contains('mnsk7h')) return;

    var navigation = document.getElementById('mnsk7h-navigation');
    var menuToggle = header.querySelector('.mnsk7h__menu-toggle');
    var menuClose = header.querySelector('.mnsk7h__drawer-close');
    var searchToggle = header.querySelector('.mnsk7h__search-toggle');
    var searchInput = document.getElementById('mnsk7h-search-input');
    var cartDropdown = document.getElementById('mnsk7h-cart-dropdown');
    var promo = document.getElementById('mnsk7-promo-bar');
    var promoClose = promo ? promo.querySelector('.mnsk7h-promo__close') : null;
    var activeState = 'closed';
    var previousFocus = null;

    function isDesktop() {
      return desktopMedia.matches;
    }

    function setControlState(control, expanded) {
      if (!control) return;
      control.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      var label = control.getAttribute(expanded ? 'data-close-label' : 'data-open-label');
      if (label) control.setAttribute('aria-label', label);
    }

    function closeDetails() {
      if (!navigation) return;
      navigation.querySelectorAll('details[open]').forEach(function (details) {
        details.open = false;
      });
    }

    function render(nextState, restoreFocus) {
      activeState = nextState;
      var menuOpen = !isDesktop() && nextState === 'menu';
      var searchOpen = !isDesktop() && nextState === 'search';
      var cartOpen = isDesktop() && nextState === 'cart';

      if (navigation) navigation.classList.toggle('is-open', menuOpen);
      body.classList.toggle('mnsk7h-menu-open', menuOpen);
      body.classList.toggle('mnsk7h-search-open', searchOpen);
      setControlState(menuToggle, menuOpen);
      setControlState(searchToggle, searchOpen);

      var cartTrigger = header.querySelector('.mnsk7h__cart-trigger');
      if (cartTrigger) cartTrigger.setAttribute('aria-expanded', cartOpen ? 'true' : 'false');
      if (cartDropdown) cartDropdown.hidden = !cartOpen;

      if (!menuOpen) closeDetails();
      if (nextState !== 'closed') previousFocus = document.activeElement;

      if (restoreFocus && previousFocus && typeof previousFocus.focus === 'function') {
        previousFocus.focus();
        previousFocus = null;
      }
    }

    if (menuToggle) {
      menuToggle.addEventListener('click', function () {
        render(activeState === 'menu' ? 'closed' : 'menu');
        if (activeState === 'menu' && menuClose) menuClose.focus();
      });
    }

    if (menuClose) {
      menuClose.addEventListener('click', function () {
        render('closed', true);
      });
    }

    if (searchToggle) {
      searchToggle.addEventListener('click', function () {
        render(activeState === 'search' ? 'closed' : 'search');
        if (activeState === 'search' && searchInput) searchInput.focus();
      });
    }

    document.addEventListener('click', function (event) {
      var cartTrigger = event.target.closest('.mnsk7h__cart-trigger');
      if (cartTrigger && isDesktop()) {
        event.preventDefault();
        render(activeState === 'cart' ? 'closed' : 'cart');
        return;
      }

      if (activeState === 'search' && !header.contains(event.target)) {
        render('closed');
      } else if (activeState === 'cart' && !event.target.closest('.mnsk7h__cart')) {
        render('closed');
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && activeState !== 'closed') {
        event.preventDefault();
        render('closed', true);
        return;
      }

      if (event.key !== 'Tab' || activeState !== 'menu' || isDesktop() || !navigation) return;
      var focusable = navigation.querySelectorAll('a[href], summary, button:not([disabled])');
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

    if (navigation) {
      navigation.addEventListener('toggle', function (event) {
        var opened = event.target;
        if (!opened.open || !opened.parentElement) return;
        var siblings = opened.parentElement.parentElement ? opened.parentElement.parentElement.children : [];
        Array.prototype.forEach.call(siblings, function (sibling) {
          var details = sibling.querySelector(':scope > details');
          if (details && details !== opened) details.open = false;
        });
      }, true);
    }

    var shopDetails = header.querySelector('.mnsk7h__shop');
    var closeTimer;
    if (shopDetails) {
      shopDetails.addEventListener('mouseenter', function () {
        if (!isDesktop()) return;
        window.clearTimeout(closeTimer);
        shopDetails.open = true;
      });
      shopDetails.addEventListener('mouseleave', function () {
        if (!isDesktop()) return;
        closeTimer = window.setTimeout(function () {
          shopDetails.open = false;
        }, 180);
      });
    }

    function handleBreakpoint() {
      render('closed');
      closeDetails();
    }

    if (typeof desktopMedia.addEventListener === 'function') {
      desktopMedia.addEventListener('change', handleBreakpoint);
    } else {
      desktopMedia.addListener(handleBreakpoint);
    }

    function syncPromo() {
      if (!promo || promo.hidden) {
        body.classList.remove('mnsk7h-has-promo');
        body.style.removeProperty('--mnsk7h-promo-height');
        return;
      }
      body.classList.add('mnsk7h-has-promo');
      body.style.setProperty('--mnsk7h-promo-height', promo.getBoundingClientRect().height + 'px');
    }

    if (promo) {
      try {
        promo.hidden = window.sessionStorage.getItem('mnsk7-promo-dismissed') === '1';
      } catch (error) {
        promo.hidden = false;
      }
    }
    syncPromo();

    if (promoClose) {
      promoClose.addEventListener('click', function () {
        promo.hidden = true;
        try {
          window.sessionStorage.setItem('mnsk7-promo-dismissed', '1');
        } catch (error) {
          // Hiding the promo remains valid when storage is unavailable.
        }
        syncPromo();
      });
    }

    window.addEventListener('resize', syncPromo, { passive: true });
    window.addEventListener('pageshow', function () {
      render('closed');
      syncPromo();
    });

    if (window.jQuery) {
      window.jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart', function () {
        render('closed');
      });
    }

    render('closed');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
