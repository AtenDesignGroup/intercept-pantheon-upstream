import React from 'react';
import { Chip } from '@material-ui/core';

class OptionChip extends React.PureComponent {
  render() {
    const icon = (
      <svg width="8" height="8" xmlns="http://www.w3.org/2000/svg">
        <g fill="#4C4D4F" fillRule="evenodd">
          <path d="M.053 7l7-7 .893.894-7 7z" />
          <path d="M7.054 7.893l-7-7L.947.002l7 7z" />
        </g>
      </svg>
    );

    return (<Chip
      {...this.props}
      className="option-chip"
      deleteIcon={icon}
    />);
  }
}

export default OptionChip;
