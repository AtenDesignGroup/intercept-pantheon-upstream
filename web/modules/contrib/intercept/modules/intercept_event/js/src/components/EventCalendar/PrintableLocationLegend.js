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
        <p key={item.abbv} className={'print-legend__item'}>
          <span className={'print-legend__item-abbv'}>{item.abbv}</span>{' '}
          <span className={'print-legend__item-title'}> {item.title}</span>
        </p>
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
        abbv: get(records[item], 'data.attributes.field_location_abbreviation'),
        title: get(records[item], 'data.attributes.title'),
      })).filter(item => item.abbv),
      ['abbv'],
    ),
  };
};

export default connect(mapStateToProps)(PrintableLocationLegend);
