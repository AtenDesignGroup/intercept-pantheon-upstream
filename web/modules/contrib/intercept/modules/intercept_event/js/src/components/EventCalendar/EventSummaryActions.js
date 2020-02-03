/* eslint-disable react/no-multi-comp */

import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import interceptClient from 'interceptClient';
import ButtonRegister from 'intercept/ButtonRegister';
import { withStyles } from '@material-ui/core/styles';

import { Button, CardActions } from '@material-ui/core';

const { select, constants } = interceptClient;
const c = constants;

const styles = theme => ({
  root: {
    justifyContent: 'flex-end',
  },
});

const onLearnMore = (event) => {
  const url = event.attributes.path ? event.attributes.path.alias : `/node/${event.attributes.nid}`;
  window.location.href = url;
};

class EventSummaryActions extends React.Component {
  render() {
    const { id, event, classes } = this.props;

    return (
      <CardActions className={classes.root}>
        <Button size="small" color="secondary" onClick={() => onLearnMore(event)}>
          Learn More
        </Button>
        <ButtonRegister {...this.props} event={event} eventId={id} />
      </CardActions>
    );
  }
}

EventSummaryActions.propTypes = {
  classes: PropTypes.object.isRequired,
  id: PropTypes.string,
  event: PropTypes.object,
};

EventSummaryActions.defaultProps = {
  id: null,
  event: null,
};

const mapStateToProps = (state, ownProps) => {
  const identifier = select.getIdentifier(c.TYPE_EVENT, ownProps.id);

  return {
    event: select.bundle(identifier)(state),
  };
};

export default connect(mapStateToProps)(withStyles(styles)(EventSummaryActions));
