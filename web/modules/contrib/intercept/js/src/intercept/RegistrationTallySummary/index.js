import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

// Lodash
import get from 'lodash/get';

/* eslint-disable */
// Intercept
import interceptClient from 'interceptClient';
import TallySummary from 'intercept/TallySummary';
/* eslint-enable */

const { select, constants } = interceptClient;
const c = constants;

function RegistrationTallySummary(props) {
  const { registration } = props;

  return registration ? (
    <TallySummary
      key={get(registration, 'data.id')}
      id={get(registration, 'data.id')}
      valuePath={'data.relationships.field_registrants.data'}
      type={c.TYPE_EVENT_REGISTRATION}
    />) : null;
}

RegistrationTallySummary.propTypes = {
  registration: PropTypes.object,
};

RegistrationTallySummary.defaultProps = {
  registration: null,
};

const mapStateToProps = (state, ownProps) => {
  const identifier = select.getIdentifier(c.TYPE_EVENT_REGISTRATION, ownProps.id);

  return {
    registration: select.record(identifier)(state),
  };
};

export default connect(mapStateToProps)(RegistrationTallySummary);
