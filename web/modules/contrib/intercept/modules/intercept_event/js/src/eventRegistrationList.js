import React from 'react';
import { render } from 'react-dom';
import withIntercept from 'intercept/withIntercept';
import AccountEventRegistrationListApp from './components/AccountEventRegistrationListApp';

const App = withIntercept(AccountEventRegistrationListApp);

render(<App />, document.getElementById('eventRegistrationRoot'));
