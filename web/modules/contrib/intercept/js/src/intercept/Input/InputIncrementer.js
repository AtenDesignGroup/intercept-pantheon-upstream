import React from 'react';
import PropTypes from 'prop-types';
import { withFormsy, propTypes, defaultProps } from 'formsy-react';
import RemoveIcon from '@material-ui/icons/Remove';
import AddIcon from '@material-ui/icons/Add';
import { withStyles } from '@material-ui/core/styles';

import { FormControl, Input, FormLabel, Button } from '@material-ui/core';

const styles = theme => ({
  container: {
    display: 'flex',
    flexWrap: 'nowrap',
    flexDirection: 'row',
    alignItems: 'stretch',
  },
  inputWrapper: {
    borderBottom: `1px solid ${theme.palette.grey[300]}`,
    borderTop: `1px solid ${theme.palette.grey[300]}`,
    fontSize: 'inherit',
    margin: 0,
    textAlign: 'center',
    display: 'flex',
    alignItems: 'center',
  },
  input: {
    appearance: 'none',
    margin: 0,
    textAlign: 'center',
    fontSize: 'inherit',
    width: '2em',
    padding: '.25em 0 .25em .5em',
  },
  button: {
    fontSize: '1em',
    minWidth: 0,
    padding: '.4em',
  },
  buttonFirst: {
    borderRadius: '.3em 0 0 .3em',
  },
  buttonLast: {
    borderRadius: '0 .3em .3em 0',
    marginRight: '1em',
  },
  icon: {
    fontSize: '1em !important',
    height: '1em !important',
    width: '1em !important',
  },
  label: {
    marginBottom: 'auto',
    marginTop: 'auto',
    fontWeight: '700',
    fontSize: `${14 / 18}em`,
    fontFamily: 'inherit',
  },
});

class InputIncrementer extends React.PureComponent {
  render() {
    const { classes, step, label, onChange, min, max, int, required, name, value } = this.props;

    const inputId = `input-increment--${name}`;

    const updateValue = (v) => {
      this.props.setValue(v);
      onChange(v);
    };

    const handleChange = (event) => {
      const parse = int ? parseInt : parseFloat;
      const v = event.target.value ? parse(event.target.value) : null;
      updateValue(v);
    };

    const decrement = () => updateValue(value - 1);
    const increment = () => updateValue(value + 1);

    return (
      <div className="input input--incrementer">
        <FormControl className={classes.container} aria-live="polite">
          <Button
            className={[classes.button, classes.buttonFirst].join(' ')}
            aria-label="Decrement"
            aria-controls={inputId}
            variant="contained"
            disabled={typeof min === 'number' && value <= min}
            onClick={decrement}
          >
            <RemoveIcon className={classes.icon} />
          </Button>
          <Input
            disableUnderline
            className={classes.inputWrapper}
            type="number"
            value={value}
            onChange={handleChange}
            step={step}
            id={inputId}
            inputProps={{
              className: classes.input,
              step,
              min,
              max,
            }}
          />
          <Button
            className={[classes.button, classes.buttonLast].join(' ')}
            aria-label="Increment"
            aria-controls={inputId}
            variant="contained"
            disabled={typeof max === 'number' && value >= max}
            onClick={increment}
          >
            <AddIcon className={classes.icon} />
          </Button>
          <FormLabel htmlFor={inputId} className={classes.label}>
            {label}
          </FormLabel>
        </FormControl>
      </div>
    );
  }
}

InputIncrementer.propTypes = {
  ...propTypes,
  onChange: PropTypes.func.isRequired,
  value: PropTypes.number,
  label: PropTypes.string,
  step: PropTypes.number,
  int: PropTypes.bool,
};

InputIncrementer.defaultProps = {
  ...defaultProps,
  value: 0,
  label: 'Number',
  step: 1,
};

export default withFormsy(withStyles(styles)(InputIncrementer));
