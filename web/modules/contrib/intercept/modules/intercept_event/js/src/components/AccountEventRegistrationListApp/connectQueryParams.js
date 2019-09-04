import { addUrlProps, UrlQueryParamTypes } from 'react-url-query';

import interceptClient from 'interceptClient';
import updateWithHistory from 'intercept/updateWithHistory';

const urlPropsQueryConfig = {
  view: { type: UrlQueryParamTypes.string },
  showSaves: { type: UrlQueryParamTypes.boolean },
  showRegistrations: { type: UrlQueryParamTypes.boolean },
};

const connectQueryParams = component => updateWithHistory(
  addUrlProps({ urlPropsQueryConfig })(component)
);

export default connectQueryParams;
