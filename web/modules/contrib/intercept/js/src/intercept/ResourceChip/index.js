import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import interceptClient from 'interceptClient';
import OptionChip from '../OptionChip';

const { select } = interceptClient;

const ResourceChip = (props) => {
  const { identifier, onDelete, label } = props;
  const { id } = identifier;

  return (
    <OptionChip key={id} label={label} onDelete={() => onDelete(id)} onClick={() => onDelete(id)} />
  );
};

ResourceChip.propTypes = {
  identifier: PropTypes.shape({
    id: PropTypes.string,
    type: PropTypes.string,
  }).isRequired,
  label: PropTypes.string,
  onDelete: PropTypes.func,
};

ResourceChip.defaultProps = {
  onDelete: null,
  label: '',
};

const mapStateToProps = (state, ownProps) => ({
  label: select.recordLabel(ownProps.identifier)(state),
});

export default connect(mapStateToProps)(ResourceChip);
