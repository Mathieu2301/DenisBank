import * as moment from 'moment';
import defineFR from './moment-fr';

defineFR(moment);

export default {
//   fromNow: date => moment(date).add(2, 'hours').fromNow(),
//   formatDate: date => `${this.addZeros(date.getDate())}/${this.addZeros(date.getMonth() - 1)}
// à ${this.addZeros(date.getHours())}h${this.addZeros(date.getMinutes())}`,
  round: (val, i = 1) => Math.round(val * (10 ** i)) / (10 ** i),
  addot: val => (val.includes(',') ? val : `${val},00`),
  add_plus: val => ((typeof val === 'number' ? val >= 0 : !val.includes('-')) ? `+${val}` : `${val}`),
  bignbr: Intl.NumberFormat().format,
};
