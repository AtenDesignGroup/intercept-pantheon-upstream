import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import interceptClient from 'interceptClient';

const { select } = interceptClient;

const ResourceLabel = (props) => {
  const { identifier, label } = props;
  const { id } = identifier;

  return (
    <div key={id} className="option-label">{label}</div>
  );
};

ResourceLabel.propTypes = {
  identifier: PropTypes.shape({
    id: PropTypes.string,
    type: PropTypes.string,
  }).isRequired,
  label: PropTypes.string,
};

ResourceLabel.defaultProps = {
  label: '',
};

const mapStateToProps = (state, ownProps) => ({
  label: select.recordLabel(ownProps.identifier)(state),
});

export default connect(mapStateToProps)(ResourceLabel);
