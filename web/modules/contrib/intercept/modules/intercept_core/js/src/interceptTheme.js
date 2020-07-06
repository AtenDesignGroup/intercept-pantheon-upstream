
// B & W
const black = '#000';
const white = '#fff';

// Grayscale
const gray00 = '#130f13';
const gray10 = '#261f26';
const gray20 = '#4C4D4F';
const gray30 = '#747481'; // changed to slightly darker to comply with A11y constrast standards for AA of 4.5+
const gray40 = '#818487';
const gray50 = '#878B90';
const gray60 = '#D9DCE0';
const gray70 = '#E4E8EB';
const gray80 = '#EEF1F4';
const gray90 = '#F6F8F9';

// Red
const red50 = '#F44336';

// Blue
const blue10 = '#008DB1';
const blue20 = '#0277BD';
const blue40 = '#0288D1';
const blue50 = '#039BE5';
const blue60 = '#03A9F4';
const blue80 = '#29B6F6';

// Orange
const orange50 = '#FFA726';

const interceptTheme = {
  palette: {
    primary: {
      light: blue50,
      main: blue40,
      dark: blue20,
      contrastText: white,
    },
    secondary: {
      light: gray60,
      main: gray30,
      dark: gray20,
      contrastText: white,
    },
    error: {
      main: red50,
    },
  },
};

window.interceptTheme = interceptTheme;
export default interceptTheme;
