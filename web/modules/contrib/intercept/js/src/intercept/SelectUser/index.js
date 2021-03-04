import React from 'react';
import PropTypes from 'prop-types';
import interceptClient from 'interceptClient';
import UserName from './UserName';
import RegistrationLookup from './RegistrationLookup';
import { Button } from '@material-ui/core';

const { utils } = interceptClient;

class SelectUser extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      myAccount: true,
      value: {
        uuid: utils.getUserUuid(),
        name: utils.getUserName(),
      },
      formValue: '',
    };
  }

  getUserName = () => {
    let output = 'User Not Found';

    if (this.state.myAccount) {
      output = utils.getUserName();
    }
    else {
      return 'look up username';
    }

    return output;
  };

  handleChange = (value) => {
    this.setState({ value });
    this.props.onChange(value);
  };

  resetValues = () => {
    this.handleChange({
      uuid: utils.getUserUuid(),
      name: utils.getUserName(),
    });
    this.setState({
      formValue: '',
    });
  };

  isMyAccount = () => this.state.value.uuid === utils.getUserUuid();

  resetLink = () => {
    return (
      <Button variant="outlined" size="small" onClick={this.resetValues} disabled={this.isMyAccount()} >
        Reset to My Account
      </Button>
    );
  }

  render() {
    const { onChange, name, label } = this.props;
    const canLookup = utils.userIsStaff();
    const canScan = utils.userIsStaff();

    return (
      <div className="select-user">
        <UserName label={label} name={this.state.value.name} />
        {canScan && (<div>
          <RegistrationLookup
            name={name}
            onSuccess={this.handleChange}
            onFailure={this.resetValues}
            onChange={formValue => this.setState({ formValue })}
            value={this.state.formValue}
          />
          {this.resetLink()}
        </div>)}
      </div>
    );
  }
}

SelectUser.defaultProps = {
  label: 'Account',
};

SelectUser.propTypes = {
  onChange: PropTypes.func.isRequired,
  name: PropTypes.string.isRequired,
  label: PropTypes.string,
};

export default SelectUser;
