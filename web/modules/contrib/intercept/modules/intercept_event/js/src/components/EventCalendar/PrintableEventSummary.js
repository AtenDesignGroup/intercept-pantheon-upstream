import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import get from 'lodash/get';

import interceptClient from 'interceptClient';

const { constants, select, utils } = interceptClient;
const c = constants;

function PrintableEventSummary(props) {
  const { event } = props;

  // Return if bundle has not loaded.
  if (!event.attributes) {
    return null;
  }

  const title = get(event, 'attributes.title');
  const dateStart = utils.dateFromDrupal(event.attributes['field_date_time'].value);
  const time = utils.getTimeDisplay(dateStart);
  // const time = `${utils.getTimeDisplay(dateStart)} - ${utils.getTimeDisplay(dateEnd)}`;
  const location = get(
    event,
    'relationships.field_location.attributes.field_location_abbreviation',
  );
  // const label = event.attributes.field_must_register ? 'Registration Required' : null;

  return (
    <p className={'print-event'}>
      <span className={'print-event__title'}>{title}</span> <span className={'print-event__location'}>{location}</span>
      <span className={'print-event__time'}>{time}</span>
    </p>
  );
}

PrintableEventSummary.propTypes = {
  id: PropTypes.string.isRequired,
  event: PropTypes.object.isRequired,
};

const mapStateToProps = (state, ownProps) => {
  const identifier = select.getIdentifier(c.TYPE_EVENT, ownProps.id);

  return {
    event: select.bundle(identifier)(state),
  };
};

export default connect(mapStateToProps)(PrintableEventSummary);
