import { create } from 'jss';
import { createGenerateClassName, jssPreset } from '@material-ui/core/styles';

export const generateClassName = createGenerateClassName();
export const jss = create(jssPreset());
