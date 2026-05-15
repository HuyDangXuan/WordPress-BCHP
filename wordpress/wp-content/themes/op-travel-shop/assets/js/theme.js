document.addEventListener('DOMContentLoaded', () => {
  const loadingClass = 'op-is-loading';
  const loadedClass = 'op-has-loaded';
  const minimumSkeletonDuration = 650;

  function shouldDisableControl(control) {
    if (!control || !('disabled' in control)) {
      return false;
    }

    if (control.matches('button[type="submit"], input[type="submit"], .single_add_to_cart_button')) {
      return false;
    }

    return true;
  }

  function setOpButtonLoading(control, isLoading) {
    if (!control) {
      return;
    }

    control.classList.toggle('op-button--loading', isLoading);
    control.setAttribute('aria-busy', String(isLoading));

    if (shouldDisableControl(control)) {
      control.disabled = isLoading;
    }
  }

  function markOpLoading(target, isLoading = true) {
    if (!target) {
      return;
    }

    target.classList.toggle(loadingClass, isLoading);
    target.setAttribute('aria-busy', String(isLoading));

    const controls = target.matches('button, input[type="submit"], .op-button, .button')
      ? [target]
      : Array.from(target.querySelectorAll('button, input[type="submit"], .op-button, .button'));

    controls.forEach((control) => setOpButtonLoading(control, isLoading));
  }

  function resolveSkeletonTarget(source) {
    const selector = source.getAttribute('data-op-skeleton-target');

    if (!selector) {
      return source;
    }

    if (selector === 'payment-state') {
      return document.querySelector('.op-thankyou-panel');
    }

    return document.querySelector(selector) || source;
  }

  function clearOpLoadingStates() {
    document.querySelectorAll(`.${loadingClass}, .op-button--loading`).forEach((node) => {
      node.classList.remove(loadingClass, 'op-button--loading');
      node.removeAttribute('aria-busy');

      if ('disabled' in node) {
        node.disabled = false;
      }
    });
  }

  function runAfterMinimumSkeletonDuration(startTime, callback) {
    const elapsed = Date.now() - startTime;
    const remaining = Math.max(0, minimumSkeletonDuration - elapsed);

    window.setTimeout(callback, remaining);
  }

  function initOpSkeletonImages() {
    const imageNodes = document.querySelectorAll(
      '.op-tour-card__media img, .woocommerce-product-gallery img, .op-gallery-grid img, .op-cart-tour-card__media img, .op-demo-qr__media img',
    );

    imageNodes.forEach((img) => {
      const wrapper = img.closest('.op-tour-card__media, .woocommerce-product-gallery, .op-gallery-grid, .op-cart-tour-card__media, .op-demo-qr__media') || img.parentElement;

      if (!wrapper) {
        return;
      }

      wrapper.classList.add('op-skeleton-media');
      const card = wrapper.closest('.op-tour-card, .op-cart-tour-card');
      const skeletonStartTime = Date.now();

      const markLoaded = () => {
        wrapper.classList.add(loadedClass);
        wrapper.classList.remove('op-skeleton');
        wrapper.classList.remove(loadingClass);

        if (card) {
          card.classList.add(loadedClass);
          card.classList.remove(loadingClass);
        }
      };

      wrapper.classList.add(loadingClass);
      if (card) {
        card.classList.add(loadingClass);
      }

      if (img.complete && img.naturalWidth > 0) {
        runAfterMinimumSkeletonDuration(skeletonStartTime, markLoaded);
        return;
      }

      img.addEventListener('load', () => runAfterMinimumSkeletonDuration(skeletonStartTime, markLoaded), { once: true });
      img.addEventListener('error', () => runAfterMinimumSkeletonDuration(skeletonStartTime, markLoaded), { once: true });
    });

    document.querySelectorAll('.op-tour-card, .op-cart-tour-card, .op-thankyou-booking-card').forEach((card) => {
      card.classList.add('op-card-enter');
    });
  }

  function initOpBookingForms() {
    const forms = document.querySelectorAll('.op-booking-panel form.cart');

    forms.forEach((form) => {
      const departureField = form.querySelector('select[name="op_departure_date"]');

      if (!departureField) {
        return;
      }

      const firstAvailableOption = Array.from(departureField.options).find((option) => option.value !== '');

      if (!firstAvailableOption) {
        return;
      }

      if (departureField.value === '') {
        departureField.value = firstAvailableOption.value;
      }

      form.addEventListener('submit', () => {
        if (departureField.value === '') {
          departureField.value = firstAvailableOption.value;
        }
      });
    });
  }

  function initOpCartSelection() {
    const radios = document.querySelectorAll('[data-op-cart-radio]');

    if (!radios.length) {
      return;
    }

    const selectedTourNode = document.querySelector('[data-op-cart-selected-tour]');
    const selectedSubtotalNodes = document.querySelectorAll('[data-op-cart-selected-subtotal]');
    const selectedTotalNodes = document.querySelectorAll('[data-op-cart-selected-total]');

    const syncSelectionSummary = (radio) => {
      if (!radio) {
        return;
      }

      const tourName = radio.getAttribute('data-op-cart-tour-name') || '';
      const subtotalHtml = radio.getAttribute('data-op-cart-subtotal-html') || '';
      const totalHtml = radio.getAttribute('data-op-cart-total-html') || subtotalHtml;

      if (selectedTourNode) {
        selectedTourNode.textContent = tourName;
      }

      selectedSubtotalNodes.forEach((node) => {
        node.innerHTML = subtotalHtml;
      });

      selectedTotalNodes.forEach((node) => {
        node.innerHTML = totalHtml;
      });
    };

    const initialRadio = Array.from(radios).find((radio) => radio.checked) || radios[0];

    if (initialRadio) {
      initialRadio.checked = true;
      syncSelectionSummary(initialRadio);
    }

    radios.forEach((radio) => {
      radio.addEventListener('change', () => {
        if (!radio.checked) {
          return;
        }

        syncSelectionSummary(radio);
      });
    });
  }

  function initOpPaymentStatusCheck() {
    const buttons = document.querySelectorAll('[data-op-payment-status-check]');
    const knownStates = ['pending', 'paid', 'failed', 'expired', 'cancelled'];

    if (!buttons.length || !window.fetch) {
      return;
    }

    const updatePaymentStateUi = (payload) => {
      const state = String(payload?.payment_status || 'pending').trim().toLowerCase();
      const stateLabel = payload?.state_label || state;
      const stateMessage = payload?.state_message || '';

      document.querySelectorAll('[data-op-payment-state-text], [data-op-payment-state-detail]').forEach((node) => {
        node.textContent = stateLabel;
      });

      document.querySelectorAll('[data-op-payment-state-message]').forEach((node) => {
        node.textContent = stateMessage;
      });

      document.querySelectorAll('[data-op-payment-state-pill]').forEach((node) => {
        node.textContent = stateLabel;
        knownStates.forEach((knownState) => {
          node.classList.remove(`op-status-pill--${knownState}`);
        });
        node.classList.add(`op-status-pill--${state}`);
      });
    };

    buttons.forEach((button) => {
      const root = button.closest('.op-demo-qr') || button.parentElement;
      const feedback = root ? root.querySelector('[data-op-payment-status-feedback]') : null;

      button.addEventListener('click', async () => {
        const statusUrl = button.getAttribute('data-op-status-url');

        if (!statusUrl) {
          return;
        }

        const requestUrl = new URL(statusUrl, window.location.origin);
        const orderKey = button.getAttribute('data-op-order-key');

        if (orderKey) {
          requestUrl.searchParams.set('order_key', orderKey);
        }

        setOpButtonLoading(button, true);
        if (feedback) {
          feedback.textContent = 'Đang kiểm tra trạng thái thanh toán...';
        }

        try {
          const response = await fetch(requestUrl.toString(), {
            method: 'GET',
            headers: {
              Accept: 'application/json',
            },
          });
          const payload = await response.json().catch(() => ({}));

          if (!response.ok) {
            throw new Error(payload?.message || 'Không thể kiểm tra trạng thái thanh toán lúc này.');
          }

          if (payload?.feedback_message) {
            payload.state_message = payload.feedback_message;
          }

          updatePaymentStateUi(payload);

          if (feedback) {
            feedback.textContent = payload?.state_message || 'Trạng thái thanh toán đã được cập nhật.';
          }
        } catch (error) {
          if (feedback) {
            feedback.textContent = error?.message || 'Không thể kiểm tra trạng thái thanh toán lúc này.';
          }
        } finally {
          setOpButtonLoading(button, false);
        }
      });
    });
  }

  function initOpLoadingForms() {
    const forms = document.querySelectorAll('[data-op-loading-form], .op-booking-panel form.cart');

    forms.forEach((form) => {
      form.addEventListener('submit', () => {
        markOpLoading(form, true);
        markOpLoading(resolveSkeletonTarget(form), true);
      });
    });

    document.querySelectorAll('[data-op-loading-link]').forEach((link) => {
      link.addEventListener('click', () => {
        markOpLoading(link, true);
        markOpLoading(resolveSkeletonTarget(link), true);
      });
    });
  }

  function initOpAuthPanelFocus() {
    const registerPanel = document.getElementById('op-register');

    if (!registerPanel) {
      return;
    }

    const params = new URLSearchParams(window.location.search);
    const shouldFocusRegister = params.get('op_auth') === 'register' || window.location.hash === '#op-register';

    if (!shouldFocusRegister) {
      return;
    }

    registerPanel.classList.add('is-targeted');
    window.setTimeout(() => {
      registerPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 120);
  }

  function initOpPasswordToggles() {
    document.querySelectorAll('.op-auth-form input[type="password"]').forEach((input) => {
      const row = input.closest('.form-row') || input.parentElement;

      if (!row || row.querySelector('.show-password-input')) {
        return;
      }

      const toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'show-password-input';
      toggle.setAttribute('aria-label', 'Hiện mật khẩu');
      toggle.addEventListener('click', () => {
        const isVisible = input.type === 'text';
        input.type = isVisible ? 'password' : 'text';
        toggle.classList.toggle('display-password', !isVisible);
        toggle.setAttribute('aria-label', isVisible ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
      });

      row.appendChild(toggle);
    });
  }

  function initOpAccountMenu() {
    const menuRoot = document.querySelector('.op-header-profile');

    if (!menuRoot) {
      return;
    }

    const trigger = menuRoot.querySelector('.op-header-profile__trigger');
    const menu = menuRoot.querySelector('.op-header-profile__menu');

    if (!trigger || !menu) {
      return;
    }

    const closeMenu = () => {
      menuRoot.classList.remove('is-open');
      menu.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
    };

    const openMenu = () => {
      menuRoot.classList.add('is-open');
      menu.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
    };

    trigger.addEventListener('click', () => {
      const shouldOpen = menu.hidden;

      if (shouldOpen) {
        openMenu();
        return;
      }

      closeMenu();
    });

    document.addEventListener('click', (event) => {
      if (!menuRoot.contains(event.target)) {
        closeMenu();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') {
        return;
      }

      closeMenu();
      trigger.focus();
    });

    closeMenu();
  }

  function initWooCommerceLoadingStates() {
    const orderReview = document.getElementById('order_review');
    const checkoutForm = document.querySelector('form.checkout');

    const setCheckoutLoading = (isLoading) => {
      markOpLoading(orderReview, isLoading);
      markOpLoading(checkoutForm, isLoading);
    };

    if (window.jQuery && document.body) {
      const $body = window.jQuery(document.body);

      $body.on('update_checkout', () => setCheckoutLoading(true));
      $body.on('updated_checkout checkout_error', () => setCheckoutLoading(false));
      $body.on('wc_fragments_refreshed updated_wc_div', () => {
        clearOpLoadingStates();
        initOpSkeletonImages();
      });
    }

    ['update_checkout'].forEach((eventName) => {
      document.body.addEventListener(eventName, () => setCheckoutLoading(true));
    });

    ['updated_checkout', 'checkout_error', 'wc_fragments_refreshed'].forEach((eventName) => {
      document.body.addEventListener(eventName, () => {
        setCheckoutLoading(false);
        initOpSkeletonImages();
      });
    });
  }

  initOpSkeletonImages();
  initOpBookingForms();
  initOpCartSelection();
  initOpPaymentStatusCheck();
  initOpLoadingForms();
  initOpAuthPanelFocus();
  initOpPasswordToggles();
  initOpAccountMenu();
  initWooCommerceLoadingStates();

  window.addEventListener('pageshow', () => {
    clearOpLoadingStates();
    initOpSkeletonImages();
  });

  /* =============================================
     Reveal animation (IntersectionObserver)
     ============================================= */
  const revealNodes = document.querySelectorAll('[data-reveal]');
  const hero = document.querySelector('.op-hero');

  if ('IntersectionObserver' in window && revealNodes.length) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.18 },
    );

    revealNodes.forEach((node) => observer.observe(node));
  } else {
    revealNodes.forEach((node) => node.classList.add('is-visible'));
  }

  /* =============================================
     Hero badge parallax
     ============================================= */
  if (hero) {
    const badge = hero.querySelector('.op-hero__badge');

    const onScroll = () => {
      const offset = Math.min(window.scrollY * 0.08, 28);
      if (badge) {
        badge.style.transform = `translateY(${offset}px)`;
      }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* =============================================
     Mobile menu toggle
     ============================================= */
  const toggle = document.querySelector('.op-mobile-toggle');
  const nav = document.getElementById('op-primary-nav');

  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const isOpen = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(isOpen));
    });
  }

  /* =============================================
     Detail tabs (single product)
     ============================================= */
  const tabsContainer = document.querySelector('.op-detail-tabs');

  if (tabsContainer) {
    const buttons = tabsContainer.querySelectorAll('.op-detail-tabs__btn');
    const panels = tabsContainer.querySelectorAll('.op-detail-tabs__panel');

    buttons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const target = btn.getAttribute('data-tab');

        buttons.forEach((b) => b.classList.remove('is-active'));
        panels.forEach((p) => p.classList.remove('is-active'));

        btn.classList.add('is-active');
        const panel = tabsContainer.querySelector(`[data-tab-panel="${target}"]`);
        if (panel) {
          panel.classList.add('is-active');
        }
      });
    });
  }
});
