import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import get from 'lodash/get';
import sortBy from 'lodash/sortBy';

import interceptClient from 'interceptClient';

const { constants, select } = interceptClient;
const c = constants;

function PrintableLocationLegend(props) {
  const { items } = props;

  if (items.length <= 0) {
    return null;
  }

  return (
    <div className={'print-legend'}>
      {items.map(item => (
        <div key={item.id} className={'print-legend__item'}>
          <div className={'print-legend__item-title'}> {item.title}</div>
          {item.phone &&
            <div className={'print-legend__item-address'}> {item.phone}</div>
          }
          {item.addressLine1 &&
            <div className={'print-legend__item-address'}> {item.addressLine1}</div>
          }
          {item.locality && item.administrative_area &&
            <div className={'print-legend__item-address'}> {item.locality}, {item.administrative_area} {item.postal_code}</div>
          }
        </div>
      ))}
    </div>
  );
}

PrintableLocationLegend.propTypes = {
  items: PropTypes.array.isRequired,
  locations: PropTypes.array,
};

PrintableLocationLegend.defaultProps = {
  locations: [],
};

const mapStateToProps = (state, ownProps) => {
  const records = select.records(c.TYPE_LOCATION)(state);

  return {
    items: sortBy(
      ownProps.locations.map(item => ({
        id: get(records[item], 'data.id'),
        title: get(records[item], 'data.attributes.title'),
        phone: get(records[item], 'data.attributes.field_contact_number', null),
        addressLine1: get(records[item], 'data.attributes.field_address.address_line1', null),
        locality: get(records[item], 'data.attributes.field_address.locality', null),
        administrative_area: get(records[item], 'data.attributes.field_address.administrative_area', null),
        postal_code: get(records[item], 'data.attributes.field_address.postal_code', null),
      })).filter(item => item.title),
      ['title'],
    ),
  };
};

export default connect(mapStateToProps)(PrintableLocationLegend);
