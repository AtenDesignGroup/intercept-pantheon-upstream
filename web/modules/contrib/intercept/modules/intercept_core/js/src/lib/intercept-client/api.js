import mapValues from 'lodash/mapValues';
import { ApiManager } from './util/ApiManager';
import models from './models';

const api = mapValues(models, model =>
  new ApiManager({
    model,
    priority: 2,
  }));

export default api;
