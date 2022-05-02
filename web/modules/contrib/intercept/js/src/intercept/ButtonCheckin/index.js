import React from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

/* eslint-disable */
import Drupal from 'Drupal';
import interceptClient from 'interceptClient';
import { Button } from '@material-ui/core';
/* eslint-enable */

const { select } = interceptClient;

function ButtonCheckin(props) {
  const { checkinStatus, checkinUrl } = props;

  return checkinStatus === 'open' ? (
    <Button
      href={checkinUrl}
      className={'action-button__button'}
      size={'small'}
      variant={'contained'}
      color={'primary'}
    >
      {Drupal.t('Check in')}
    </Button>
  ) : null;
}

ButtonCheckin.propTypes = {
  // Passed Props
  eventId: PropTypes.string.isRequired, // eslint-disable-line react/no-unused-prop-types
  // connect
  checkinStatus: PropTypes.string,
  checkinUrl: PropTypes.string,
};

ButtonCheckin.defaultProps = {
  checkinStatus: 'closed',
  checkinUrl: null,
};

const mapStateToProps = (state, ownProps) => {
  const { eventId } = ownProps;

  // Checkin
  const checkinStatus = select.eventCheckinStatus(eventId)(state);
  const checkinUrl = select.eventCheckinUrl(eventId)(state);

  return {
    checkinStatus,
    checkinUrl,
  };
};

export default connect(mapStateToProps)(ButtonCheckin);
