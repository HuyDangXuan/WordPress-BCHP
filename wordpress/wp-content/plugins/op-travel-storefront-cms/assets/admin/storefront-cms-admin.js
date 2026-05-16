(function () {
  function syncRouteTargetVisibility() {
    var select = document.getElementById('op-travel-storefront-route-type');
    var target = document.querySelector('.op-storefront-page-target');

    if (!select || !target) {
      return;
    }

    target.style.display = select.value === 'page' ? '' : 'none';
  }

  function createSection(container) {
    var template = container.querySelector('template[data-section-template]');
    var list = container.querySelector('[data-sections-list]');

    if (!template || !list) {
      return;
    }

    var html = template.innerHTML.replaceAll('__index__', String(Date.now()));
    list.insertAdjacentHTML('beforeend', html);
  }

  function moveSection(card, direction) {
    if (!card || !card.parentElement) {
      return;
    }

    if (direction === 'up' && card.previousElementSibling) {
      card.parentElement.insertBefore(card, card.previousElementSibling);
    }

    if (direction === 'down' && card.nextElementSibling) {
      card.parentElement.insertBefore(card.nextElementSibling, card);
    }
  }

  document.addEventListener('click', function (event) {
    var root = document.querySelector('[data-storefront-sections]');

    if (!root) {
      return;
    }

    if (event.target.matches('[data-add-section]')) {
      event.preventDefault();
      createSection(root);
      return;
    }

    var card = event.target.closest('[data-section-card]');

    if (!card) {
      return;
    }

    if (event.target.matches('[data-remove-section]')) {
      event.preventDefault();
      card.remove();
      return;
    }

    if (event.target.matches('[data-move-up]')) {
      event.preventDefault();
      moveSection(card, 'up');
      return;
    }

    if (event.target.matches('[data-move-down]')) {
      event.preventDefault();
      moveSection(card, 'down');
    }
  });

  document.addEventListener('change', function (event) {
    if (event.target && event.target.id === 'op-travel-storefront-route-type') {
      syncRouteTargetVisibility();
    }
  });

  syncRouteTargetVisibility();
}());
