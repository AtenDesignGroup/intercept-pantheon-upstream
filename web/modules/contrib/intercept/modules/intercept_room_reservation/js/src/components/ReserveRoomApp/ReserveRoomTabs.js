import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import { Tabs, Tab } from '@material-ui/core';

const styles = theme => ({
  root: {
    flexGrow: 1,
    backgroundColor: theme.palette.background.paper,
  },
  tabsRoot: {
    borderBottom: '1px solid #e8e8e8',
  },
  tabsIndicator: {
    backgroundColor: '#1890ff',
  },
  tabRoot: {
    textTransform: 'initial',
    minWidth: 72,
    letterSpacing: 0,
    marginRight: theme.spacing(4),
    '&:hover': {
      // color: '#40a9ff',
      opacity: 1,
    },
    '&$tabSelected': {
      // color: '#1890ff',
      fontWeight: theme.typography.fontWeightMedium,
    },
    '&:focus': {
      // color: '#40a9ff',
    },
  },
  tabSelected: {},
  typography: {
    padding: theme.spacing(3),
  },
});

class ReserveRoomTabs extends React.Component {
  state = {
    value: 0,
  };

  handleChange = (event, value) => {
    this.setState({ value });
  };

  render() {
    const { classes } = this.props;
    const { value } = this.state;

    return (
      <div className={classes.root}>
        <Tabs
          value={value}
          onChange={this.handleChange}
          classes={{ root: classes.tabsRoot, indicator: classes.tabsIndicator }}
        >
          <Tab
            disableRipple
            classes={{ root: classes.tabRoot, selected: classes.tabSelected }}
            label="Find a Room"
          />
          <Tab
            disableRipple
            classes={{ root: classes.tabRoot, selected: classes.tabSelected }}
            label="Find a Time"
          />
        </Tabs>
      </div>
    );
  }
}

ReserveRoomTabs.propTypes = {
  classes: PropTypes.object.isRequired,
};

export default withStyles(styles)(ReserveRoomTabs);
