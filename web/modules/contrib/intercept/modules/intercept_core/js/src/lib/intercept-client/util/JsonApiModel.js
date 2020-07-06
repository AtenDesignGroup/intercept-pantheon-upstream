/* eslint no-param-reassign: ["error", { "props": false }] */
/* eslint no-return-assign: ["error", "except-parens"] */
import assign from 'lodash/assign';
import forEach from 'lodash/forEach';
import map from 'lodash/map';

export const JsonApiSchema = class {
  constructor(resource, schema) {
    this.type = resource;
    this.schema = schema;
  }

  /**
   * Sets one or more values on a model returning a new instance.
   * @param {string|object} field If a string, this is the field from the schema to set.
   *                              If an object, it's a set of field value pairs.
   * @param {string|array|onject|boolean} value The value to be set. Ignored if field is an object.
   */
  setValue(field, value) {
    const newModel = new this.constructor();

    if (typeof field === 'string') {
      newModel.setField(field, value);
    }
    else {
      forEach(field, (val, key) => {
        newModel.setField(key, val);
      });
    }
    return assign(new this.constructor(), this, newModel);
  }

  /**
   * Sets a single field value on a model
   * @param {string} field The field from the schema to set.
   * @param {string|array|object|boolean} value The value to be set
   */
  setField(field, value) {
    const { schema } = this;

    if (field in schema === false) {
      return this;
    }

    switch (schema.type) {
      case 'relationship':
        this.relationships[field] = {
          data: {
            type: schema[field].ref,
            id: value
          }
        };
        break;
      default:
        this.attributes[field] = value;
    }
  }
};

/**
 * Get a JSON_API attribute getter for a resource.
 * @param  {Object} resource  JSON_API formatted resource.
 * @param  {String} attribute The resource attribute to get
 * @return {function} A getter for the attribute.
 */
function getAttribute(resource, attribute) {
  return () => resource._data.attributes[attribute];
}

/**
 * Get a JSON_API attribute setter for a resource.
 * @param  {Object} resource  JSON_API formatted resource.
 * @param  {String} attribute The resource attribute to set
 * @return {function} A setter for the attribute.
 */
function setAttribute(resource, attribute) {
  return value => (resource._data.attributes[attribute] = value);
}

/**
 * Get a JSON_API relationship getter for a resource.
 * @param  {Object}   resource      JSON_API formatted resource.
 * @param  {String}   relationship  The resource relationship to get
 * @param  {Boolean}  multiple      Whether or nohis is a multi-value relationship
 * @return {function} A getter for the relationship.
 */
function getRelationship(resource, relationship, multiple) {
  return () => {
    if (relationship in resource._data.relationships === false) {
      return undefined;
    }

    //
    // Handle multi-value fields
    //
    if (multiple) {
      return map(
        resource._data.relationships[relationship].data,
        d => (d ? d.id : d)
      );
    }

    //
    // Handle single value fields
    //
    return resource._data.relationships[relationship].data
      ? resource._data.relationships[relationship].data.id
      : resource._data.relationships[relationship].data;
  };
}

/**
 * Get a JSON_API relationship setter for a resource.
 * @param  {Object}   resource      JSON_API formatted resource.
 * @param  {String}   relationship  The resource relationship to set
 * @param  {Boolean}  multiple      Whether or not this is a multi-value relationship
 * @return {function} A setter for the relationship.
 */
function setRelationship(resource, relationship, type, multiple) {
  return value => {
    if (relationship in resource._data.relationships === false) {
      resource._data.relationships[relationship] = {};
    }

    //
    // Handle multi-value fields
    //
    if (multiple) {
      const valueArray = [].concat(value);
      return (resource._data.relationships[relationship].data = map(
        valueArray,
        v => ({
          type,
          id: v
        })
      ));
    }

    //
    // Handle single value fields
    //
    return (resource._data.relationships[relationship].data = {
      type,
      id: value
    });
  };
}

export const JsonApiModel = class {
  constructor(schema) {
    this._data = {
      type: schema.type,
      attributes: {},
      relationships: {}
    };

    // Create getters and setters for each prop.
    forEach(schema.schema, (value, key) => {
      Object.defineProperty(this, value.alias ? value.alias : key, {
        get:
          value.type === 'relationship'
            ? getRelationship(this, key, value.multiple)
            : getAttribute(this, key),
        set:
          value.type === 'relationship'
            ? setRelationship(this, key, value.ref, value.multiple)
            : setAttribute(this, key)
      });
    });
  }
};
