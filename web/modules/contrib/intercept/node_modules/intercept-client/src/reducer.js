import keyBy from 'lodash/keyBy';
import mapValues from 'lodash/mapValues';
import { apiReducer } from './util/ApiManager';
import schema from './schema/schema.json';

// Add JSON_API resources to this list to create reducers.
const resources = Object.keys(schema).map(resource => ({
  resource,
  strategy: 'overrideAll'
}));

const reducer = mapValues(keyBy(resources, r => r.resource), r =>
  apiReducer(r.resource, r.strategy));

export default reducer;
