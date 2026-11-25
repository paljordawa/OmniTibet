/**
 * OmniTibet
 * Real-time Extended Wylie to Tibetan Unicode Converter
 *
 * @package Tibetan_Multilingual_Typography
 * @version 1.0.0
 */

(function () {
  'use strict';
  // Base Tibetan consonant mappings (Unicode hex values)
  const TIB_BASE = {
    'k': 0x0F40, 'kh': 0x0F41, 'g': 0x0F42, 'gh': 0x0F43, 'ng': 0x0F44,
    'c': 0x0F45, 'ch': 0x0F46, 'j': 0x0F47, 'ny': 0x0F49,
    'T': 0x0F4A, 'Th': 0x0F4B, 'D': 0x0F4C, 'Dh': 0x0F4D, 'N': 0x0F4E,
    't': 0x0F4F, 'th': 0x0F50, 'd': 0x0F51, 'dh': 0x0F52, 'n': 0x0F53,
    'p': 0x0F54, 'ph': 0x0F55, 'b': 0x0F56, 'bh': 0x0F57, 'm': 0x0F58,
    'ts': 0x0F59, 'tsh': 0x0F5A, 'dz': 0x0F5B, 'dzh': 0x0F5C, 'w': 0x0F5D,
    'zh': 0x0F5E, 'z': 0x0F5F, "'": 0x0F60, 'y': 0x0F61,
    'r': 0x0F62, 'l': 0x0F63, 'sh': 0x0F64, 'Sh': 0x0F65, 's': 0x0F66,
    'h': 0x0F67, 'a': 0x0F68
  };

  // Vowel signs in Tibetan script
  const VOWEL_MARKS = {
    'a': '',
    'i': '\u0F72',
    'u': '\u0F74',
    'e': '\u0F7A',
    'o': '\u0F7C',
    'A': '\u0F71',
    'I': '\u0F71\u0F72',
    'U': '\u0F71\u0F74',
    'ai': '\u0F7B',
    'au': '\u0F7D',
    '-i': '\u0F80',
    '-I': '\u0F71\u0F80'
  };

  // Tibetan punctuation and special symbols
  const PUNCTUATION = {
    ' ': '\u0F0B', // space -> tsheg ་
    '*': '\u0F0C', // tsheg bstar
    '/': '\u0F0D', // shad །
    '//': '\u0F0E', // nyis shad ༎
    ';': '\u0F0F', // rinchen spungs shad
    '@': '\u0F04', // yig mgo ༄
    '#': '\u0F05', // ༅
    '$': '\u0F06', // ༆
    '%': '\u0F07', // ༇
    '!': '\u0F14', // gter tsheg ༔
    ':': '\u0F14'
  };

  // Tibetan numerals 0-9
  const NUMERALS = {
    '0': '\u0F20', '1': '\u0F21', '2': '\u0F22', '3': '\u0F23', '4': '\u0F24',
    '5': '\u0F25', '6': '\u0F26', '7': '\u0F27', '8': '\u0F28', '9': '\u0F29'
  };

  // Consonants list sorted descending by length
  const PURE_CONSONANTS = [
    'kh', 'gh', 'ng', 'ch', 'ny', 'Th', 'Dh', 'th', 'dh', 'ph', 'bh',
    'ts', 'tsh', 'dz', 'dzh', 'zh', 'Sh', 'sh',
    'k', 'g', 'c', 'j', 'T', 'D', 'N', 't', 'd', 'n', 'p', 'b', 'm',
    'w', 'z', "'", 'y', 'r', 'l', 's', 'h'
  ];
  PURE_CONSONANTS.sort((a, b) => b.length - a.length);

  const VOWEL_KEYS = ['-I', '-i', 'ai', 'au', 'A', 'I', 'U', 'i', 'u', 'e', 'o', 'a'];
  VOWEL_KEYS.sort((a, b) => b.length - a.length);

  function getBase(c) {
    return TIB_BASE[c] ? String.fromCharCode(TIB_BASE[c]) : '';
  }

  function getSubjoined(c) {
    return TIB_BASE[c] ? String.fromCharCode(TIB_BASE[c] + 0x50) : '';
  }

  function isPrefix(c) {
    return ['g', 'd', 'b', 'm', "'"].includes(c);
  }

  function isValidSubjoined(root, sub) {
    if (sub === 'y') return ['k', 'kh', 'g', 'p', 'ph', 'b', 'm', 'r', 's'].includes(root);
    if (sub === 'r') return ['k', 'kh', 'g', 't', 'th', 'd', 'p', 'ph', 'b', 'm', 'sh', 's', 'h'].includes(root);
    if (sub === 'l') return ['k', 'g', 'b', 'z', 'r', 's'].includes(root);
    if (sub === 'w') return true;
    return false;
  }

  /**
   * Render pre-vowel consonant cluster
   */
  function renderCluster(list) {
    const c1 = list[0];
    const c2 = list[1];
    let hasPrefix = false;

    if (isPrefix(c1)) {
      if (list.length === 2) {
        if (['y', 'r', 'l', 'w'].includes(c2) && isValidSubjoined(c1, c2)) {
          hasPrefix = false;
        } else {
          hasPrefix = true;
        }
      } else {
        if (['r', 'l', 's'].includes(c1) && c1 !== 'b') {
          hasPrefix = false;
        } else {
          hasPrefix = true;
        }
      }
    }

    let out = '';
    let stackStart = 0;
    if (hasPrefix) {
      out += getBase(c1);
      stackStart = 1;
    }

    for (let i = stackStart; i < list.length; i++) {
      if (i === stackStart) {
        out += getBase(list[i]);
      } else {
        out += getSubjoined(list[i]);
      }
    }

    return out;
  }

  /**
   * Convert an individual Wylie syllable to Tibetan Unicode
   */
  function convertSyllable(syl) {
    if (!syl) return '';

    // Check numerals
    if (/^\d+$/.test(syl)) {
      return syl.split('').map(d => NUMERALS[d] || d).join('');
    }

    // Explicit stack with '+' e.g. k+Sha
    if (syl.includes('+')) {
      const parts = syl.split('+');
      let res = convertSyllable(parts[0]);
      for (let i = 1; i < parts.length; i++) {
        let p = parts[i];
        let subCons = null;
        for (const c of PURE_CONSONANTS) {
          if (p.startsWith(c)) {
            subCons = c;
            break;
          }
        }
        if (subCons) {
          res += getSubjoined(subCons);
          res += convertSyllable(p.substring(subCons.length));
        } else {
          res += p;
        }
      }
      return res;
    }

    // Explicit dot e.g. g.yag
    if (syl.includes('.')) {
      return syl.split('.').map(convertSyllable).join('');
    }

    let str = syl;
    let preCons = [];
    let vowel = null;
    let postCons = [];
    let diacritics = [];

    while (str.length > 0) {
      // Diacritics
      if (str.startsWith('~M`')) { diacritics.push('\u0F82'); str = str.substring(3); continue; }
      if (str.startsWith('~M')) { diacritics.push('\u0F83'); str = str.substring(2); continue; }
      if (str[0] === 'M') { diacritics.push('\u0F7E'); str = str.substring(1); continue; }
      if (str[0] === 'H') { diacritics.push('\u0F7F'); str = str.substring(1); continue; }

      // Check vowels
      let vMatch = null;
      if (vowel === null) {
        for (const vk of VOWEL_KEYS) {
          if (str.startsWith(vk)) {
            vMatch = vk;
            break;
          }
        }
      }

      if (vMatch !== null) {
        vowel = vMatch;
        str = str.substring(vMatch.length);
        continue;
      }

      // Check consonant
      let cMatch = null;
      for (const c of PURE_CONSONANTS) {
        if (str.startsWith(c)) {
          cMatch = c;
          break;
        }
      }

      if (cMatch !== null) {
        if (vowel === null) {
          preCons.push(cMatch);
        } else {
          postCons.push(cMatch);
        }
        str = str.substring(cMatch.length);
        continue;
      }

      // Other character fallback
      if (vowel === null) {
        preCons.push(str[0]);
      } else {
        postCons.push(str[0]);
      }
      str = str.substring(1);
    }

    // If no explicit vowel was found, default inherent 'a'
    if (vowel === null) {
      vowel = 'a';
      const SUFFIX_SET = new Set(['g', 'ng', 'd', 'n', 'b', 'm', "'", 'r', 'l', 's']);
      const POST_SUFFIX_SET = new Set(['d', 's']);

      if (preCons.length === 2) {
        const [c1, c2] = preCons;
        if (['y', 'r', 'l', 'w'].includes(c2) && isValidSubjoined(c1, c2)) {
          // Keep stack
        } else if (SUFFIX_SET.has(c2) && !isPrefix(c1)) {
          preCons = [c1];
          postCons = [c2];
        } else if (isPrefix(c1) && SUFFIX_SET.has(c2)) {
          if (c1 === 'b' && c2 === 's') {
            preCons = [c1];
            postCons = [c2];
          } else {
            preCons = [c1, c2];
          }
        }
      } else if (preCons.length === 3) {
        const [c1, c2, c3] = preCons;
        if (['y', 'r', 'l', 'w'].includes(c3) && isValidSubjoined(c2, c3)) {
          // Keep preCons e.g. bkra
        } else if (SUFFIX_SET.has(c3) && ['y', 'r', 'l', 'w'].includes(c2)) {
          preCons = [c1, c2];
          postCons = [c3];
        } else if (isPrefix(c1) && SUFFIX_SET.has(c3)) {
          preCons = [c1, c2];
          postCons = [c3];
        } else if (SUFFIX_SET.has(c2) && POST_SUFFIX_SET.has(c3)) {
          preCons = [c1];
          postCons = [c2, c3];
        }
      } else if (preCons.length >= 4) {
        const last = preCons[preCons.length - 1];
        const secondLast = preCons[preCons.length - 2];
        if (POST_SUFFIX_SET.has(last) && SUFFIX_SET.has(secondLast)) {
          postCons = [secondLast, last];
          preCons = preCons.slice(0, preCons.length - 2);
        } else if (SUFFIX_SET.has(last)) {
          postCons = [last];
          preCons = preCons.slice(0, preCons.length - 1);
        }
      }
    }

    let result = '';
    if (preCons.length === 0) {
      result += getBase('a');
    } else if (preCons.length === 1) {
      result += getBase(preCons[0]);
    } else {
      result += renderCluster(preCons);
    }

    // Add vowel sign
    result += VOWEL_MARKS[vowel] || '';

    // Diacritics on vowel/root
    for (const d of diacritics) {
      if (d === '\u0F7E' || d === '\u0F83' || d === '\u0F82') {
        result += d;
      }
    }

    // Add suffixes as unstacked base consonants
    for (const sc of postCons) {
      result += getBase(sc);
    }

    // Post diacritics
    for (const d of diacritics) {
      if (d === '\u0F7F') {
        result += d;
      }
    }

    return result;
  }

  /**
   * Convert arbitrary text containing Wylie to Tibetan
   */
  function wylieToTibetan(text) {
    if (!text) return '';
    let out = '';
    let buffer = '';

    for (let i = 0; i < text.length; i++) {
      const ch = text[i];
      const nextCh = text[i + 1] || '';

      // Handle double slash '//' -> nyis shad
      if (ch === '/' && nextCh === '/') {
        if (buffer) {
          out += convertSyllable(buffer);
          buffer = '';
        }
        out += PUNCTUATION['//'];
        i++;
        continue;
      }

      // Handle single punctuation
      if (PUNCTUATION[ch]) {
        if (buffer) {
          out += convertSyllable(buffer);
          buffer = '';
        }
        out += PUNCTUATION[ch];
        continue;
      }

      // Whitespace / Newline
      if (ch === '\n' || ch === '\r' || ch === '\t') {
        if (buffer) {
          out += convertSyllable(buffer);
          buffer = '';
        }
        out += ch;
        continue;
      }

      buffer += ch;
    }

    if (buffer) {
      out += convertSyllable(buffer);
    }

    return out;
  }

  // Expose on global window object
  window.TibetanWylieConverter = {
    convertSyllable: convertSyllable,
    toTibetan: wylieToTibetan,
    isEnabled: true
  };

  /**
   * Real-time Editor Keystroke Interceptor
   */
  let wylieActive = true;
  const STORAGE_KEY = 'tmt_wylie_active';

  // Restore saved state
  if (typeof localStorage !== 'undefined') {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved !== null) {
      wylieActive = saved === '1';
    }
  }

  /**
   * Create Floating / Header Toggle Button for Wylie Converter
   */
  function initToggleButton() {
    if (document.getElementById('tmt-wylie-toggle-badge')) return;

    const badge = document.createElement('div');
    badge.id = 'tmt-wylie-toggle-badge';
    badge.className = 'tmt-wylie-badge ' + (wylieActive ? 'active' : 'inactive');
    badge.title = 'Tibetan Wylie Input (Shortcut: Alt + W)';
    badge.setAttribute('role', 'button');
    badge.setAttribute('tabindex', '0');

    badge.innerHTML = `
      <span class="tmt-badge-icon">བོད</span>
      <span class="tmt-badge-text">Wylie: <strong class="tmt-status">${wylieActive ? 'ON' : 'OFF'}</strong></span>
      <span class="tmt-badge-hotkey">Alt+W</span>
    `;

    badge.addEventListener('click', function (e) {
      e.preventDefault();
      toggleWylieState();
    });

    document.body.appendChild(badge);
  }

  function toggleWylieState() {
    wylieActive = !wylieActive;
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem(STORAGE_KEY, wylieActive ? '1' : '0');
    }
    const badge = document.getElementById('tmt-wylie-toggle-badge');
    if (badge) {
      badge.className = 'tmt-wylie-badge ' + (wylieActive ? 'active' : 'inactive');
      const statusEl = badge.querySelector('.tmt-status');
      if (statusEl) {
        statusEl.textContent = wylieActive ? 'ON' : 'OFF';
      }
    }
  }

  // Keyboard shortcut: Alt+W or Ctrl+M
  document.addEventListener('keydown', function (e) {
    if ((e.altKey && (e.key === 'w' || e.key === 'W')) || (e.ctrlKey && (e.key === 'm' || e.key === 'M'))) {
      e.preventDefault();
      toggleWylieState();
    }
  });

  /**
   * Handle text insertion into input or textarea
   */
  function handleInputKeydown(e) {
    if (!wylieActive) return;
    const target = e.target;
    if (!target) return;

    const isInput = target.tagName === 'INPUT' && (target.type === 'text' || target.type === 'search');
    const isTextarea = target.tagName === 'TEXTAREA';

    if (!isInput && !isTextarea) return;

    // Trigger conversion on Space, '/', ';', '!', or Enter
    const triggerKeys = [' ', '/', ';', '!', 'Enter'];
    if (!triggerKeys.includes(e.key)) return;

    const start = target.selectionStart;
    const end = target.selectionEnd;
    if (start === null || start !== end) return;

    const val = target.value;
    // Extract current word up to cursor
    const beforeCursor = val.substring(0, start);
    const match = beforeCursor.match(/([a-zA-Z0-9'+.~`-]+)$/);

    if (match) {
      e.preventDefault();
      const wylieWord = match[1];
      const converted = convertSyllable(wylieWord);

      // Determine punctuation to append
      let punct = '';
      if (e.key === ' ') {
        punct = '\u0F0B'; // tsheg ་
      } else if (e.key === '/') {
        // If preceding character is already shad, make it nyis shad ༎
        if (beforeCursor.endsWith('\u0F0D')) {
          // Replace previous shad with nyis shad
          target.value = val.substring(0, start - 1) + '\u0F0E' + val.substring(end);
          target.setSelectionRange(start, start);
          dispatchInputEvents(target);
          return;
        } else {
          punct = '\u0F0D'; // shad །
        }
      } else if (e.key === ';') {
        punct = '\u0F0F';
      } else if (e.key === '!') {
        punct = '\u0F14';
      } else if (e.key === 'Enter') {
        punct = '\n';
      }

      const wordStart = start - wylieWord.length;
      const newVal = val.substring(0, wordStart) + converted + punct + val.substring(end);
      target.value = newVal;

      const newCursorPos = wordStart + converted.length + punct.length;
      target.setSelectionRange(newCursorPos, newCursorPos);
      dispatchInputEvents(target);
    } else if (e.key === ' ') {
      // Space without preceding word -> insert tsheg ་
      e.preventDefault();
      const newVal = val.substring(0, start) + '\u0F0B' + val.substring(end);
      target.value = newVal;
      target.setSelectionRange(start + 1, start + 1);
      dispatchInputEvents(target);
    } else if (e.key === '/') {
      e.preventDefault();
      const punct = beforeCursor.endsWith('\u0F0D') ? '\u0F0E' : '\u0F0D';
      if (punct === '\u0F0E') {
        target.value = val.substring(0, start - 1) + '\u0F0E' + val.substring(end);
        target.setSelectionRange(start, start);
      } else {
        target.value = val.substring(0, start) + '\u0F0D' + val.substring(end);
        target.setSelectionRange(start + 1, start + 1);
      }
      dispatchInputEvents(target);
    }
  }

  /**
   * Handle Gutenberg ContentEditable Blocks
   */
  function handleContentEditableKeydown(e) {
    if (!wylieActive) return;
    const target = e.target;
    if (!target || !target.isContentEditable) return;

    const triggerKeys = [' ', '/', ';', '!', 'Enter'];
    if (!triggerKeys.includes(e.key)) return;

    const sel = window.getSelection();
    if (!sel || !sel.isCollapsed || !sel.rangeCount) return;

    const range = sel.getRangeAt(0);
    const node = range.startContainer;
    if (node.nodeType !== Node.TEXT_NODE) return;

    const text = node.textContent;
    const offset = range.startOffset;
    const beforeCursor = text.substring(0, offset);

    const match = beforeCursor.match(/([a-zA-Z0-9'+.~`-]+)$/);

    if (match) {
      e.preventDefault();
      const wylieWord = match[1];
      const converted = convertSyllable(wylieWord);

      let punct = '';
      if (e.key === ' ') {
        punct = '\u0F0B';
      } else if (e.key === '/') {
        punct = beforeCursor.endsWith('\u0F0D') ? '\u0F0E' : '\u0F0D';
      } else if (e.key === ';') {
        punct = '\u0F0F';
      } else if (e.key === '!') {
        punct = '\u0F14';
      } else if (e.key === 'Enter') {
        punct = '\n';
      }

      const wordStart = offset - wylieWord.length;
      let newText;
      let cursorOffset;

      if (e.key === '/' && punct === '\u0F0E') {
        newText = text.substring(0, offset - 1) + '\u0F0E' + text.substring(offset);
        cursorOffset = offset;
      } else {
        newText = text.substring(0, wordStart) + converted + punct + text.substring(offset);
        cursorOffset = wordStart + converted.length + punct.length;
      }

      node.textContent = newText;

      const newRange = document.createRange();
      newRange.setStart(node, Math.min(cursorOffset, node.textContent.length));
      newRange.collapse(true);
      sel.removeAllRanges();
      sel.addRange(newRange);

      dispatchInputEvents(target);
    } else if (e.key === ' ') {
      e.preventDefault();
      const newText = text.substring(0, offset) + '\u0F0B' + text.substring(offset);
      node.textContent = newText;

      const newRange = document.createRange();
      newRange.setStart(node, offset + 1);
      newRange.collapse(true);
      sel.removeAllRanges();
      sel.addRange(newRange);
      dispatchInputEvents(target);
    }
  }

  function dispatchInputEvents(element) {
    element.dispatchEvent(new Event('input', { bubbles: true, cancelable: true }));
    element.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
  }

  // Attach global event listeners
  document.addEventListener('keydown', function (e) {
    handleInputKeydown(e);
    handleContentEditableKeydown(e);
  }, true);

  // Initialize UI once DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initToggleButton);
  } else {
    initToggleButton();
  }

})();
