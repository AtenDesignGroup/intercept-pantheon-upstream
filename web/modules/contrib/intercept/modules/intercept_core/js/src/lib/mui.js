import { create } from 'jss';
import createGenerateClassName from '@material-ui/core/styles/createGenerateClassName';
import jssPreset from '@material-ui/core/styles/jssPreset';

export const generateClassName = createGenerateClassName();
export const jss = create(jssPreset());
