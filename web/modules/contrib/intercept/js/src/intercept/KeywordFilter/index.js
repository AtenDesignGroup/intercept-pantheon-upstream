import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import { Input, InputLabel, FormControl } from '@material-ui/core';

const styles = theme => ({
  root: {
    display: 'flex',
    flexWrap: 'wrap',
  },
  formControl: {
    margin: theme.spacing(1),
    minWidth: 120,
    maxWidth: 300,
    width: '100%',
  },
  inputLabel: {
    margin: 0,
  },
});

class KeywordFilter extends React.Component {
  state = {
    name: 'Composed TextField',
  };

  render() {
    const { label, value, id, handleChange } = this.props;
    const htmlId = `keyword-input--${id}`;

    return (
      <div className="keyword-filter input input--text">
        <FormControl className="keyword-filter__control">
          <InputLabel htmlFor={htmlId} className="keyword-filter__label">
            {label}
          </InputLabel>
          <Input id={htmlId} value={value} onChange={handleChange} />
        </FormControl>
      </div>
    );
  }
}

KeywordFilter.propTypes = {
  handleChange: PropTypes.func.isRequired,
  label: PropTypes.string.isRequired,
  value: PropTypes.string,
  id: PropTypes.string,
};

KeywordFilter.defaultProps = {
  value: '',
  id: 'keyword',
};

export default withStyles(styles, { withTheme: true })(KeywordFilter);
