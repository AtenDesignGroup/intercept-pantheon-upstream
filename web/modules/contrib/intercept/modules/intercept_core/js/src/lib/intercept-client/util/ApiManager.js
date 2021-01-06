import { v4 as uuidv4 } from 'uuid';
import assign from 'lodash/assign';
import filter from 'lodash/filter';
import forEach from 'lodash/forEach';
import groupBy from 'lodash/groupBy';
import mapValues from 'lodash/mapValues';
import reduce from 'lodash/reduce';
import * as a from './../actions';
import * as t from './../actionTypes';
import exponentialBackoff from './exponentialBackoff';
import { EntityModel } from './EntityModel';

// Registrar
import Registrar from './Registrar';

// Logger
import logger from './logger';

const url = require('url');
const URL = require('url-parse');

export const apiRegistrar = new Registrar('api');

/**
 * Generic fetch resolve handler.
 * @param  {Request} request The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
 * @param  {function} resolve Promise.prototype.resolve()
 * @param  {function} reject Promise.prototype.reject()
 * @return {function} A handler that takes the error object from a failed fetch.
 */
function responseHandler(request, resolve, reject) {
  return (resp) => {
    // Handle a successful response.
    if (resp.ok) {
      resolve(resp);
    }
    else {
      // Handle a failed response.
      switch (resp.status) {
        // Don't retry:
        //   403 - Permission denied responses.
        //   404 - Not Found.
        //   409 - Conflict.
        //   422 - Failed validation.
        //   504 - Timeout.
        case 403:
        case 404:
        case 409:
        case 422:
        case 504:
          resolve(resp);
          break;
        // Retry all other responses.
        default:
          reject(new Error(resp.status.toString()));
      }
    }
  };
}

/**
 * Generic fetch rejection handler.
 * @param  {Request} request The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
 * @param  {function} resolve Promise.prototype.resolve()
 * @param  {function} reject Promise.prototype.reject()
 * @return {function} A handler that takes the error object from a failed fetch.
 */
function errorHandler(request, resolve, reject) {
  return (err) => {
    logger.log('network', 'Rejected fetch', err);
    reject(err);
  };
}

/**
 * Handles dispatching a successful API call's response.
 * @param  {Object}   resp     Response object instance.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource Resource type
 * @param  {Object}   model  EntityManager for this resource.
 * @param  {String}   [uuid]     UUID of the resource
 */
export function handleValidateSuccessResponse(resp, items, dispatch, resource) {
  return resp.json().then((json) => {
    logger.group('validate', 'response');
    logger.log('validate', `Validation Response: ${resource}`, json);

    if (!json.states) {
      return;
    }

    const manifest = reduce(
      json.states,
      (accumulator, remoteItem, key) => {
        // Locally stored item;
        const localItem = items[key];

        if (!remoteItem.created) {
          accumulator.notSaved.push(key);
        }
        else if (
          parseInt(localItem.data.changed, 10) !==
          parseInt(remoteItem.changed, 10)
        ) {
          accumulator.notSynced.push(key);
        }
        else {
          accumulator.valid.push(key);
        }

        return accumulator;
      },
      {
        valid: [],
        notSaved: [],
        notSynced: [],
      },
    );

    manifest.notSaved.forEach((uuid) => {
      dispatch(a.setSaved(false, resource, uuid));
    });

    manifest.notSynced.forEach((uuid) => {
      dispatch(a.markDirty(resource, uuid));
    });

    logger.log('validate', manifest);
    logger.groupEnd('validate', 'response');
  });
}

/**
 * Handles dispatching a failed API call's response.
 * @param  {Object}   resp     Response object instance.
 */
export function handleValidateFailedResponse(resp) {
  return resp.json().then((json) => {
    const err =
      json ||
      `${resp.status}: ${resp.statusText || 'No status message provided'}`;
    logger.log('validate', 'Failed Validation json', err);
  });
}

/**
 * Handles dispatching a failed API call's response.
 * @param  {Object}   resp     Response object instance.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource Resource type
 * @param  {Object}   model  EntityManager for this resource.
 * @param  {String}   [uuid]     UUID of the resource
 */
export function handleValidateResponse(resp, items, dispatch, resource) {
  logger.log('validate', 'Response', resp, resource);

  if (resp.ok) {
    return handleValidateSuccessResponse(resp, items, dispatch, resource);
  }
  return handleValidateFailedResponse(resp, items, dispatch, resource);
}

/**
 * Handles dispatching a failed API call's response.
 * @param  {Object}   resp     Response object instance.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource Resource type
 * @param  {String}   [uuid]     UUID of the resource
 */
export function handleFailedResponse(resp, request, dispatch, resource, uuid) {
  return resp
    .json()
    .then((json) => {
      logger.log('network', 'Failed json response', json);

      const err =
        json ||
        `${resp.status}: ${resp.statusText || 'No status message provided'}`;

      // Mark an non-existent entity as not saved.
      if (request.method === 'PATCH' && resp.status === 404) {
        dispatch(a.failure(err, resource, uuid));
        return dispatch(a.setSaved(false, resource, uuid));
      }

      // Mark an existing entity as saved.
      if (request.method === 'POST' && resp.status === 409) {
        dispatch(a.failure(err, resource, uuid));
        return dispatch(a.setSaved(true, resource, uuid));
      }

      if ((request.method === 'POST' || request.method === 'PATCH') && resp.status === 422) {
        return dispatch(a.failure(err, resource, uuid));
      }

      return dispatch(a.failure(err, resource, uuid));
    })
    .catch(err => dispatch(a.failure(err, resource, uuid)));
}

/**
 * Processes included resources, adding them to the store.
 * @param {Function} dispatch
 *   Redux dispatch function
 * @returns {Function}
 *   Accepts an array of included resource objects, groups them by resource type and
 *   dispatches a receive action for each type.
 */
export function processIncludes(dispatch) {
  return (includes) => {
    const resources = groupBy(includes, record => record.type);
    forEach(resources, (records, resource) => {
      dispatch(a.receive({ data: records.map(EntityModel.import) }, resource));
    });
  };
}

/**
 * Handles dispatching a successful API call's response.
 * @param  {Object}   resp     Response object instance.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource Resource type
 * @param  {Object}   model  EntityManager for this resource.
 * @param  {String}   [uuid]     UUID of the resource
 */
export function handleSuccessResponse(resp, dispatch, resource, model, uuid) {
  return resp.json().then((json) => {
    logger.group('network', 'response');
    logger.log('network', `Response: ${resource}`, json);
    // Handle cases where the response doesn't have a nested data object,
    //  such as file uploads.
    const data = 'data' in json ? json.data : json;

    const output = {
      data: Array.isArray(data)
        ? data.map(EntityModel.import)
        : EntityModel.import(data),
    };

    logger.log('network', output.data);
    logger.groupEnd('network', 'response');

    if ('included' in json) {
      processIncludes(dispatch)(json.included);
    }

    return dispatch(a.receive(output, resource, uuid));
  });
}

/**
 * Handles dispatching a failed API call's response.
 * @param  {Object}   resp     Response object instance.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource Resource type
 * @param  {Object}   model  EntityManager for this resource.
 * @param  {String}   [uuid]     UUID of the resource
 */
export function handleResponse(resp, request, dispatch, resource, model, uuid) {
  logger.log('network', 'Response', resp, resource, uuid);

  if (resp.ok) {
    return handleSuccessResponse(resp, dispatch, resource, model, uuid);
  }
  return handleFailedResponse(resp, request, dispatch, resource, uuid);
}

/**
 * Handles a network error in a request.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource [description]
 * @param  {[type]} id       [description]
 * @return {[type]}          [description]
 */
export function handleNetworkError(dispatch, resource, id) {
  return (error) => {
    const message = `There has been a problem with your connection: ${
      error.message
    }`;
    dispatch(a.failure(message, resource, id));
    logger.log('network', message, error);
  };
}

export const ApiManager = class {
  constructor(options) {
    const { model } = options;
    this.model = model;
    this.type = model.type;
    this.bundle = model.bundle;
    this.resource = model.resource;
    this.fields = {
      [model.resource]: model.getFields(false),
    };
    this.include = options.include || [];
    this.namespace = options.namespace || 'jsonapi';
    this.priority = options.priority || 9;
    this.latestFetch = null;

    // Bind methods.
    this.getLatestFetch = this.getLatestFetch.bind(this);
    this.setLatestFetch = this.setLatestFetch.bind(this);
    this.getEndpoint = this.getEndpoint.bind(this);
    this.getEndpointPath = this.getEndpointPath.bind(this);
    this.getRelationshipEndpoint = this.getRelationshipEndpoint.bind(this);
    this.getTimestampEndpoint = this.getTimestampEndpoint.bind(this);
    this.fetchAll = this.fetchAll.bind(this);
    this.fetchResource = this.fetchResource.bind(this);
    this.fetchTranslations = this.fetchTranslations.bind(this);
    this.sync = this.sync.bind(this);
    this.updateRelationshipsIfNeeded = this.updateRelationshipsIfNeeded.bind(this);
    this.backoffFetch = this.backoffFetch.bind(this);
    this.wrapFetch = this.wrapFetch.bind(this);

    // Register this instance.
    apiRegistrar.register(model.resource, this);
  }

  /**
   * Creates a url string for making JSON_API requests
   * @param  {object|string} options
   *  A url object as returned by url.parse() or a fully constructed url which
   *  will be passed through url.parse
   * @return {string} Fully formed url origin segment ex. https://www.example.com.
   */
  static getEndpointOrigin(options) {
    // Format the url string.
    return options ? new URL(url.format(options)).origin : '/';
  }

  /**
   * Creates a url string for making JSON_API requests
   * @param  {object|string} options
   *  A url object as returned by url.parse() or a fully constructed url
   *  which will be passed through url.parse
   * @return {string}
   *  Fully formed url origin segment ex. https://www.example.com.
   */
  getEndpointPath() {
    return [this.namespace, this.type, this.bundle].join('/');
  }

  /**
   * Creates a query parameter object formatted for filters
   * See https://www.drupal.org/docs/8/modules/json-api/filtering
   * @param  {Object} filters
   *  @todo document
   * @return {Object}
   *  An object of query param key|value pairs
   */
  static getEndpointFilters(filters) {
    return reduce(
      filters,
      (query, value, key) => {
        const output = assign({}, query);
        const type = value.type || 'condition';
        const multiOperators = ['IN', 'NOT IN', 'BETWEEN'];
        const useShorthand =
          'operator' in value === false &&
          'condition' in value === false &&
          'memberOf' in value === false &&
          'type' in value === false;

        // Handle shorthand filters.
        if (useShorthand) {
          output[`filter[${value.path}][value]`] = value.value;
          return output;
        }

        // Handle groups
        if (type === 'group') {
          // Default to AND if conjuction is not specified.
          output[`filter[${key}][group][conjunction]`] =
            value.conjunction || 'AND';

          if ('memberOf' in value) {
            output[`filter[${key}][group][memberOf]`] = value.memberOf;
          }

          return output;
        }

        // Handle default
        output[`filter[${key}][condition][path]`] = value.path;

        // Handle multi-value operators.
        if (multiOperators.indexOf(value.operator) > -1) {
          output[`filter[${key}][condition][value][]`] = value.value;
        }
        else {
          output[`filter[${key}][condition][value]`] = value.value;
        }

        if ('operator' in value) {
          output[`filter[${key}][condition][operator]`] = value.operator;
        }

        if ('memberOf' in value) {
          output[`filter[${key}][condition][memberOf]`] = value.memberOf;
        }

        return output;
      },
      {},
    );
  }

  /**
   * Creates a query parameter object formatted for sorts
   * See https://www.drupal.org/docs/8/modules/json-api/collections-and-sorting
   * @param  {Object} sort
   *  @todo document
   * @return {Object}
   *  An object of query param key|value pairs
   */
  static getEndpointSort(sort) {
    const output = reduce(
      sort,
      (query, value) => {
        const direction = value.direction === 'DESC' ? '-' : '';
        const sortParam = `${direction}${value.path}`;
        return query ? [].concat(query, sortParam).join(',') : sortParam;
      },
      null,
    );

    return output === null ? {} : { sort: output };
  }

  /**
   * Creates a query parameter object formatted for sparse fieldsets
   * See https://www.drupal.org/docs/8/modules/json-api/collections-filtering-and-sorting
   * See http://jsonapi.org/format/#fetching-sparse-fieldsets
   * @param  {Object} fields
   *  @todo document
   * @return {Object}
   *  An object of query param key|value pairs
   */
  static getEndpointFields(fields) {
    if (fields === null) {
      return {};
    }

    return reduce(
      fields,
      (query, value, key) =>
        assign(query, { [`fields[${key}]`]: value.join(',') }),
      {},
    );
  }

  /**
   * Creates a query parameter object formatted for including related resources
   * See https://www.drupal.org/docs/8/modules/json-api/fetching-resources-get
   * See http://jsonapi.org/format/#fetching-includes
   * @param  {Object} include
   *  @todo document
   * @return {Object}
   *  An object of query param key|value pairs
   */
  static getEndpointInclude(include) {
    // Set includes if they exist.
    return Array.isArray(include) && include.length > 0
      ? { include: include.join(',') }
      : {};
  }

  /**
   * Creates a query parameter object formatted for pagination limit
   * See https://www.drupal.org/docs/8/modules/json-api/pagination
   * See http://jsonapi.org/format/#fetching-pagination
   * @param  {number} limit
   *  @todo document
   * @return {Object}
   *  An object of query param key|value pairs
   */
  static getEndpointLimit(limit) {
    return limit ? { 'page[limit]': limit } : {};
  }

  /**
   * Creates a query parameter object formatted for pagination offset
   * See https://www.drupal.org/docs/8/modules/json-api/pagination
   * See http://jsonapi.org/format/#fetching-pagination
   * @param  {number} offset
   *  @todo document
   * @return {Object}
   *  An object of query param key|value pairs
   */
  static getEndpointOffset(offset) {
    return offset ? { 'page[offset]': offset } : {};
  }

  /**
   * Creates a query parameter object formatted for sparse fieldsets
   * See https://www.drupal.org/docs/8/modules/json-api/collections-filtering-and-sorting
   * See http://jsonapi.org/format/#fetching-sparse-fieldsets
   * @param  {Object} fields
   *  @todo document
   * @return {Object}
   *  An object of query param key|value pairs
   */
  getEndpointQueryParams(options) {
    // Generate query params.
    const params = options.params || {};

    // Set filters if they exist.
    const filters = options.filters
      ? this.constructor.getEndpointFilters(options.filters)
      : {};

    // Set fields if they exist.
    const fields = this.constructor.getEndpointFields(
      options.fields ||
      options.fields === null ||
      this.fields ||
      {},
    );

    // Set include if they exist.
    const include = options.include
      ? this.constructor.getEndpointInclude(options.include)
      : {};

    // Set sorts if they exist.
    const sort = options.sort
      ? this.constructor.getEndpointSort(options.sort)
      : {};

    // Set limit if specified.
    const limit = options.limit
      ? this.constructor.getEndpointLimit(options.limit)
      : {};

    // Set offset if specified.
    const offset = options.offset
      ? this.constructor.getEndpointOffset(options.offset)
      : {};

    return assign(params, filters, fields, limit, include, offset, sort);
  }

  /**
   * Creates a url string for making JSON_API requests
   * @param  {string} resource
   *  The machine name of the resource.
   * @param  {object} options
   *  Additional request parameters.
   * @param  {string} [options.lang]
   *  Translation language code. _ex. 'en'
   * @param  {string} options.bundle
   *  Entity bundle. ex. 'article', 'tags'
   * @param  {Object} [options.fields]
   *  An object in which the properties are resource types and
   *  the value is an array of attribute name strings
   * @param  {Array}  [options.include]
   *  An array of resource type strings.
   *
   * @return {string}           Fully formed url.
   */
  getEndpoint(options) {
    const origin = this.constructor.getEndpointOrigin();

    // Generate the path.
    const pathParts = [''];

    // Add translation if needed.
    if (options.lang) {
      pathParts.push(options.lang);
    }

    // Add the collection specific path parts.
    pathParts.push(this.getEndpointPath());

    // If this is resource specific, add the id.
    if (options.id) {
      pathParts.push(options.id);
    }

    const pathname = pathParts.join('/');

    // Generate query params.
    const query = this.getEndpointQueryParams({
      params: options.params,
      fields: options.fields,
      filters: options.filters,
      include: options.include,
      limit: options.limit,
      offset: options.offset,
      sort: options.sort,
    });

    // Format the url string.
    return url.format({
      origin,
      pathname,
      query,
    });
  }

  /**
   * Creates a url string for making JSON_API relationship requests
   * @param  {object} options         Additional request parameters.
   * @param  {string} options.id      UUID o
   *
   * @return {string}           Fully formed url.
   */
  getRelationshipEndpoint(options) {
    const origin = this.constructor.getEndpointOrigin();

    // Generate the path.
    const pathParts = [''];
    // Add translation if needed.
    if (options.lang) {
      pathParts.push(options.lang);
    }
    // Add the collection specific path parts.
    pathParts.push(this.getEndpointPath());
    // If this is resource specific, add the id.
    pathParts.push(options.id);
    pathParts.push('relationships');
    pathParts.push(options.relationship);
    const pathname = pathParts.join('/');

    const query = this.getEndpointQueryParams({
      params: options.params,
      fields: options.fields,
      filters: options.filters,
      include: options.include,
    });

    // Format the url string.
    return url.format({
      origin,
      pathname,
      query,
    });
  }

  getTimestampEndpoint() {
    const origin = this.constructor.getEndpointOrigin();
    const pathname = 'intercept/time';

    // Format the url string.
    return url.format({
      origin,
      pathname,
    });
  }

  static getTimestamp() {
    const now = new Date();
    return Math.floor(now / 1000);
  }

  /**
   * Creates a resource object to make a JSON_API fetch request
   * @param  {string} endpoint
   *  A fully formed url.
   * @param  {object} [options]
   *  Additional request options.
   * @param  {string} [options.method = 'GET']
   *  GET|POST|PATCH|DELETE
   * @param  {string} [options.token]
   *  JWT token. If omited, the request will be unathenticated and will most likely fail.
   * @param  {string} [options.headers]
   *  Additional headers
   * @return {object}
   *  A Request object https://developer.mozilla.org/en-US/docs/Web/API/Request
   */
  static getRequest(endpoint, options = {}) {
    // Assume we're talking JSON_API.
    const defaultHeaders = {
      Accept: 'application/vnd.api+json',
      'Content-Type': 'application/vnd.api+json',
    };

    const requestOptions = {
      method: options.method || 'GET',
      headers: assign({}, defaultHeaders, options.headers || {}),
      credentials: 'same-origin',
    };

    // Add the body field if we have one.
    if (options.body) {
      assign(requestOptions, { body: options.body });
    }

    // Return the Request object.
    return new Request(endpoint, requestOptions);
  }

  static fetchTimestamp() {
    return Promise.resolve(Math.floor(new Date().getTime() / 1000));
  }

  getLatestFetch() {
    return this.latestFetch;
  }

  setLatestFetch(id) {
    this.latestFetch = id;
    return id;
  }

  fetcher(options = {}) {
    let nextLink;
    let totalFetched = 0;
    let done = false;
    let replace = options.replace || false;

    const getNextLink = () => nextLink;
    const getDone = () => done;

    return {
      next: () =>
        this.fetchAll({
          ...options,
          endpoint: getNextLink(),
          totalFetched,
          replace,
          onNext: (endpoint, total) => {
            nextLink = endpoint;
            totalFetched = total;
            replace = false;
          },
          onDone: () => {
            if (options.onDone) {
              options.onDone();
            }
            done = true;
          },
        }),
      isDone: () => getDone(),
    };
  }

  /**
   * Fetches a resource collection.
   * @param {Object} options
   */
  fetchAll(options = {}) {
    // on successful JSON response, map data to this.EntityModel.import
    // then dispatch success, type, data (transformed data)
    const {
      backoffFetch, resource, getLatestFetch, setLatestFetch,
    } = this;
    const { fetchTimestamp } = this.constructor;
    const { getRequest, getTimestamp } = this.constructor;
    const currentFetch = setLatestFetch(uuidv4());

    const filters = options.filters || [];
    const include = options.include || [];
    const sort = options.sort || [];
    const count = options.count || 0;
    let totalFetched = options.totalFetched || 0;
    const {
      fields, limit, offset, onNext, onDone,
    } = options;
    const _fetchAll = this.fetchAll.bind(this);
    let replace = options.replace || false;

    return (dispatch, getState) => {
      const state = getState();

      //
      // Handle request for recent content.
      //
      if (options.recent && state[resource].updated) {
        filters.push({
          path: 'changed',
          value: state[resource].updated,
          operator: '>',
        });
      }

      //
      // Construct and endpoint if one was not supplied
      //
      const endpoint =
        options.endpoint ||
        this.getEndpoint({
          filters,
          include,
          fields,
          sort,
          limit,
          offset,
        });

      //
      // Generate the request object
      //
      const request = getRequest(endpoint, options);

      //
      // Dispatch API collection request action.
      //
      dispatch(a.request(resource));
      logger.log('network', 'Request', request);

      //
      // Make the actual API call
      //
      function makeApiCall() {
        //
        // Get the current timestamp
        // This is referenced later when fetching fresh data, or data changed after this timestamp.
        //
        const fetchTime = fetchTimestamp(getState)
          .then(time => time)
          .catch((err) => {
            logger.log(err);
          });

        //
        // Fetch the data.
        //
        const fetchData = backoffFetch(request, responseHandler, errorHandler)
          .then((resp) => {
            //
            // Handle an OK response
            //
            if (resp.ok) {
              resp.json().then((json) => {
                //
                // Abort if there's a new request in route.
                //
                if (replace && currentFetch !== getLatestFetch()) {
                  return;
                }

                //
                // Ensure the response data is an Array
                //
                const output = {
                  data: [].concat(json.data),
                };

                totalFetched += output.data.length;

                //
                // Log network response
                //
                logger.group('network', 'response');
                logger.log(
                  'network',
                  `Response: ${getTimestamp()} ${resource}`,
                  json,
                );
                logger.log('network', output.data);
                logger.groupEnd('network', 'response');

                //
                // Process included resources.
                //
                if ('included' in json) {
                  processIncludes(dispatch)(json.included);
                }

                //
                // Purge store if replacing.
                //
                if (replace && currentFetch === getLatestFetch()) {
                  dispatch(a.purge(resource));
                  // Ensure it only purges once.
                  replace = false;
                }

                // Cancel receive action if the current fetch doesn't match the latest
                // This is to prevent paginated requests from being added to the results.
                if (currentFetch !== getLatestFetch()) {
                  return;
                }

                //
                // Dispatch Receive action
                //
                dispatch(a.receive(output, resource));

                const hasMore = json.links && json.links.next;

                if (!hasMore) {
                  // Call onDone() then exit.
                  if (onDone) {
                    onDone();
                  }
                  return;
                }

                //
                // Recursively fetch paginated items.
                //
                if (
                  count === 0 ||
                  (count > totalFetched && currentFetch === getLatestFetch())
                ) {
                  dispatch(
                    _fetchAll({
                      ...options,
                      endpoint: json.links.next.href,
                      totalFetched,
                      replace,
                    }),
                  );
                }
                else if (onNext && currentFetch === getLatestFetch()) {
                  // Call onNext()
                  onNext(json.links.next.href, totalFetched);
                }
              });
            }
            //
            // Handle a NOT OK response
            //
            else {
              dispatch(a.failure(
                `${resp.status}: ${resp.statusText ||
                    'No status message provided'}`,
                resource,
              ));
            }

            return resp;
          })
          //
          // Catch network error
          //
          .catch(handleNetworkError(dispatch, resource));

        return Promise.all([fetchTime, fetchData])
          .then((values) => {
            //
            // Set the collection updated timestamp.
            //
            dispatch(a.setTimestamp(resource, values[0]));
            //
            // Return the fetched data.
            //
            return values[1];
          })
          .catch((err) => {
            logger.log(err);
          });
      }

      //
      // Make the API call
      //
      return makeApiCall();
    };
  }

  /**
   * Fetches a resource collection.
   * @param {Object} options
   */
  fetchResource(uuid, options = {}) {
    // on successful JSON response, map data to this.EntityModel.import
    // then dispatch success, type, data (transformed data)
    const { backoffFetch, resource } = this;
    const { getRequest, getTimestamp } = this.constructor;

    const include = options.include || [];
    const { fields } = options;

    return (dispatch) => {
      //
      // Construct and endpoint if one was not supplied
      //
      const endpoint =
        options.endpoint ||
        this.getEndpoint({
          include,
          fields,
          id: uuid,
        });

      //
      // Generate the request object
      //
      const request = getRequest(endpoint, options);

      //
      // Dispatch API collection request action.
      //
      dispatch(a.request(resource, uuid));
      logger.log('network', 'Request', request);

      //
      // Make the actual API call
      //
      function makeApiCall() {
        //
        // Fetch the data.
        //
        const fetchData = backoffFetch(request, responseHandler, errorHandler)
          .then((resp) => {
            //
            // Handle an OK response
            //
            if (resp.ok) {
              resp.json().then((json) => {
                //
                // Ensure the response data is an Array
                //
                const output = {
                  data: json.data,
                };

                //
                // Log network response
                //
                logger.group('network', 'response');
                logger.log(
                  'network',
                  `Response: ${getTimestamp()} ${resource}`,
                  json,
                );
                logger.log('network', output.data);
                logger.groupEnd('network', 'response');

                //
                // Process included resources.
                //
                if ('included' in json) {
                  processIncludes(dispatch)(json.included);
                }

                //
                // Dispatch Receive action
                //
                dispatch(a.receive(output, resource, uuid));
              });
            }
            //
            // Handle a NOT OK response
            //
            else {
              dispatch(a.failure(
                `${resp.status}: ${resp.statusText ||
                    'No status message provided'}`,
                resource,
                uuid,
              ));
            }

            return resp;
          })
          //
          // Catch network error
          //
          .catch(handleNetworkError(dispatch, resource, uuid));

        return (
          Promise.all([fetchData])
            //
            // Return the fetched data.
            //
            .then(values => values[1])
            .catch((err) => {
              logger.log(err);
            })
        );
      }

      //
      // Make the API call
      //
      return makeApiCall();
    };
  }

  // Fetch related translations.
  fetchTranslations(options = {}) {
    // on successful JSON response, map data to this.EntityModel.import
    // then dispatch success, type, data (transformed data)
    const {
      backoffFetch, fields, include, resource, priority,
    } = this;
    const { getRequest, getTimestamp } = this.constructor;

    const _fetchTranslations = this.fetchTranslations.bind(this);

    return (dispatch, getState) => {
      const state = getState();

      // Exit if we're not logged in.
      if (!state.userData.auth.loggedIn) {
        logger.log('network', 'User is logged out: Aborting request.');
        return Promise.resolve('Aborted');
      }

      const { limit, offset } = options;

      const endpoint =
        options.endpoint ||
        this.getEndpoint({
          fields,
          include,
          langcode: options.langcode,
          filters: [].concat(options.filters, {
            path: 'langcode',
            value: options.langcode,
            operator: '=',
          }),
          limit,
          offset,
        });

      const request = getRequest(endpoint, options);

      // Dispatch generic api request action.
      dispatch(a.request(resource));

      logger.log('network', 'Request', request);

      function makeApiCall() {
        return (
          backoffFetch(request, responseHandler, errorHandler)
            .then((resp) => {
              if (resp.ok) {
                resp.json().then((json) => {
                  const output = {
                    data: [].concat(json.data).map(EntityModel.import),
                  };

                  // @todo Handle transforming included resources.
                  logger.group('network', 'response');
                  logger.log('network', `Priority: ${priority}`);
                  logger.log(
                    'network',
                    `Translation Response: ${getTimestamp()} ${
                      options.langcode
                    } ${resource}`,
                    json,
                  );
                  logger.log('network', output.data);
                  logger.groupEnd('network', 'response');

                  dispatch(a.receiveTranslation(output, resource, options.langcode));

                  // Recursively fetch paginated items.
                  if (json.links && json.links.next.href) {
                    dispatch(_fetchTranslations({
                      endpoint: json.links.next.href,
                      langcode: options.langcode,
                    }));
                  }
                });
              }
              else {
                dispatch(a.failure(
                  `${resp.status}: ${resp.statusText ||
                      'No status message provided'}`,
                  resource,
                ));
              }

              return resp;
            })
            // Catch network error.
            .catch(handleNetworkError(dispatch, resource))
        );
      }

      return makeApiCall();
    };
  }

  // Clear API Errors
  clearErrors() {
    const { resource } = this;

    logger.log('network', `Running Clear errors on ${resource}.`);

    return (dispatch) => {
      dispatch(a.clearErrors(resource));
    };
  }

  // Clear API Errors
  markDirty() {
    const { resource } = this;

    logger.log('network', `Marking all ${resource} items as dirty.`);

    return (dispatch) => {
      dispatch(a.markDirty(resource));
    };
  }

  // Purge local store
  purge() {
    const { resource } = this;

    return (dispatch) => {
      dispatch(a.purge(resource));
    };
  }

  // Reset API store
  reset() {
    const { resource } = this;

    return (dispatch) => {
      dispatch(a.reset(resource));
    };
  }

  /**
   * Syncs data using either POST or PATCH based on the saved status of the entity.
   * @param  {String} uuid   UUID of the entity to create remotely.
   * @return {Function}      Redux thunk.
   */
  removeRelationship(relationship, uuid) {
    const {
      backoffFetch, bundle, model, resource, type,
    } = this;
    const { getRequest, getRelationshipEndpoint } = this.constructor;

    return (dispatch, getState) => {
      const state = getState();
      const entity = state[resource].items[uuid];

      // Abort if a request is already in progress.
      // or if this request previously errored.
      // @todo Determine a better way to handle errors. The current implementation
      // will prevent an infinite loop of error requests but will also prevent
      // reattempts in case of network errors.
      // if (entity.state.syncing) {
      if (entity.state.syncing || entity.state.error) {
        return Promise.resolve('Aborted');
      }

      const method = 'PATCH';

      const data = {
        data: null,
      };

      const endpointParts = {
        type,
        bundle,
        relationship,
        uuid,
      };

      const endpoint = getRelationshipEndpoint(endpointParts);

      const request = getRequest(endpoint, {
        method,
        body: JSON.stringify(data),
      });

      logger.log('network', method, request);

      // Dispatch generic api request action.
      dispatch(a.request(resource, uuid));

      function makeApiCall() {
        return (
          backoffFetch(request, responseHandler, errorHandler)
            .then(resp =>
              handleResponse(resp, request, dispatch, resource, model, uuid))
            // Catch network error.
            .catch(handleNetworkError(dispatch, resource, uuid))
        );
      }

      return makeApiCall();
    };
  }

  /**
   * Syncs data using either POST or PATCH based on the saved status of the entity.
   * @param  {String} uuid   UUID of the entity to create or update remotely.
   * @return {Function}      Redux thunk.
   */
  sync(uuid, options) {
    const {
      backoffFetch, model, include, resource,
    } = this;
    const { getRequest } = this.constructor;

    const updateRelationshipsIfNeeded = this.updateRelationshipsIfNeeded.bind(this);

    return (dispatch, getState) => {
      const state = getState();
      const entity = state[resource].items[uuid];

      // Abort if a request is already in progress.
      // or if this request previously errored.
      // @todo Determine a better way to handle errors. The current implementation
      // will prevent an infinite loop of error requests but will also prevent
      // reattempts in case of network errors.
      if (entity.state.syncing) {
        return Promise.reject(new Error('Entity already syncing.'));
      }

      if (entity.state.error) {
        return Promise.reject(new Error('Will not retry a request with an error state.'));
      }

      // Has this entity successfully saved to remotely?
      const { saved } = entity.state;
      // Determine the HTTP method based on saved status.
      const method = saved ? 'PATCH' : 'POST';
      // Format for local entity data for jsonapi
      const data = EntityModel.export(entity, state);

      //
      // Create API endpoint string
      //
      const endpointParts = {
        include,
      };
      // Add the uuid to the endpoint if this entity exists remotely.
      if (saved) {
        assign(endpointParts, { id: uuid });
      }
      const endpoint = options.endpoint || this.getEndpoint(endpointParts);

      const request = getRequest(endpoint, {
        ...options,
        method,
        body: JSON.stringify(data),
      });

      function makeApiCall() {
        return backoffFetch(request, responseHandler, errorHandler)
          .then(resp =>
            handleResponse(resp, request, dispatch, resource, model, uuid))
          .then((action) => {
            // Abort if this is a failure.
            if (action.type === t.FAILURE) {
              return;
            }
            // If this is an update operation, we need to update relationships as well.
            else if (saved) {
              return updateRelationshipsIfNeeded(
                dispatch,
                entity.data,
                action.resp.data,
              );
            }
          })
          .catch((err) => {
            logger.log('network', 'we give up', err);
            handleNetworkError(dispatch, resource, uuid)(err);
            return Promise.reject(err);
          });
      }

      logger.log('network', method, request);

      // Dispatch generic api request action.
      dispatch(a.request(resource, uuid));

      return makeApiCall();
    };
  }

  /**
   * Compares local data with remote data to determine if we need to remove any entity references.
   * @param  {Function} dispatch Redux dispatch function.
   * @param  {Object}   localData   Entity data from the local redux store.
   * @param  {Object}   remoteData  Entity data from the remote server.
   */
  updateRelationshipsIfNeeded(dispatch, localData, remoteData) {
    const relationships = this.model.getRelationshipAliases();
    const dirtyRelationships = filter(
      relationships,
      r =>
        // If a relationship exists remotely but not locally, it's dirty.
        !localData[r] && localData[r] !== remoteData[r],
    );
    const removeRelationship = this.removeRelationship.bind(this);

    return Promise.all(dirtyRelationships.map(r =>
      dispatch(removeRelationship(this.model.getPropertyFromAlias(r), localData.uuid))));
  }

  /**
   * Normalizes the resolution and rejection of a fetch request.
   * @param  {Request} request
   *  The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
   * @param  {responseHandlerCallback} responseHandler
   *  The callback that handles the response.
   * @param  {errorHandlerCallback} errorHandler
   *  The callback that handles the error from a failed request.
   * @return {Promise}
   *  A promise that will resolve if the fetch response is OK. It will reject otherwise.
   */
  wrapFetch(request) {
    return () => {
      return new Promise((resolve, reject) => {
        // Fetch the request.
        fetch(request.clone())
          // Handle a successful request.
          .then(responseHandler(request, resolve, reject))
          .catch(errorHandler(request, resolve, reject));
      });
    };
  }

  /**
   * Retries a failed request using Exponential Backoff.
   * @param  {Request} request
   *  The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
   * @param  {responseHandlerCallback} responseHandler
   *  The callback that handles the response.
   * @param  {errorHandlerCallback} errorHandler
   *  The callback that handles the error from a failed request.
   * @return {Promise}
   *  A wrapped fetch request that will retry if it encounters a failure.
   */
  backoffFetch(request) {
    return exponentialBackoff(this.wrapFetch(request));
  }
};

/**
 * Initial state of an api data store.
 * @type {Object}
 */
const initialDataState = {
  items: {},
  validating: false,
  syncing: false,
  error: null,
  updated: null,
};

/**
 * Creates a new data item from an api call.
 * @param  {Object} data Data used to populate item.data.
 * @return {Object}      A new item with data and state properties populated.
 */
function itemImport(data) {
  const output = {
    data,
    state: {
      syncing: false,
      saved: true,
      error: null,
      dirty: false,
    },
  };

  return output;
}

/**
 * Creates a new data item from an existing item, overriding any specified data properties.
 * @param  {Object} item Existing item from the store
 * @param  {Object} data Data used to populate item.data.
 * @return {Object}      A new item with data and state properties populated.
 */
function itemEdit(item, data) {
  const output = assign({}, item);
  output.data = {
    ...item.data,
    ...data,
  };
  output.state = {
    ...item.state,
    error: null,
    dirty: true,
  };
  return output;
}

function editItems(items, data) {
  const output = assign({}, items);

  // Loop through each new data point.
  forEach(data, (d) => {
    output[d.uuid] = itemEdit(items[d.uuid], d);
  });

  return output;
}

function mergeProp(prop, x, y) {
  if (x[prop] || y[prop]) {
    return {
      ...x[prop],
      ...y[prop],
    };
  }
}

/**
 * Creates a new data item from an existing item, overriding data properties from an api response.
 * @param  {Object} item Existing item from the store
 * @param  {Object} data Data used to populate item.data.
 * @return {Object}      A new item with data and state properties populated.
 */
function itemUpdate(item, input) {
  const output = Object.assign({}, item);

  output.data = item.data || {};

  output.data.attributes = mergeProp('attributes', item.data, input);
  output.data.relationships = mergeProp('relationships', item.data, input);
  output.data.meta = mergeProp('meta', item.data, input);
  output.data.links = mergeProp('links', item.data, input);

  output.state = {
    ...item.state,
    saved: true,
  };

  return output;
}

/**
 * Creates a new data item from an existing item, overriding data properties from an api response.
 * @param  {Object} item Existing item from the store
 * @param  {Object} data Data used to populate item.data.
 * @return {Object}      A new item with data and state properties populated.
 */
function itemUpdateTimestamps(item, data) {
  const output = assign({}, item);
  output.data = item.data;

  const limitFieldsTo = ['created', 'changed', 'id'];

  limitFieldsTo.forEach((field) => {
    if (data[field]) {
      output.data[field] = data[field];
    }
  });

  output.state = {
    ...item.state,
    saved: true,
  };
  return output;
}

/**
 * Prepares an item from an api call to be merged into the items collection.
 * @param  {Object} items Collection of data items.
 * @param  {Object} data    The new data to be merged from an api response.
 * @param  {String} mergeStrategy  The merge strategy for handling incoming data
 * @return {Object}         A new item with data and state properties populated.
 */
function mergeItem(items, data, mergeStrategy) {
  switch (mergeStrategy) {
    case 'mergeNew':
      // Only add new items.
      return data.id in items ? items[data.id] : itemImport(data);
    default:
      // Update existing items.
      return data.id in items
        ? itemUpdate(items[data.id], data)
        : itemImport(data);
  }
}

/**
 * Merges items from an api response into an existing collection.
 * @param  {Object} items
 *  Collection of existing items.
 * @param  {Array}  data
 *  An array of items from an api response.
 * @param  {String} mergeStrategy
 *  The merge strategy for handling incoming data
 * @return {Object}
 *  A new collection with items from the api response merged into the existing collection.
 */
function mergeItems(items, data, mergeStrategy) {
  const output = assign({}, items);

  // Loop through each new data point.
  forEach(data, (d) => {
    output[d.id] = mergeItem(items, d, mergeStrategy);
  });

  return output;
}

/**
 * Resets the error state of all items to null.
 *   When setting the dirty state we check for an error status as early < 1.1.4.1 versions
 *    of the app would not reset the dirty value to true after a failed response.
 * @param  {Object} items Collection of existing items.
 * @return {Object}       A new collection with items with errors set to null.
 */
function clearItemsErrors(items) {
  return mapValues(items, item => ({
    ...item,
    state: {
      ...item.state,
      dirty:
        item.state.dirty ||
        item.state.error !== null ||
        item.state.syncing ||
        false,
      syncing: false,
      error: null,
    },
  }));
}

/**
 * Sets the state of all items to dirty.
 * @param  {Object} items Collection of existing items.
 * @return {Object}       A new collection with items with errors set to null.
 */
function markItemsDirty(items) {
  return mapValues(items, item => ({
    ...item,
    state: {
      ...item.state,
      dirty: true,
    },
  }));
}

/**
 * Prepares an item from an api call to be merged into the items collection.
 * @param  {Object} items Collection of data items.
 * @param  {Object} data    The new data to be merged from an api response.
 * @param  {String} mergeStrategy  The merge strategy for handling incoming data
 * @param  {String} lancode  The 2 letter ISO_639-1 language code.
 * @return {Object}         A new item with data and state properties populated.
 */
function mergeTranslation(items, data, mergeStrategy, langcode) {
  const output = assign({}, items[data.uuid]);
  const translations =
    'translations' in output.data ? output.data.translations : {};
  output.data.translations = assign(translations, { [langcode]: data });

  return output;
}

/**
 * Merges items from an api response into an existing collection.
 * @param  {Object} items
 *  Collection of existing items.
 * @param  {Array}  data
 *  An array of items from an api response.
 * @param  {String} mergeStrategy
 *  The merge strategy for handling incoming data
 * @param  {String} lancode
 *  The 2 letter ISO_639-1 language code.
 * @return {Object}
 *  A new collection with items from the api response merged into the existing collection.
 */
function mergeTranslations(items, data, mergeStrategy, langcode) {
  const output = assign({}, items);

  // Loop through each new data point.
  forEach(data, (d) => {
    if (output[d.uuid]) {
      output[d.uuid] = mergeTranslation(items, d, mergeStrategy, langcode);
    }
  });

  return output;
}

/**
 * Generic API reducer for dealing with single items.
 * @param  {Object} state  Current state of the Redux store
 * @param  {Object} action Flux standard action
 * @return {Object}        The altered state of the store
 */
export function dataReducer(state = initialDataState, action, mergeStrategy) {
  // Grab common variables from the action payload.
  const { id, data } = action;

  let item = {};

  // Exit if we the item doesn't already exist and we're trying to do something other than updating.
  if (id in state === false && [t.ADD, t.RECEIVE].indexOf(action.type) < 0) {
    return state;
  }

  // Create a copy of the item.
  item = assign({}, state[id]);

  switch (action.type) {
    case t.CLEAR_ERRORS:
      item.state = {
        ...item.state,
        dirty:
          item.state.dirty ||
          item.state.error !== null ||
          item.state.syncing ||
          false,
        syncing: false,
        error: null,
      };
      break;
    case t.SET_SAVED:
      item.state = {
        ...item.state,
        dirty: true,
        error: null,
        saved: action.value,
      };
      break;
    case t.MARK_DIRTY:
      item.state = {
        ...item.state,
        dirty: true,
      };
      break;
    case t.REQUEST:
      item.state = {
        ...item.state,
        syncing: true,
        dirty: false,
      };
      break;
    case t.RECEIVE:
      if (id in state === false) {
        item.data = action.resp.data;
        item.state = {
          dirty: false,
          saved: true,
          syncing: false,
          error: null,
        };
      }
      else {
        // If the item is now dirty, let's not update it again as we will
        //   lose changes and need to sync again anyway.
        if (action.resp.data && !item.state.dirty) {
          // item.data = action.resp.data;
          item =
            mergeStrategy === 'mergeNew'
              ? itemUpdateTimestamps(item, action.resp.data)
              : itemUpdate(item, action.resp.data);
        }
        item.state = {
          ...item.state,
          saved: true,
          syncing: false,
          error: null,
        };
      }
      break;
    case t.FAILURE:
      item.state = {
        ...item.state,
        syncing: false,
        error: action.error,
        dirty: true,
      };
      break;
    case t.ADD:
      item.data = data;
      item.state = {
        saved: false,
        syncing: false,
        error: null,
        dirty: true,
      };
      break;
    case t.EDIT:
      item = itemEdit(item, data);
      break;
    default:
      break;
  }

  return assign({}, state, { [id]: item });
}

/**
 * Creates an api Redux reducer for a specific resource type
 * @param  {String} resource JSON_API resource type.
 * @param  {String} mergeStrategy  The merge strategy for handling incoming data
 * @return {Function}        Reducer function for handling API data
 */
export function apiReducer(resource, mergeStrategy) {
  return (state = initialDataState, action) => {
    // Only respond to the actions we care about.
    if (
      [
        t.CLEAR_ERRORS,
        t.SET_SAVED,
        t.SET_VALIDATING,
        t.REQUEST,
        t.RECEIVE,
        t.RECEIVE_TRANSLATION,
        t.FAILURE,
        t.MARK_DIRTY,
        t.PURGE,
        t.RESET,
        t.SET_TIMESTAMP,
        t.ADD,
        t.EDIT,
      ].indexOf(action.type) === -1
    ) {
      return state;
    }

    // Return State if this is not the resource we care about.
    if (action.resource !== resource) {
      return state;
    }

    //
    // Handle full collection actions.
    //
    if (!action.id) {
      switch (action.type) {
        case t.CLEAR_ERRORS:
          return {
            ...state,
            items: clearItemsErrors(state.items),
            syncing: false,
            error: null,
          };
        case t.MARK_DIRTY:
          return {
            ...state,
            items: markItemsDirty(state.items),
          };
        case t.REQUEST:
          return {
            ...state,
            syncing: true,
          };
        case t.SET_TIMESTAMP:
          return {
            ...state,
            updated: action.timestamp,
          };
        case t.RECEIVE:
          return {
            ...state,
            items: mergeItems(state.items, action.resp.data, mergeStrategy),
            syncing: false,
            error: null,
          };
        case t.RECEIVE_TRANSLATION:
          return {
            ...state,
            // @todo this will override all local data with the response.
            // Fine for read-only resources ie. Testlet, Testlet Items
            // Need a merge strategy for other Students, Classes, Assessments etc.
            items: mergeTranslations(
              state.items,
              action.resp.data,
              mergeStrategy,
              action.langcode,
            ),
            syncing: false,
            error: null,
          };
        case t.PURGE:
          // Removes all locally stored items!
          return {
            ...state,
            items: {},
            syncing: false,
            error: null,
            updated: null,
          };
        case t.FAILURE:
          // Log failure remotely.
          return {
            ...state,
            isFetching: false,
            error: action.error,
          };
        case t.RESET:
          return {
            ...state,
            syncing: false,
            error: null,
            updated: null,
          };
        case t.SET_VALIDATING:
          return {
            ...state,
            validating: action.value,
          };
        case t.EDIT:
          return {
            ...state,
            items: editItems(state.items, action.data),
          };
        default:
          return state;
      }
    }

    //
    // Handle single entity requests
    //
    return {
      ...state,
      items: dataReducer(state.items, action, mergeStrategy),
    };
  };
}
