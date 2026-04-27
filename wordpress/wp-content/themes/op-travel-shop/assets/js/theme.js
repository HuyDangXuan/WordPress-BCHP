document.addEventListener('DOMContentLoaded', () => {
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
});
