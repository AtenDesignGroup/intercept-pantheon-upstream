/* eslint-disable react/no-multi-comp */

import React from 'react';
import PropTypes from 'prop-types';
import interceptClient from 'interceptClient';
import Drupal from 'Drupal';
import { Button } from '@material-ui/core';

import { withStyles } from '@material-ui/core/styles';

import { Dialog } from '@material-ui/core';

const { utils } = interceptClient;

const styles = theme => ({});

const EventListing = ({ event }) => {
  const dateStart = utils.dateFromDrupal(event.attributes['field_date_time'].value);

  return (
    <li className="rbc-more-dialog__item">
      <span className="rbc-more-dialog__item-time">{utils.getTimeDisplay(dateStart)}</span>
      <a className="rbc-more-dialog__item-title" href={event.attributes.path.alias}>{event.attributes.title}</a>
    </li>
  );
};

class ShowMoreDialog extends React.PureComponent {
  render() {
    const { close, onClose, open, events, date } = this.props;
    // The incoming date will be in the local timezone of the browser.
    // We need to convert it to the user's timezone to match the server.
    const dateString = date ? utils.getDayDisplay(utils.convertDateFromLocal(date, utils.getUserTimezone())) : null;

    const content = dateString ? (
      <div className="rbc-more-dialog">
        <header className="rbc-more-dialog__header">
          <h2 className="rbc-more-dialog__date">{dateString}</h2>
        </header>
        <div className="rbc-more-dialog__content">
          <ul className="rbc-more-dialog__list">
            {events.map(event => (<EventListing event={event.data} key={event.data.id} />))}
          </ul>
        </div>
        <footer className="rbc-more-dialog__footer">
          <Button size="small" color="secondary" onClick={onClose}>{Drupal.t('Close')}</Button>
        </footer>
      </div>
    ) : null;

    return (
      <Dialog close={close} onClose={onClose} open={open} keepMounted>
        {content}
      </Dialog>
    );
  }
}

ShowMoreDialog.propTypes = {
  classes: PropTypes.object.isRequired,
  onClose: PropTypes.func,
  open: PropTypes.bool,
  events: PropTypes.arrayOf(PropTypes.object),
  date: PropTypes.instanceOf(Date),
};

ShowMoreDialog.defaultProps = {
  open: false,
  onClose: null,
  id: null,
  loading: false,
  events: [],
  date: new Date(),
};

export default withStyles(styles)(ShowMoreDialog);
