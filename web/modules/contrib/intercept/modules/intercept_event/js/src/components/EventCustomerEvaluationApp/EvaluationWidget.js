import React from 'react';
import PropTypes from 'prop-types';
import Radio from '@material-ui/core/Radio';
import FormLabel from '@material-ui/core/FormLabel';
import FormControl from '@material-ui/core/FormControl';
import FormGroup from '@material-ui/core/FormGroup';

const radioClasses = {
  root: "evaluation__radio-icon",
  checked: "evaluation__radio-icon--checked",
  disabled: "evaluation__radio-icon--disabled",
};

class EvaluationWidget extends React.PureComponent {
  // state = {
  //   selectedValue: 'a',
  // };

  handleChange = (event) => {
    this.props.onChange(event.target.value);
  };

  render() {
    const { value, label, likeIcon, dislikeIcon } = this.props;

    return (
      <FormControl component="fieldset" className={'evaluation__eval-widget'} name={name}>
        {label && (
          <FormLabel component="legend" className={'evaluation__widget-label'}>
            {label}
          </FormLabel>
        )}
        <FormGroup className={'evaluation__widget-inputs'}>
          <Radio
            checked={value === '1'}
            onChange={this.handleChange}
            value="1"
            color="default"
            name={name}
            aria-label="Like"
            icon={likeIcon('#7A7D81')}
            checkedIcon={likeIcon('#ffffff')}
            classes={radioClasses}
          />
          <Radio
            checked={value === '0'}
            onChange={this.handleChange}
            value="0"
            color="default"
            name={name}
            aria-label="Dislike"
            icon={dislikeIcon('#7A7D81')}
            checkedIcon={dislikeIcon('#ffffff')}
            classes={radioClasses}
          />
        </FormGroup>
      </FormControl>
    );
  }
}

EvaluationWidget.propTypes = {
  label: PropTypes.string,
  name: PropTypes.string.isRequired,
  onChange: PropTypes.func,
  value: PropTypes.string,
};

EvaluationWidget.defaultProps = {
  label: 'How’d the Event Go?',
};

export default EvaluationWidget;
