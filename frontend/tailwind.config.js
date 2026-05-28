/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        industrial: {
          900: '#1a1a1a',
          800: '#2d2d2d',
          700: '#3d3d3d',
          600: '#4d4d4d',
          primary: '#f59e0b', // Amber for "Zebra" feel
        }
      }
    },
  },
  plugins: [],
}
