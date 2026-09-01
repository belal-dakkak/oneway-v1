/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#2C1810',
          foreground: '#FFFFFF',
        },
        secondary: {
          DEFAULT: '#F5F5F0',
          foreground: '#2C1810',
        },
        accent: {
          DEFAULT: '#D4AF37',
          foreground: '#2C1810',
        },
        muted: {
          DEFAULT: '#E8E2D5',
          foreground: '#5C4033',
        },
        background: {
          DEFAULT: '#FAFAF8',
          foreground: '#2C1810',
        },
        foreground: {
          DEFAULT: '#2C1810',
        },
        card: {
          DEFAULT: '#FFFFFF',
          foreground: '#2C1810',
        },
        border: '#E0D5C7',
        input: '#F0E6DC',
        ring: '#D4AF37',
        destructive: {
          DEFAULT: '#B91C1C',
          foreground: '#FFFFFF',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        lg: '0.625rem',
        md: 'calc(0.625rem - 2px)',
        sm: 'calc(0.625rem - 4px)',
      },
    },
  },
  plugins: [],
}