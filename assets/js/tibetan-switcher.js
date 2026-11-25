/**
 * OmniTibet
 * Frontend Language Switcher Script
 *
 * @package Tibetan_Multilingual_Typography
 * @version 1.0.0
 */

(function () {
  'use strict';

  function initLanguageSwitchers() {
    const switchers = document.querySelectorAll('.tmt-language-switcher.tmt-dropdown');

    switchers.forEach(function (switcher) {
      const btn = switcher.querySelector('.tmt-switcher-current');
      const menu = switcher.querySelector('.tmt-switcher-menu');

      if (!btn || !menu) return;

      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const isOpen = switcher.classList.contains('tmt-open');
        closeAllSwitchers();

        if (!isOpen) {
          switcher.classList.add('tmt-open');
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    });

    // Close when clicking outside
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.tmt-language-switcher')) {
        closeAllSwitchers();
      }
    });

    // Handle radio button language switches
    document.querySelectorAll('.tmt-language-switcher.tmt-radio input[type="radio"]').forEach(function (radio) {
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
    document.querySelectorAll('.tmt-language-switcher.tmt-open').forEach(function (el) {
      el.classList.remove('tmt-open');
      const btn = el.querySelector('.tmt-switcher-current');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLanguageSwitchers);
  } else {
    initLanguageSwitchers();
  }
})();
