import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';
import get from 'lodash/get';
import interceptClient from 'interceptClient';

const { constants, select } = interceptClient;
const c = constants;

const styles = {
  card: {
    maxWidth: 345,
  },
  media: {
    height: 0,
    paddingTop: '56.25%', // 16:9
  },
};

function TallySummary(props) {
  const { tally } = props;

  return <p>{tally.map(t => `${t.count || 0} ${t.label}`).join(', ')}</p>;
}

TallySummary.propTypes = {
  tally: PropTypes.array,
};

TallySummary.defaultProps = {
  tally: [],
};

const mapStateToProps = (state, ownProps) => {
  const identifier = select.getIdentifier(ownProps.type, ownProps.id);
  const entity = select.record(identifier)(state);
  const segments = select.records(c.TYPE_POPULATION_SEGMENT)(state);
  const tallies = get(entity, ownProps.valuePath);

  const tally = tallies
    .filter(i => get(i, 'meta.count') > 0)
    .map(i => ({
      label: get(segments[i.id], 'data.attributes.name'),
      count: get(i, 'meta.count'),
    }));

  return {
    tally,
  };
};

export default connect(mapStateToProps)(withStyles(styles)(TallySummary));
