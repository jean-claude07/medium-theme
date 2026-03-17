/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./templates/**/*.php",
    "./inc/**/*.php",
    "./assets/js/**/*.js",
  ],
  darkMode: 'class', // Enables dark mode via a 'dark' class
  theme: {
    extend: {
      colors: {
        primary: '#10b981',
        secondary: '#6366f1',
        dark: {
          bg: '#0f172a',
          surface: '#1e293b',
          border: '#334155'
        },
        light: {
          bg: '#ffffff',
          surface: '#f8fafc',
          border: '#e2e8f0'
        }
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        serif: ['Georgia', 'ui-serif', 'serif'],
      },
      animation: {
        'fade-in': 'fadeIn 0.3s ease-in-out',
        'slide-up': 'slideUp 0.4s ease-out'
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        }
      }
    },
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('@tailwindcss/forms'),
  ],
}
