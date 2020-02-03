import React from 'react';
import { Provider } from 'react-redux';
import { configureUrlQuery } from 'react-url-query';

import interceptClient from 'interceptClient';
import interceptTheme from 'interceptTheme';

import { StylesProvider, MuiThemeProvider, createMuiTheme } from '@material-ui/core/styles';

// Redux store
const store = interceptClient.store;

// Intercept Material UI theme
const { jss, generateClassName } = interceptClient.mui;
const theme = createMuiTheme(interceptTheme);

// link the history used in our app to url-query so it can update the URL with it.
configureUrlQuery({ history: interceptClient.history });

function getDisplayName(WrappedComponent) {
  return WrappedComponent.displayName || WrappedComponent.name || 'Component';
}

//
// Higher order component that connects child components with:
//  1. interceptClient.store: Intercept Redux store.
//  2. interceptTheme: Intercept MUI theme
//  3. interceptClient.history: history provider for url parma integrations
//
function withIntercept(WrappedComponent) {
  // eslint-disable-next-line react/prefer-stateless-function
  class WithIntercept extends React.Component {
    render() {
      return (
        <Provider store={store}>
          <StylesProvider jss={jss} generateClassName={generateClassName}>
            <MuiThemeProvider theme={theme}>
              <WrappedComponent {...this.props} />
            </MuiThemeProvider>
          </StylesProvider>
        </Provider>
      );
    }
  }

  WithIntercept.displayName = `WithIntercept(${getDisplayName(WrappedComponent)})`;
  return WithIntercept;
}

export default withIntercept;
