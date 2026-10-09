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
        },
        // New design (includes/v2): the acibademinternational.com palette
        navy: { DEFAULT: '#092c74', 600: '#0c317f', 800: '#08235e', 900: '#061a47', 950: '#03102b' },
        azure: { DEFAULT: '#1479a8', dark: '#0e628a' },
        aqua: { DEFAULT: '#25aae1', light: '#3db7e8', deep: '#1a8fc4', soft: '#e6f5fc' },
        coral: { DEFAULT: '#f26b50', ink: '#c8452c', light: '#ffa08a', soft: '#fff1ed' },
        ink: '#13233c',
        muted: { DEFAULT: '#566880', 2: '#8696aa' },
        line: '#e7ecf3',
        surface: { DEFAULT: '#f4f7fc', 2: '#eaf0f8' },
        wa: '#25d366',
      },
      fontFamily: {
        sans: ['Inter', 'Noto Sans Arabic', 'Noto Sans Georgian', 'system-ui', 'sans-serif'],
        // Plus Jakarta Sans has no basic Cyrillic: Inter fills in for ru/uk/bg/mk
        brand: ['"Plus Jakarta Sans"', 'Inter', 'Noto Sans Arabic', 'Noto Sans Georgian', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        'card': '0 6px 20px rgba(9,44,116,.07)',
        'card-hover': '0 20px 50px rgba(9,44,116,.12)',
        'lift': '0 40px 90px rgba(9,44,116,.20)',
        'cta': '0 12px 26px rgba(37,170,225,.35)',
        'cta-hover': '0 20px 38px rgba(37,170,225,.45)',
      },
      borderRadius: {
        '4xl': '30px',
      },
      transitionTimingFunction: {
        'brand': 'cubic-bezier(.2,.7,.2,1)',
      },
    }
  },
  plugins: [],
}
