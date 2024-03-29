import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import interceptClient from 'interceptClient';
import OptionChip from '../OptionChip';

const { select } = interceptClient;

const ResourceChip = (props) => {
  const { identifier, onDelete, label } = props;
  const { id } = identifier;

  // Prevent interactions from bubbling. This is necessary because the chip is
  // rendered inside a multi-select input and would otherwise trigger the
  // multi-select to open, rather than deleting the chip.
  const wrapDelete = (event) => {
    if (event && event.stopPropagation !== undefined) {
      event.stopPropagation();
    }
    return onDelete(id);
  };

  return (
    <OptionChip key={id} label={label} onDelete={wrapDelete} onMouseDown={wrapDelete} onClick={wrapDelete} />
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
