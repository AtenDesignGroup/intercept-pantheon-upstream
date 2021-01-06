import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import { withFormsy, propTypes, defaultProps } from 'formsy-react';

import { Input, InputLabel, MenuItem, FormControl, ListItemText, Select } from '@material-ui/core';

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

const ITEM_HEIGHT = 24;
const ITEM_PADDING_TOP = 4;
const MenuListProps = {
  className: 'select-filter__menu-list',
};

const MenuProps = {
  MenuListProps,
  PaperProps: {
    style: {
      // maxHeight: (ITEM_HEIGHT * 8.5) + ITEM_PADDING_TOP,
      maxHeight: 200,
      width: 250,
    },
  },
  getContentAnchorEl: null,
  anchorOrigin: {
    vertical: 'bottom',
    horizontal: 'left',
  },
  className: 'select-filter__menu',
};

class SelectSingle extends React.Component {
  handleChange = (event) => {
    this.props.setValue(event.target.value);
    this.props.handleChange(event);
  };

  render() {
    const { options, label, disabled, isValid, value } = this.props;
    const checkboxId = id => `select-filter--${id}`;
    const checkboxLabel = (text, id) => (
      <label className="select-filter__checkbox-label" htmlFor={id}>
        {text}
      </label>
    );

    return (
      <div className="select-filter input input--select">
        <FormControl className="select-filter__control" disabled={disabled}>
          <InputLabel
            className="select-filter__label"
            htmlFor="select-multiple-chip"
            required={this.props.required}
            shrink={!!value}
          >
            {label}
          </InputLabel>

          <Select
            value={value === null || !value ? '' : value}
            onChange={this.handleChange}
            input={<Input id="select-multiple-chip" />}
            // renderValue={(value) => value}
            MenuProps={MenuProps}
            error={!isValid}
            required={this.props.required}
          >
            {options.map(option => (
              <MenuItem key={option.key} value={option.key} className="select-filter__menu-item">
                <ListItemText
                  disableTypography
                  primary={checkboxLabel(option.value, checkboxId(option.key))}
                />
              </MenuItem>
            ))}
          </Select>
        </FormControl>
      </div>
    );
  }
}

SelectSingle.propTypes = {
  ...propTypes,
  label: PropTypes.string.isRequired,
  value: PropTypes.oneOfType([PropTypes.arrayOf(String), PropTypes.string]),
  options: PropTypes.arrayOf(Object).isRequired,
  handleChange: PropTypes.func.isRequired,
  multiple: PropTypes.bool,
  disabled: PropTypes.bool,
};

SelectSingle.defaultProps = {
  ...defaultProps,
  value: null,
  multiple: false,
  disabled: false,
};

export default withStyles(styles, { withTheme: true })(withFormsy(SelectSingle));
