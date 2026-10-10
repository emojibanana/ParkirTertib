/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './views/**/*.php',
    './public/**/*.php',
    './public/assets/js/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
