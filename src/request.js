const api = 'denisbank.apis.colmon.fr';

function rq(type) {
  return function request(data = {}, callback = () => {}) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', `https://${api}/?${type}`, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = () => {
      if (xhr.readyState === XMLHttpRequest.DONE) {
        let response = xhr.responseText;
        try {
          response = JSON.parse(response);
        } catch (e) {
          callback({ error: true, message: 'Can\'t parse server response' });
          return;
        }
        callback(response);
      }
    };
    xhr.send(JSON.stringify(data));
  };
}

export default {
  TEST: rq('test'),
  ACCOUNT: {
    REGISTER: rq('createAccount'),
    LOGIN: rq('loginAccount'),
    ADD_TOKEN: rq('addToken'),
    CHECK_SESSION: rq('checkSession'),
    ONCHANGES: rq('onChanges'),
    GET: rq('getInfos'),
  },
  DEAL: {
    NEW: {
      SEND: rq('sendMoney'),
      ASK: rq('askMoney'),
    },
    PAY: rq('payDeal'),
    CANCEL: rq('cancelDeal'),
  },
  TRADING: {
    GET_TRADES: rq('getTrades'),
    NEW_TRADE: rq('newTrade'),
    CLOSE_TRADE: rq('closeTrade'),
    FETCH_MARKETS: rq('fetchMarkets'),
    SET_FAV_MARKETS: rq('setFavMarkets'),
  },
  USER: {
    SEARCH: rq('searchUser'),
  },
};
