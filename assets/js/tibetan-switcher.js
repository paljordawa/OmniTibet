/**
 * OmniTibet
 * Frontend Language Switcher Script
 *
 * @package OmniTibet
 * @version 1.0.0
 */

(function () {
  'use strict';

  function initLanguageSwitchers() {
    const switchers = document.querySelectorAll('.omniti-language-switcher.omniti-dropdown');

    switchers.forEach(function (switcher) {
      const btn = switcher.querySelector('.omniti-switcher-current');
      const menu = switcher.querySelector('.omniti-switcher-menu');

      if (!btn || !menu) return;

      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const isOpen = switcher.classList.contains('omniti-open');
        closeAllSwitchers();

        if (!isOpen) {
          switcher.classList.add('omniti-open');
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    });

    // Close when clicking outside
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.omniti-language-switcher')) {
        closeAllSwitchers();
      }
    });

    // Handle radio button language switches
    document.querySelectorAll('.omniti-language-switcher.omniti-radio input[type="radio"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        if (this.value && this.value !== window.location.href) {
          window.location.href = this.value;
        }
      });
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' || e.key === 'Esc') {
        closeAllSwitchers();
      }
    });
  }

  function closeAllSwitchers() {
    document.querySelectorAll('.omniti-language-switcher.omniti-open').forEach(function (el) {
      el.classList.remove('omniti-open');
      const btn = el.querySelector('.omniti-switcher-current');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLanguageSwitchers);
  } else {
    initLanguageSwitchers();
  }
})();
