import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import SelectMultiple from './../Select/SelectMultiple';
import SelectSingle from './../Select/SelectSingle';
import ResourceChip from './../ResourceChip';
import interceptClient from 'interceptClient';

const { select, api } = interceptClient;

class SelectResource extends React.Component {
  componentDidMount() {
    if (this.props.shouldFetch) {
      this.props.fetchAll({});
    }
  }

  render() {
    const { chips, value, multiple, type, handleChange } = this.props;

    let renderValue = () => null;

    if (chips) {
      renderValue = selected => (
        <div className="current-filter current-filter--embedded">
          {selected.map(id => (
            <ResourceChip
              key={id}
              identifier={{ type, id }}
              onDelete={item =>
                handleChange({
                  target: {
                    value: value.filter(v => v !== item),
                  },
                })
              }
            />
          ))}
        </div>
      );
    }

    return multiple ? (
      <SelectMultiple
        {...this.props}
        value={value === null ? [] : value}
        renderValue={renderValue}
      />
    ) : (
      <SelectSingle
        {...this.props}
        options={[{ key: '', value: 'None' }, ...this.props.options]}
        value={value}
      />
    );
  }
}

const mapStateToProps = (state, ownProps) => {
  // console.log(select.getTermTree(ownProps.type)(state));
  return {
    options: select.recordOptions(ownProps.type)(state),
  };
};

const mapDispatchToProps = (dispatch, ownProps) => ({
  fetchAll: (options) => {
    dispatch(api[ownProps.type].fetchAll(options));
  },
});

SelectResource.defaultProps = {
  chips: false,
  multiple: false,
  value: null,
  shouldFetch: true,
};

SelectResource.propTypes = {
  chips: PropTypes.bool,
  handleChange: PropTypes.func.isRequired,
  fetchAll: PropTypes.func.isRequired,
  label: PropTypes.string.isRequired,
  multiple: PropTypes.bool,
  shouldFetch: PropTypes.bool,
  options: PropTypes.arrayOf(Object).isRequired,
  value: PropTypes.oneOfType([PropTypes.arrayOf(String), PropTypes.string]),
  type: PropTypes.string.isRequired,
};

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(SelectResource);
