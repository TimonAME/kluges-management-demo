/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.vue",
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
    "./node_modules/flowbite/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        main: "#9900fe",
        first_accent: "#d19cff",
        second_accent: "#a38cff",
        first_bg: "#fef0ff",
        second_bg: "#fef0ff",
        window_bg: "#ffffff",
        icon_color: "#5f6368",
        primary_text: "#25282b",
        secondary_text: "#5f6368",
        warning_text: "#ffb82e",
        error_text: "#f93232",
        success_text: "#439f6e",
      },

      borderRadius: {
        window: "0.25rem",
        button: "0.25rem",
      },

      boxShadow: {
        window: "2px 2px 8px 0 rgba(0, 0, 0, 0.1)",
      },
      width: {
        18: "72px",
      },
      margin: {
        18: "72px",
      },
    },
  },
  plugins: [require("flowbite/plugin")],
};
