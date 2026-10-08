/** Frontend Tailwind config — mirrors the theme that used to be inlined in includes/header.php */
module.exports = {
  content: [
    './*.php',
    './includes/**/*.php',
    './lang/**/*.php',
    './assets/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        acibadem: {
          blue: '#0c2d74',
          light: '#E6F0FA',
          dark: '#0A1C36',
          accent: '#1a4ba0'
        }
      },
      fontFamily: {
        sans: ['Inter', 'Inter', 'system-ui', 'sans-serif']
      }
    }
  },
  plugins: [],
}
