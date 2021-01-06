import React from 'react';
import PropTypes from 'prop-types';

import get from 'lodash/get';

import interceptClient from 'interceptClient';

const { constants, utils } = interceptClient;
const c = constants;

function PrintableClosedSummary(props) {
  const { closing } = props;

  const title = get(closing, 'attributes.title');
  const dateStart = utils.dateFromDrupal(closing.start);
  const time = utils.getTimeDisplay(dateStart);
  // const time = `${utils.getTimeDisplay(dateStart)} - ${utils.getTimeDisplay(dateEnd)}`;
  const message = get(closing, 'message');
  // const label = event.attributes.field_must_register ? 'Registration Required' : null;

  return (
    <p className={'print-event print-event--highlight'}>
      <span className={'print-event__title'}>{title}</span> <span className={'print-event__location'}>{message}</span>
      <span className={'print-event__time'}>{time}</span>
    </p>
  );
}

PrintableClosedSummary.propTypes = {
  closing: PropTypes.object.isRequired,
};

export default PrintableClosedSummary;
