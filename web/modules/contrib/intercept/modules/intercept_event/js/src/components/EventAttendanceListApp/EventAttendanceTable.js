import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import interceptClient from 'interceptClient';
import TallySummary from 'intercept/TallySummary';
import get from 'lodash/get';
import groupBy from 'lodash/groupBy';

import { Table, TableBody, TableCell, TableHead, TableRow } from '@material-ui/core';

const c = interceptClient.constants;

const styles = theme => ({
  root: {
    width: '100%',
    marginTop: theme.spacing(3),
    overflowX: 'auto',
  },
  table: {
    minWidth: 700,
  },
});

const getData = (users, registrations, attendance, savedEvents) => {
  const registrationsByUser = groupBy(registrations, i =>
    get(i, 'data.relationships.field_user.data.id'),
  );
  const attendanceByUser = groupBy(attendance, i =>
    get(i, 'data.relationships.field_user.data.id'),
  );
  const savedByUser = groupBy(savedEvents, i => get(i, 'data.relationships.uid.data.id'));

  return Object.values(users).map((u) => {
    const id = get(u, 'data.id');

    return {
      id,
      name: get(u, 'data.attributes.name'),
      registered: registrationsByUser[id] || [],
      attendance: attendanceByUser[id] || [],
      saved: savedByUser[id] || [],
    };
  });
};

const getAttendance = item => (
  <TallySummary
    key={get(item, 'data.id')}
    id={get(item, 'data.id')}
    valuePath={'data.relationships.field_attendees.data'}
    type={c.TYPE_EVENT_ATTENDANCE}
  />
);

const getRegistered = item => (
  <TallySummary
    key={get(item, 'data.id')}
    id={get(item, 'data.id')}
    valuePath={'data.relationships.field_registrants.data'}
    type={c.TYPE_EVENT_REGISTRATION}
  />
);

const getSaved = item => <p key={get(item, 'data.id')}>Yes</p>;

function EventAttendanceTable(props) {
  const { classes, users, registrations, attendance, savedEvents } = props;
  const data = getData(users, registrations, attendance, savedEvents);

  return (
    <Table className={classes.table}>
      <TableHead>
        <TableRow>
          <TableCell>Name</TableCell>
          <TableCell>Saved</TableCell>
          <TableCell>Registered</TableCell>
          <TableCell>Scanned</TableCell>
        </TableRow>
      </TableHead>
      <TableBody>
        {data.map(n => (
          <TableRow key={n.id}>
            <TableCell component="th" scope="row">
              {n.name}
            </TableCell>
            <TableCell>{n.saved.map(getSaved) || null}</TableCell>
            <TableCell>{n.registered.map(getRegistered) || null}</TableCell>
            <TableCell>{n.attendance.map(getAttendance) || null}</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}

EventAttendanceTable.propTypes = {
  classes: PropTypes.object.isRequired,
  eventId: PropTypes.string.isRequired,
  users: PropTypes.object,
  attendance: PropTypes.object,
  registrations: PropTypes.object,
  savedEvents: PropTypes.object,
};

EventAttendanceTable.defaultProps = {
  users: {},
  attendance: {},
  registrations: {},
  savedEvents: {},
};

export default withStyles(styles)(EventAttendanceTable);
