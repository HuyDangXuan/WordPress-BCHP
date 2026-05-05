document.addEventListener('DOMContentLoaded', () => {
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
