module.exports = {
  content: [
    "./src/**/*.{js,jsx,ts,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#00a884',
          dark: '#008069',
        },
        dark: {
          DEFAULT: '#111b21',
          lighter: '#202c33',
          lightest: '#2a3942',
        },
        chat: {
          incoming: '#202c33',
          outgoing: '#005c4b',
        }
      },
      animation: {
        'pop': 'pop 0.25s ease-out',
      },
      keyframes: {
        pop: {
          '0%': { transform: 'scale(0.95)', opacity: '0' },
          '100%': { transform: 'scale(1)', opacity: '1' },
        }
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
} 