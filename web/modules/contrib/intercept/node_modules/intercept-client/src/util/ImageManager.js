import forEach from 'lodash/forEach';
import { EntityModel } from './EntityModel';

/**
 * An EntityModel is meant to manage the back and forth transformation between
 *  JSON_API structured resources and locally stored entity objects.
 *  EntityModel instances are created on a per resource type basis.
 *
 * @type {EntityModel}
 */
export class ImageModel extends EntityModel {

  /**
   * Converts JSON_API formated Entity into a plain object.
   * @param  {Object} entity JSON_API formated object
   * @return {Object}        Plain object representation of the data.
   */
  import(entity) {
    const { model } = this;
    // Set the models data property to match the entity's
    model._data = entity;

    const data = {};

    // Set each prop listed in the schema.
    forEach(this.schema, (value, key) => {
      const prop = value.alias ? value.alias : key;

      // Translations is a special property that does not map to a field.
      if (prop !== 'translations') {
        data[prop] = model[prop];
      }
    });

    return data;
  }
}

export default EntityModel;
