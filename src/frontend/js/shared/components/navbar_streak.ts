/**
 * Navbar Streak Component - Shows current streak as a flame icon in the navbar.
 *
 * Lightweight component that fetches only the streak endpoint.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

interface NavbarStreakState {
  streak: number;
  init(): void;
}

function navbarStreak(): NavbarStreakState {
  return {
    streak: 0,

    init() {
      fetch('/api/v1/activity/streak')
        .then(r => r.json())
        .then((data: { current_streak?: number }) => {
          this.streak = data.current_streak ?? 0;
        })
        .catch(() => {
          // Silently ignore — streak is non-critical
        });
    },
  };
}

Alpine.data('navbarStreak', navbarStreak);
