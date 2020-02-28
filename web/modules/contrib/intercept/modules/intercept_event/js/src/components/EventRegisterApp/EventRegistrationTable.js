import React from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

import { withStyles } from '@material-ui/core/styles';
import { isWidthUp } from '@material-ui/core/withWidth';

// Lodash
import get from 'lodash/get';

/* eslint-disable */
import interceptClient from 'interceptClient';
import RegistrationTallySummary from 'intercept/RegistrationTallySummary';
import RegistrationStatus from 'intercept/RegistrationStatus';
/* eslint-enable */
import EventRegistrationActions from '../EventRegistrationActions';

import { withWidth, Table, TableBody, TableCell, TableHead, TableRow } from '@material-ui/core';

const { constants, select, utils } = interceptClient;
const c = constants;

const defaultUserId = utils.getUserUuid();

const styles = theme => ({
  root: {
    width: '100%',
    marginTop: theme.spacing(3),
    overflowX: 'auto',
  },
  table: {
    minWidth: 700,
  },
  lastColumn: {
    paddingRight: 0,

    '&:last-child': {
      paddingRight: 0,
    },
  },
  canceled: {
    opacity: '.6',
  },
});

const getData = registrations =>
  registrations.map(registered => ({
    id: get(registered, 'data.id'),
    status: get(registered, 'data.attributes.status'),
  }));

function EventRegistrationTable(props) {
  const { classes, registrations, width } = props;
  const data = getData(registrations);

  // const getEventAction = id => getAction(id, eventId);

  if (isWidthUp('md', width)) {
    return (
      <Table className={classes.table}>
        <TableHead>
          <TableRow>
            <TableCell>Registration</TableCell>
            <TableCell>Status</TableCell>
            <TableCell align="right" />
          </TableRow>
        </TableHead>
        <TableBody>
          {data.map(n => (
            <TableRow key={n.id} className={n.status === 'canceled' ? classes.canceled : ''}>
              <TableCell>
                <RegistrationTallySummary id={n.id} />
              </TableCell>
              <TableCell>{n.status}</TableCell>
              <TableCell align="right" className={classes.lastColumn}>
                <EventRegistrationActions registrationId={n.id} />
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    );
  }

  return (
    <div className={'l--subsection'}>
      <h4>Existing Registrations</h4>
      {data.map(n => (
        <div
          key={n.id}
          className={`metadata metadata--${n.status}`}
        >
          <div className="metadata__item metadata__item--block">
            <h4 className="metadate__title">Attendees:</h4>
            <div className="metadata__content">
              <RegistrationTallySummary id={n.id} />
            </div>
          </div>
          <div className="metadata__item metadata__item--inline">
            <h4 className="metadate__title">Status:</h4>
            <div className="metadata__content">{n.status}</div>
          </div>
          <div className="metadata__footer">
            <EventRegistrationActions registrationId={n.id} />
          </div>
        </div>
      ))}
    </div>
  );
}

EventRegistrationTable.propTypes = {
  eventId: PropTypes.string.isRequired,
  // Connect
  registrations: PropTypes.array,
  // withStyles
  classes: PropTypes.object.isRequired,
};

EventRegistrationTable.defaultProps = {
  registrations: [],
};

const mapStateToProps = (state, ownProps) => ({
  registrations: select.eventRegistrationsByEventByUser(
    ownProps.eventId,
    ownProps.userId || defaultUserId,
  )(state),
  registrationsLoading: select.recordsAreLoading(c.TYPE_EVENT_REGISTRATION)(state),
});

export default withWidth()(withStyles(styles)(connect(mapStateToProps)(EventRegistrationTable)));
