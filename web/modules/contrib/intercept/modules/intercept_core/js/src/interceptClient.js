// Polyfill: Fetch API.
import 'whatwg-fetch';
import { createStore, applyMiddleware, combineReducers, compose } from 'redux';
import intercept from './lib/intercept-client';
import drupalSettings from 'drupalSettings';
import thunk from 'redux-thunk';
import * as mui from './lib/mui';
import * as select from './lib/select';
import * as utils from './lib/utils';
import history from './lib/history';
import session from './lib/session';

const reducers = combineReducers({
  ...intercept.reducer,
});

const consumer = drupalSettings.intercept ? drupalSettings.intercept.consumer : null;
/* eslint-disable no-underscore-dangle */
const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;
/* eslint-enable */

const store = createStore(reducers, composeEnhancers(applyMiddleware(thunk)));
const interceptClient = Object.assign(
  {},
  intercept,
  {
    mui,
    store,
    select,
    consumer,
    history,
    session,
    utils,
  });
window.interceptClient = interceptClient;
export default interceptClient;
