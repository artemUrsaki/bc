import type { Config } from 'tailwindcss'
import defaultTheme from 'tailwindcss/defaultTheme'

export default {
  content: ['./src/*.vue', './src/components/**/*.vue', './src/views/*.vue'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['InterVariable', ...defaultTheme.fontFamily.sans],
      },
      colors: {
        'accent-blue': '#0077ff',
        'dark-blue': '#000c1a',
        'lighter-blue': '#003671',
        'lightest-blue': '#3392FF',
        'accent-purple': '#5e00ff',
      },
      minHeight: {
        hero: 'calc(100vh - 200px)',
      },
    },
  },
  plugins: [],
} satisfies Config
