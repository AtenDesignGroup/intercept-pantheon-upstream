import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import { withFormsy } from 'formsy-react';

import {
  Input,
  InputLabel,
  MenuItem,
  FormControl,
  ListItemText,
  Select,
  Checkbox,
} from '@material-ui/core';

const styles = (theme) => ({
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

const MenuListProps = {
  className: 'select-filter__menu-list',
};

const MenuProps = {
  MenuListProps,
  PaperProps: {
    style: {
      // maxHeight: (ITEM_HEIGHT * 8.5) + ITEM_PADDING_TOP,
      backfaceVisibility: 'hidden',
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

class SelectMultiple extends React.Component {
  constructor(props) {
    super(props);
    this.state = { open: false };
  }

  handleChange = (event) => {
    this.props.handleChange(event);
  };

  render() {
    const {
      chips,
      labels,
      isValid,
      renderValue,
      options,
      label,
      multiple,
      name,
      required,
      value,
    } = this.props;

    const checkboxId = (id) => `select-filter--${id}`;
    const checkboxLabel = (text, id) => (
      <label className="select-filter__checkbox-label">{text}</label>
    );

    // Remove "Richland Library" prefix from locations on the Location SelectMultiple dropdown (such as on /events)
    if (name === 'node--location') {
      options.forEach((option) => {
        option.value = option.value.replace('Richland Library ', '');
      });
    }

    const toOptionItems = (depth = 0) => (all, option) => {
      let indeterminate = false;
      let selected = [];
      if (option.children.length > 0) {
        option.children.forEach((child) => {
          selected.push(value.indexOf(child.key) > -1);
        });
        const all = selected.every((v) => v === true);
        const some = selected.indexOf(true) > -1;
        if (some && !all) {
          indeterminate = true;
        }
      }
      if (option.children.length > 0) {
        return [].concat(
          all,
          <MenuItem
            onClick={this.handleClick}
            key={option.key}
            value={option.key}
            className="select-filter__menu-item"
            data-depth={depth}
          >
            <Checkbox
              checked={multiple ? value.indexOf(option.key) > -1 : value === option.key}
              id={checkboxId(option.key)}
              className="select-filter__checkbox"
              indeterminate={indeterminate}
            />
            <ListItemText
              disableTypography
              primary={checkboxLabel(option.value, checkboxId(option.key))}
            />
          </MenuItem>,
          option.children && option.children.reduce(toOptionItems(depth + 1), [])
        );
      } else {
        return [].concat(
          all,
          <MenuItem
            key={option.key}
            value={option.key}
            className="select-filter__menu-item"
            data-depth={depth}
          >
            <Checkbox
              checked={multiple ? value.indexOf(option.key) > -1 : value === option.key}
              id={checkboxId(option.key)}
              className="select-filter__checkbox"
              indeterminate={indeterminate}
            />
            <ListItemText
              disableTypography
              primary={checkboxLabel(option.value, checkboxId(option.key))}
            />
          </MenuItem>,
          option.children && option.children.reduce(toOptionItems(depth + 1), [])
        );
      }
    };

    return (
      <div className="select-filter input input--select">
        <FormControl className="select-filter__control">
          <InputLabel
            className="select-filter__label"
            htmlFor={name}
            shrink={(labels || chips) && value.length >= 1}
            required={required}
          >
            {label}
          </InputLabel>

          <Select
            multiple={multiple}
            value={!value ? '' : value}
            onChange={this.handleChange}
            input={<Input id={name} />}
            renderValue={renderValue}
            MenuProps={MenuProps}
            error={!isValid}
            required={this.props.required}
          >
            {options.reduce(toOptionItems(), [])}
          </Select>
        </FormControl>
      </div>
    );
  }
}

SelectMultiple.propTypes = {
  label: PropTypes.string.isRequired,
  value: PropTypes.oneOfType([PropTypes.arrayOf(String), PropTypes.string]),
  options: PropTypes.arrayOf(Object).isRequired,
  renderValue: PropTypes.func,
  handleChange: PropTypes.func.isRequired,
  multiple: PropTypes.bool,
  chips: PropTypes.bool,
  labels: PropTypes.bool,
};

SelectMultiple.defaultProps = {
  value: null,
  multiple: false,
  chips: false,
  labels: false,
  renderValue: () => null,
};

export default withStyles(styles, { withTheme: true })(withFormsy(SelectMultiple));
