/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        zebra: {
          dark: '#1a1a1a',
          sidebar: '#252526',
          active: '#37373d',
          accent: '#007acc',
          light: {
            bg: '#f3f3f3',
            sidebar: '#ffffff',
            active: '#e8e8e8',
            text: '#333333'
          }
        }
      }
    },
  },
  plugins: [],
}
