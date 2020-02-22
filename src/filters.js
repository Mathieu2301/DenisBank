import * as moment from 'moment';
import defineFR from './moment-fr';

defineFR(moment);

export default {
//   fromNow: date => moment(date).add(2, 'hours').fromNow(),
//   formatDate: date => `${this.addZeros(date.getDate())}/${this.addZeros(date.getMonth() - 1)}
// à ${this.addZeros(date.getHours())}h${this.addZeros(date.getMinutes())}`,
  addot: val => (val.includes(',') ? val : `${val},00`),
  bignbr: Intl.NumberFormat().format,
};
