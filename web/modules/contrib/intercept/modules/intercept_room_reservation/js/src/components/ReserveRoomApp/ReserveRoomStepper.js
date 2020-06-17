import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';
import { isWidthUp } from '@material-ui/core/withWidth';

/* eslint-disable */
import interceptClient from 'interceptClient';
import { isFutureTime } from './ReserveRoom';
import { withWidth, Stepper, Step, StepButton, Button, Typography } from '@material-ui/core';
/* eslint-enable */
const { select, utils } = interceptClient;

const styles = theme => ({
  root: {
    width: '90%',
  },
  button: {
    marginRight: theme.spacing(1),
  },
  completed: {
    display: 'inline-block',
  },
  instructions: {
    marginTop: theme.spacing(1),
    marginBottom: theme.spacing(1),
  },
  labelButton: {
    textTransform: 'none',
    textAlign: 'left',
    letterSpacing: 0,
    paddingBottom: theme.spacing(1),
    paddingTop: theme.spacing(1),
    '&:disabled': {
      opacity: '.5',
      backgroundColor: 'transparent',
    },
    '& .MuiTypography-caption': {
      display: 'block',
    },
  },
});

function getSteps() {
  return ['Choose a Room', 'Choose a Time', 'Confirm Reservation'];
}

class HorizontalNonLinearStepper extends React.Component {
  state = {
    activeStep: 0,
    completed: {},
  };

  getStepCaption = (step) => {
    switch (step) {
      case 0:
        return this.props.roomLabel;
      case 1:
        // return props.dateLabel;
        return this.getDateLabel();
      case 2:
        return '';
      default:
        return 'Unknown step';
    }
  }

  getStepDisabledState = (step) => {
    const { date, start } = this.props.values;

    switch (step) {
      case 0:
      case 1:
        return false;
      case 2:
        return !(date && start && isFutureTime(start, date));
      default:
        return false;
    }
  }

  getDateLabel = () => {
    const { date, start, end } = this.props.values;
    return (date && start && end) ? utils.getDateTimespanDisplay({ date, start, end }) : '';
  };

  completedSteps() {
    return Object.keys(this.state.completed).length;
  }

  totalSteps = () => getSteps().length;

  isLastStep() {
    return this.state.activeStep === this.totalSteps() - 1;
  }

  allStepsCompleted() {
    return this.completedSteps() === this.totalSteps();
  }

  handleNext = () => {
    let activeStep;

    if (this.isLastStep() && !this.allStepsCompleted()) {
      // It's the last step, but not all steps have been completed,
      // find the first step that has been completed
      const steps = getSteps();
      activeStep = steps.findIndex((step, i) => !(i in this.state.completed));
    }
    else {
      activeStep = this.state.activeStep + 1;
    }
    this.setState({
      activeStep,
    });
  };

  handleBack = () => {
    const { activeStep } = this.state;
    this.setState({
      activeStep: activeStep - 1,
    });
  };

  handleStep = step => () => {
    this.props.onChangeStep(step);
  };

  handleComplete = () => {
    const { completed } = this.state;
    completed[this.state.activeStep] = true;
    this.setState({
      completed,
    });
    this.handleNext();
  };

  handleReset = () => {
    this.setState({
      activeStep: 0,
      completed: {},
    });
  };

  render() {
    const { classes, step, width } = this.props;
    const steps = getSteps();

    return (
      <div className={classes.root}>
        <Stepper
          nonLinear
          activeStep={step}
          orientation={isWidthUp('md', width) ? 'horizontal' : 'vertical'}
        >
          {steps.map((label, index) => (
            <Step key={label}>
              <StepButton
                className={classes.labelButton}
                onClick={this.handleStep(index)}
                completed={this.state.completed[index]}
                disabled={this.getStepDisabledState(index)}
              >
                {label}
                {<Typography variant="caption">{this.getStepCaption(index, this.props)}</Typography>}
              </StepButton>
            </Step>
          ))}
        </Stepper>
        <div>
          {this.allStepsCompleted() ? (
            <div>
              <Typography className={classes.instructions}>
                All steps completed - you&quot;re finished
              </Typography>
              <Button onClick={this.handleReset}>Reset</Button>
            </div>
          ) : (
            <div>
              {/* <Typography className={classes.instructions}>{getStepContent(activeStep)}</Typography>
              <div>
                <Button
                  disabled={activeStep === 0}
                  onClick={this.handleBack}
                  className={classes.button}
                >
                  Back
                </Button>
                <Button
                  variant="contained"
                  color="primary"
                  onClick={this.handleNext}
                  className={classes.button}
                >
                  Next
                </Button>
                {activeStep !== steps.length &&
                  (this.state.completed[this.state.activeStep] ? (
                    <Typography variant="caption" className={classes.completed}>
                      Step {activeStep + 1} already completed
                    </Typography>
                  ) : (
                    <Button variant="contained" color="primary" onClick={this.handleComplete}>
                      {this.completedSteps() === this.totalSteps() - 1 ? 'Finish' : 'Complete Step'}
                    </Button>
                  ))}
              </div> */}
            </div>
          )}
        </div>
      </div>
    );
  }
}

HorizontalNonLinearStepper.propTypes = {
  step: PropTypes.number,
  classes: PropTypes.object.isRequired,
  onChangeStep: PropTypes.func.isRequired,
  values: PropTypes.object.isRequired,
};

HorizontalNonLinearStepper.defaultProps = {
  step: 0,
};

const mapStateToProps = (state, ownProps) => {
  if (!ownProps.room) {
    return {};
  }

  const roomLabel = select.roomLabel(ownProps.room)(state);
  const locationLabel = select.roomLocationLabel(ownProps.room)(state);

  if (!roomLabel && !locationLabel) {
    return {};
  }

  return {
    roomLabel: locationLabel ? `${locationLabel}: ${roomLabel}` : roomLabel,
  };
};

export default withWidth()(connect(mapStateToProps)(withStyles(styles)(HorizontalNonLinearStepper)));
