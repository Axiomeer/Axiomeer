/**
 * Paper UI — Interactive Behaviours (Axiomeer Edition)
 * Zero dependencies — vanilla JS only.
 */

const PaperUI = (() => {
  'use strict';

  /* ── Button Ripple ── */
  function initRipple() {
    document.addEventListener('click', function(e) {
      const btn = e.target.closest('.btn-mm');
      if (!btn) return;
      const r    = document.createElement('span');
      const rect = btn.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      r.style.cssText = [
        'position:absolute',
        `width:${size}px`, `height:${size}px`,
        'border-radius:50%',
        'background:rgba(255,255,255,0.35)',
        'transform:scale(0)',
        `left:${e.clientX - rect.left - size / 2}px`,
        `top:${e.clientY  - rect.top  - size / 2}px`,
        'animation:ripple 0.55s ease-out forwards',
        'pointer-events:none',
      ].join(';');
      btn.appendChild(r);
      setTimeout(() => r.remove(), 600);
    });
  }

  /* ── Modal Helper ── */
  const modal = {
    open(selector) {
      const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
      if (el) el.classList.add('open');
    },
    close(selector) {
      const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
      if (el) el.classList.remove('open');
    },
    closeAll() {
      document.querySelectorAll('.mm-modal-backdrop.open').forEach(el => el.classList.remove('open'));
    },
  };

  function initModals() {
    document.addEventListener('click', e => {
      if (e.target.classList.contains('mm-modal-backdrop')) e.target.classList.remove('open');
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') modal.closeAll();
    });
  }

  /* ── Tab Switcher ── */
  function tabs(containerSelector) {
    const containers = typeof containerSelector === 'string'
      ? document.querySelectorAll(containerSelector)
      : [containerSelector];
    containers.forEach(container => {
      container.querySelectorAll('.mm-tab').forEach(btn => {
        btn.addEventListener('click', () => {
          const targetId = btn.dataset.tab;
          if (!targetId) return;
          container.querySelectorAll('.mm-tab').forEach(t => t.classList.remove('active'));
          btn.classList.add('active');
          const scope = container.closest('.card-mm') || document;
          scope.querySelectorAll('.mm-pane').forEach(p => p.classList.remove('active'));
          const target = scope.querySelector(`#${targetId}`) || document.getElementById(targetId);
          if (target) target.classList.add('active');
        });
      });
    });
  }

  /* ── Toast ── */
  function toast(message, type = 'success', duration = 4000) {
    const icons = {
      success: 'ph-check-circle',
      error:   'ph-warning-circle',
      warning: 'ph-warning',
      info:    'ph-info',
    };
    const el = document.createElement('div');
    el.className = `pu-toast ${type}`;
    el.innerHTML = `<i class="ph ${icons[type] || icons.success}" style="font-size:17px;flex-shrink:0;"></i><span>${message}</span>`;
    document.body.appendChild(el);
    setTimeout(() => {
      el.style.transition = 'opacity 0.4s, transform 0.4s';
      el.style.opacity = '0';
      el.style.transform = 'translateY(8px)';
      setTimeout(() => el.remove(), 420);
    }, duration);
  }

  /* ── Sidebar Toggle ── */
  function initSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const toggle  = document.getElementById('sidebar-toggle');
    const overlay = document.querySelector('.sidebar-overlay');
    if (!sidebar || !toggle) return;

    const STORAGE_KEY = 'axiomeer_sidebar_collapsed';
    const isMobile = () => window.innerWidth <= 768;

    // Restore state
    if (!isMobile() && localStorage.getItem(STORAGE_KEY) === '1') {
      sidebar.classList.add('collapsed');
    }

    toggle.addEventListener('click', () => {
      if (isMobile()) {
        sidebar.classList.toggle('mobile-open');
        if (overlay) overlay.classList.toggle('active', sidebar.classList.contains('mobile-open'));
      } else {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem(STORAGE_KEY, sidebar.classList.contains('collapsed') ? '1' : '0');
      }
    });

    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
      });
    }
  }

  /* ── Theme Toggle (Dark Mode) ── */
  function initTheme() {
    const STORAGE_KEY = 'axiomeer_theme';
    const btn = document.getElementById('theme-toggle');

    function applyTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem(STORAGE_KEY, theme);
    }

    // Restore
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
      applyTheme(saved);
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
      applyTheme('dark');
    }

    if (btn) {
      btn.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme');
        applyTheme(current === 'dark' ? 'light' : 'dark');
      });
    }
  }

  /* ── Auto-Init ── */
  function init() {
    initRipple();
    initModals();
    initSidebar();
    initTheme();
    tabs('.mm-tabs');
  }

  return { init, modal, toast, tabs };
})();

document.addEventListener('DOMContentLoaded', () => PaperUI.init());
