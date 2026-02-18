import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Alpine is provided by Livewire on app layout; on guest/landing we start it here so dropdowns and collapse work.
// Wait a short time after DOMContentLoaded to let Livewire scripts start loading
document.addEventListener('DOMContentLoaded', function () {
  setTimeout(function () {
    if (typeof window.Livewire === 'undefined') {
      // Only set up Alpine when Livewire is not present (guest/landing pages)
      Alpine.plugin(collapse);
      window.Alpine = Alpine;
      Alpine.start();
    } else {
      // On Livewire pages, register the collapse plugin with Livewire's Alpine instance
      if (window.Alpine && typeof window.Alpine.plugin === 'function') {
        try {
          window.Alpine.plugin(collapse);
        } catch (e) {
          // Plugin might already be registered, ignore error
        }
      }
    }
  }, 50);
});
