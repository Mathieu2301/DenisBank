<template>
  <div class="dashboard">
    <div class="header" v-if="tab != 'market'">
      <logo color="#2c3e50" width="80px"></logo>
      <div class="logo_text">ashboard</div>
    </div>

    <section v-show="tab == 'dashboard'">

      <div class="circle" :class="{
        red: account.balance.money < 0,
        yellow: account.balance.money == 0,
        green: account.balance.money > 0,
      }" v-if="account && account.balance">
        {{ account.balance.money | bignbr | addot }} €
      </div>

      <div class="container noselect" v-if="account && account.balance">
        <div class="top">Résumé</div>
        <div class="content">
          <table>
            <tr>
              <td>Rentrées</td>
              <td>{{ account.balance.got | bignbr | addot }} €</td>
            </tr>
            <tr>
              <td>Dépenses</td>
              <td>{{ account.balance.given | bignbr | addot }} €</td>
            </tr>
            <tr>
              <td>Rentrées Potentielles</td>
              <td>{{ account.balance.haveto_get | bignbr | addot }} €</td>
            </tr>
            <tr>
              <td>Dépenses potentielles</td>
              <td>{{ account.balance.haveto_give | bignbr | addot }} €</td>
            </tr>
          </table>
        </div>
      </div>

      <div class="container noselect" v-if="pending_deals && pending_deals.length > 0">
        <div class="top">En attente</div>
        <div class="content">
          <div class="deal" v-for="deal in pending_deals" :key="deal.id">
            <div class="title">
              <div>{{ deal.title }}</div>
              <div>{{ deal.amount | bignbr | addot }} €</div>
            </div>
            <div class="flex" :class="{ inverse: deal.target == account.email }">
              <div>Vous</div>
              <!-- eslint-disable-next-line -->
              <svg viewBox="0 0 100 100"><path d=" M 91.75 51.8 Q 92.5 51.05 92.5 50 92.5 48.95 91.75 48.25 L 61.75 18.25 Q 61.05 17.5 60 17.5 58.95 17.5 58.2 18.25 57.5 18.95 57.5 20 57.5 21.05 58.2 21.8 L 83.95 47.5 10 47.5 Q 8.95 47.5 8.2 48.25 7.5 48.95 7.5 50 7.5 51.05 8.2 51.8 8.95 52.5 10 52.5 L 83.95 52.5 58.2 78.25 Q 57.5 78.95 57.5 80 57.5 81.05 58.2 81.8 58.95 82.5 60 82.5 61.05 82.5 61.75 81.8 L 91.75 51.8 Z"/></svg>
              <div>{{ deal.user }}</div>
            </div>
            <div class="bottom">
              <div class="cancel" @click="cancelDeal(deal)">Annuler</div>
              <div @click="payDeal(deal)" :class="{
                disabled: deal.target == account.email
              }">Payer</div>
            </div>
          </div>
        </div>
      </div>

      <div class="container noselect" v-if="confirmed_deals && confirmed_deals.length > 0">
        <div class="top">Transactions</div>
        <div class="content">
          <table>
            <tr>
              <th>Titre</th>
              <th>Utilisateur</th>
              <th>Montant</th>
            </tr>
            <tr v-for="deal in confirmed_deals" :key="deal.id">
              <td>{{ deal.title }}</td>
              <td>{{ deal.user }}</td>
              <td :class="{
                text_green: deal.target == account.email,
                text_red: deal.giver == account.email,
              }">
                {{ (deal.target == account.email ? '+' : '-') }}
                {{ deal.amount | bignbr | addot }} €
              </td>
            </tr>
          </table>
        </div>
      </div>

      <div class="container noselect" v-show="account && account.valid">
        <div class="top">Historique</div>
        <canvas class="content" id="chart"></canvas>
      </div>
    </section>

    <section v-show="tab == 'contacts'">
      <div class="contacts_container" v-show="!deal.target">
        <input type="text"
          class="search"
          placeholder="Chercher utilisateur"
          v-model="user_search"
          @keyup="searchUser"
        >

        <div class="tm_container" v-show="user_search">
          <div class="user"
            v-for="account in search_results"
            :key="account.email"
            @click="deal.target = account"
          >
            <!-- eslint-disable-next-line -->
            <svg class="icon" viewBox="0 0 8449 8449"><path d="M4224 4777c1213,0 2196,-983 2196,-2196 0,-1213 -983,-2196 -2196,-2196 -1213,0 -2196,983 -2196,2196 0,1213 983,2196 2196,2196zm-1875 -423c-958,-1012 -938,-2610 50,-3598 1030,-1030 2709,-1006 3709,62 932,995 927,2545 -6,3533 1505,747 2347,2242 2347,3901 -2816,0 -5632,0 -8449,0 -20,-1661 851,-3154 2349,-3898zm-1958 3540l7664 0c-103,-1423 -955,-2682 -2262,-3265 -923,709 -2211,710 -3135,3 -1302,581 -2177,1837 -2267,3262z"/></svg>
            <div class="username">{{ account.fullname }}</div>
            <div class="select"></div>
          </div>
        </div>
      </div>
      <div v-if="deal.target">
        <div class="switch">
          <div class="choice"
            @click="deal.type = 'send'"
            :class="{ selected: deal.type == 'send' }"
          >Envoyer</div>
          <div class="choice"
            @click="deal.type = 'ask'"
            :class="{ selected: deal.type == 'ask' }"
          >Demander</div>
        </div>

        <div class="flex" :class="{ inverse: deal.type == 'ask' }">
          <div>Vous</div>
          <!-- eslint-disable-next-line -->
          <svg viewBox="0 0 100 100"><path d=" M 91.75 51.8 Q 92.5 51.05 92.5 50 92.5 48.95 91.75 48.25 L 61.75 18.25 Q 61.05 17.5 60 17.5 58.95 17.5 58.2 18.25 57.5 18.95 57.5 20 57.5 21.05 58.2 21.8 L 83.95 47.5 10 47.5 Q 8.95 47.5 8.2 48.25 7.5 48.95 7.5 50 7.5 51.05 8.2 51.8 8.95 52.5 10 52.5 L 83.95 52.5 58.2 78.25 Q 57.5 78.95 57.5 80 57.5 81.05 58.2 81.8 58.95 82.5 60 82.5 61.05 82.5 61.75 81.8 L 91.75 51.8 Z"/></svg>
          <div>{{ deal.target.fullname }}</div>
        </div>

        <div class="input">
          <div>Titre :</div>
          <input type="text" placeholder="Titre" v-model="deal.title">
        </div>

        <div class="input">
          <div>Quantité :</div>
          <input type="number" v-model="deal.amount" placeholder="0,00€">
        </div>

        <div class="switch bottom">
          <div class="choice" @click="deal.target = null">Retour</div>
          <div class="choice selected" @click="sendDeal">
            {{ (deal.type == "send" ? 'Envoyer' : 'Demander') }} l'argent
          </div>
        </div>

      </div>
    </section>

    <section v-show="tab == 'trading'">
      <div class="markets">
        <div class="market" v-for="symbol in account.fav_markets" :key="symbol">
          <div @click="selectMarket(symbol)" class="market_container">
            <iframe
              :src="'https://s.tradingview.com/embed-widget/mini-symbol'
                + '-overview/?locale=fr#%7B%22symbol%22%3A%22' + symbol
                + '%22%2C%22dateRange%22%3A%221m%22%2C%22colorTheme%22%'
                + '3A%22dark%22%2C%22trendLineColor%22%3A%22%2300b176%2'
                + '2%2C%22underLineColor%22%3A%22%2300b17630%22%2C%22au'
                + 'tosize%22%3Atrue%7D'"
                frameborder="0"
            ></iframe>
          </div>
          <div class="market_sidebar">
            <div class="market_sidebar_button" @click="marketAction(symbol, 'BUY')">BUY</div>
            <div class="market_sidebar_button" @click="marketAction(symbol, 'SELL')">SELL</div>
          </div>
        </div>
      </div>
    </section>

    <section v-show="tab == 'market'">
      <div id="market_chart"></div>
    </section>

    <section v-if="tab == 'market_action'">
      <iframe id="market_analysis"
        :src="'https://s.tradingview.com/embed-widget/technical-analysis/'
          + '?locale=fr#%7B%22symbol%22%3A%22' + market_action.market
          + '%22%2C%22interval%22%3A%221h%22%2C%22isTransparent%22%3Atrue'
          + '%2C%22showIntervalTabs%22%3Atrue%2C%22colorTheme%22%3A%22dark%22%7D'"
          frameborder="0"
      ></iframe>

      <div class="switch">
        <div class="choice"
          @click="market_action.type = 'BUY'"
          :class="{ selected: market_action.type == 'BUY' }"
        >BUY</div>
        <div class="choice red"
          @click="market_action.type = 'SELL'"
          :class="{ selected: market_action.type == 'SELL' }"
        >SELL</div>
      </div>

      <div class="market_action_form">

        <div class="separator_title">Position</div>
        <div class="columns_container">
          <div class="column">
            <div class="BR_TL input_title bg_red">Stop-Loss</div>
            <input class="BR_BL center" type="number" v-model="market_action.SL">
          </div>
          <div class="column">
            <div class="input_title bg_grey">Quantité</div>
            <input class="center" type="number" v-model="market_action.amount">
          </div>
          <div class="column">
            <div class="BR_TR input_title bg_green">Take-Profit</div>
            <input class="BR_BR center" type="number" v-model="market_action.TP">
          </div>
        </div>

        <div class="separator_title">Effet de levier</div>
        <div class="columns_container">
          <div class="column">
            <div class="grid_button BR_TL bg_green"
              @click="market_action.leverage = 1"
              :class="{ selected: market_action.leverage == 1 }">x1</div>
            <div class="grid_button BR_BL bg_red"
              @click="market_action.leverage = 10"
              :class="{ selected: market_action.leverage == 10 }">x10</div>
          </div>
          <div class="column">
            <div class="grid_button bg_green"
              @click="market_action.leverage = 2"
              :class="{ selected: market_action.leverage == 2 }">x2</div>
            <div class="grid_button bg_red"
              @click="market_action.leverage = 20"
              :class="{ selected: market_action.leverage == 20 }">x20</div>
          </div>
          <div class="column">
            <div class="grid_button BR_TR bg_green"
              @click="market_action.leverage = 5"
              :class="{ selected: market_action.leverage == 5 }">
              x5
            </div>
            <div class="grid_button BR_BR bg_red"
              @click="market_action.leverage = 30"
              :class="{ selected: market_action.leverage == 30 }">x
              30
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="footer">
      <!-- eslint-disable-next-line -->
      <svg class="tab" :class="{ selected: tab=='dashboard' }" @click="selecTab('dashboard')" viewBox="0 0 65 65"><path transform="translate(-20,-970)" d="m 49.188324,974.24378 -31.02473,27.02532 c -0.79457,0.6883 -0.89175,2.0382 -0.20398,2.8334 0.68777,0.7952 2.0365,0.8925 2.83107,0.2041 l 29.67991,-25.86662 28.71038,25.83542 c 0.77822,0.7048 2.12697,0.6372 2.83111,-0.1416 0.70415,-0.7788 0.63676,-2.1287 -0.14146,-2.8334 l -30.02393,-27.02542 c -0.84443,-0.5451 -1.84522,-0.5451 -2.65837,-0.031 z m 23.33109,25.3655 0,27.24452 -13.01037,0 c 0,-5.3445 0,-10.689 0,-16.0335 -6.47829,0 -13.27006,0 -18.85878,0 -0.0806,5.3443 -0.12816,10.6889 -0.1876,16.0335 l -12.97909,0 0,-27.05662 -4.00319,3.47612 c 0,9.1963 0,18.3926 0,27.5889 7.19553,0 14.72076,0 20.92292,0 0.0798,-5.3443 0.12804,-10.689 0.1876,-16.0335 l 10.91495,0 c 0,5.3444 0,10.689 0,16.0335 7.22814,0 14.78671,0 21.01676,0 0,-9.2172 0,-18.4345 0,-27.6516 z"/></svg>

      <!-- eslint-disable-next-line -->
      <svg class="tab" :class="{ selected: tab=='trading' }" @click="selecTab('trading')" viewBox="0 0 100 100"><path d="M95.5,59.2l-17.1-1.1c-1.8-0.1-2.9,1.8-1.9,3.3l3.4,5.1L55.1,83.2L46,69.5c-1.3-2-4-2.5-5.9-1.2L9.7,88.7     c-2,1.3-2.5,4-1.2,5.9c1.3,2,4,2.5,5.9,1.2l26.8-18l9.1,13.6c1.3,2,4,2.5,5.9,1.2l28.3-19l3.4,5.1c1,1.5,3.2,1.2,3.8-0.5L97.4,62     C97.8,60.7,96.9,59.3,95.5,59.2z"/><path d="M8.5,72.8c0,1.2,1,2.2,2.2,2.2s2.2-1,2.2-2.2v-4.4H17c1,0,1.9-0.9,1.9-1.9V40.2c0-1.1-0.9-1.9-1.9-1.9h-4.1v-4.4     c0-1.2-1-2.2-2.2-2.2s-2.2,1-2.2,2.2v4.4H4.4c-1,0-1.9,0.8-1.9,1.9v26.3c0,1,0.8,1.9,1.9,1.9h4.1V72.8z"/><path d="M75,40.1h4.1v4.4c0,1.2,1,2.2,2.2,2.2c1.2,0,2.2-1,2.2-2.2v-4.4h4.1c1,0,1.9-0.9,1.9-1.9V12c0-1.1-0.9-1.9-1.9-1.9h-4.1     V5.7c0-1.2-1-2.2-2.2-2.2c-1.2,0-2.2,1-2.2,2.2v4.4H75c-1,0-1.9,0.8-1.9,1.9v26.3C73.1,39.3,73.9,40.1,75,40.1z"/><path d="M39.7,50.4h4.1v4.4c0,1.2,1,2.2,2.2,2.2c1.2,0,2.2-1,2.2-2.2v-4.4h4.1c1,0,1.9-0.9,1.9-1.9V22.3c0-1-0.8-1.9-1.9-1.9     h-4.1V16c0-1.2-1-2.2-2.2-2.2c-1.2,0-2.2,1-2.2,2.2v4.4h-4.1c-1,0-1.9,0.9-1.9,1.9v26.3C37.8,49.6,38.6,50.4,39.7,50.4z"/></svg>

      <!-- eslint-disable-next-line -->
      <svg class="tab" :class="{ selected: tab=='contacts' }" @click="selecTab('contacts')" viewBox="0 0 30 30"><path d="M23.697,15.175C25.107,13.894,26,12.051,26,10c0-3.859-3.14-7-7-7c-1.436,0-2.825,0.449-3.995,1.267  C13.869,3.471,12.489,3,11,3c-3.86,0-7,3.141-7,7c0,2.051,0.893,3.894,2.303,5.175C3.071,16.882,1,20.225,1,24v3  c0,0.553,0.448,1,1,1h26c0.552,0,1-0.447,1-1v-3C29,20.225,26.929,16.882,23.697,15.175z M19,5c2.757,0,5,2.243,5,5  c0,2.023-1.213,3.76-2.945,4.546c-0.077,0.035-0.154,0.067-0.232,0.098c-0.246,0.097-0.498,0.179-0.761,0.237  c-0.05,0.011-0.101,0.018-0.151,0.027c-0.286,0.053-0.579,0.087-0.88,0.089c-0.133,0-0.267-0.01-0.4-0.021  c-0.05-0.004-0.1-0.005-0.149-0.01c-0.548-0.063-1.098-0.221-1.635-0.482c-0.019-0.009-0.041-0.009-0.061-0.017  c-0.096-0.048-0.195-0.089-0.289-0.144c0.008-0.01,0.013-0.022,0.021-0.032c0.455-0.584,0.818-1.241,1.071-1.95  c0.01-0.028,0.021-0.056,0.031-0.085c0.114-0.333,0.203-0.676,0.266-1.029c0.009-0.051,0.016-0.103,0.024-0.155  C17.963,10.722,18,10.366,18,10s-0.037-0.722-0.091-1.073c-0.008-0.052-0.014-0.103-0.024-0.155  c-0.063-0.353-0.152-0.696-0.266-1.028c-0.01-0.029-0.021-0.056-0.031-0.085c-0.253-0.71-0.616-1.367-1.071-1.951  c-0.008-0.01-0.013-0.021-0.021-0.031C17.254,5.236,18.115,5,19,5z M6,10c0-2.757,2.243-5,5-5c1.334,0,2.542,0.533,3.439,1.388  c0.058,0.056,0.115,0.112,0.171,0.17c0.172,0.18,0.331,0.371,0.474,0.575c0.044,0.063,0.084,0.128,0.126,0.193  c0.137,0.215,0.263,0.438,0.366,0.674c0.025,0.056,0.044,0.115,0.067,0.172c0.102,0.257,0.19,0.521,0.248,0.796  c0.006,0.03,0.009,0.061,0.015,0.091C15.963,9.364,16,9.678,16,10s-0.037,0.636-0.095,0.941c-0.006,0.03-0.008,0.061-0.015,0.091  c-0.058,0.275-0.146,0.539-0.248,0.796c-0.023,0.057-0.042,0.116-0.067,0.172c-0.103,0.236-0.229,0.459-0.366,0.674  c-0.041,0.065-0.081,0.13-0.126,0.193c-0.143,0.204-0.303,0.395-0.474,0.575c-0.055,0.058-0.112,0.115-0.171,0.17  c-0.403,0.384-0.868,0.699-1.381,0.932c-0.081,0.037-0.164,0.071-0.246,0.103c-0.24,0.094-0.486,0.175-0.742,0.231  c-0.064,0.014-0.128,0.021-0.192,0.033c-0.268,0.048-0.542,0.08-0.824,0.083c-0.037,0-0.073,0-0.11,0  c-0.281-0.003-0.555-0.035-0.824-0.083c-0.064-0.011-0.128-0.019-0.192-0.033c-0.256-0.056-0.502-0.137-0.742-0.231  c-0.083-0.032-0.165-0.066-0.246-0.103C7.211,13.757,6,12.021,6,10z M3,24c0-3.422,2.122-6.404,5.318-7.536  C9.144,16.808,10.05,17,11,17s1.856-0.192,2.682-0.536c0.339,0.12,0.66,0.271,0.974,0.432c0.208,0.107,0.403,0.229,0.598,0.351  c0.13,0.082,0.262,0.161,0.387,0.249c0.193,0.137,0.376,0.287,0.554,0.439c0.115,0.098,0.229,0.195,0.337,0.299  c0.164,0.157,0.319,0.321,0.468,0.49c0.107,0.122,0.211,0.246,0.31,0.374c0.131,0.168,0.256,0.339,0.372,0.517  c0.098,0.15,0.189,0.306,0.277,0.462c0.097,0.172,0.192,0.344,0.276,0.523c0.086,0.184,0.158,0.375,0.23,0.566  c0.063,0.166,0.13,0.331,0.182,0.502c0.07,0.232,0.119,0.472,0.168,0.712c0.029,0.142,0.069,0.281,0.09,0.426  C18.965,23.196,19,23.594,19,24v2H3V24z M27,26h-6v-2c0-0.313-0.018-0.623-0.046-0.93c-0.008-0.091-0.022-0.18-0.033-0.269  c-0.026-0.221-0.058-0.44-0.098-0.656c-0.017-0.094-0.037-0.187-0.057-0.28c-0.048-0.223-0.104-0.444-0.167-0.661  c-0.021-0.072-0.04-0.145-0.062-0.217c-0.282-0.902-0.689-1.751-1.205-2.527c-0.013-0.019-0.026-0.038-0.039-0.057  c-0.172-0.255-0.356-0.502-0.551-0.741c-0.003-0.003-0.005-0.006-0.007-0.009c-0.204-0.248-0.417-0.489-0.645-0.717  c0.014,0.002,0.027,0.001,0.041,0.003c0.281,0.035,0.565,0.06,0.851,0.06c0.018,0,0.037-0.001,0.055-0.001  c0.267-0.001,0.529-0.021,0.787-0.051c0.082-0.01,0.164-0.025,0.245-0.038c0.213-0.033,0.423-0.075,0.63-0.127  c0.06-0.015,0.12-0.029,0.179-0.046c0.274-0.077,0.543-0.165,0.803-0.273C24.878,17.596,27,20.578,27,24V26z"/></svg>
    </div>
  </div>
</template>

<script>
// import filters from '@/filters';
import ChartJS from 'chart.js';
import filters from '@/filters.js';
import logo from '@/components/logo.vue';

export default {
  filters,
  components: {
    logo,
  },

  data() {
    return {
      tab: 'dashboard',

      account: {},
      pending_deals: [],
      confirmed_deals: [],

      chart: null,

      user_search: '',
      search_results: [],

      deal: {
        type: 'send',
        target: null,
        title: '',
        amount: null,
      },

      market_action: {
        market: null,
        type: null,
        amount: 100,
        leverage: 1,
        TP: 10,
        SL: 10,
      },
    };
  },

  methods: {
    selecTab(tab) {
      this.tab = tab;
    },

    searchUser() {
      if (this.user_search) {
        this.api.searchUser(this.user_search, (rs) => {
          this.search_results = rs.filter(u => u.email !== this.account.email);
        });
      }
    },

    sendDeal() {
      this.api.sendDeal(this.deal, (rs) => {
        if (rs.success) {
          this.deal.target = null;
          this.deal.title = '';
          this.deal.amount = null;
          this.search_results = '';
          this.refresh();
        }
      });
    },

    payDeal(deal) {
      if (deal.target !== this.account.email) {
        this.api.payDeal(deal.id);
      } else {
        this.toast.warning("You can't pay your onw deal.");
      }
    },

    cancelDeal(deal) {
      this.toast.question(`Are you sure you want to cancel the deal "${deal.title}" ?`, 'Confirm', {
        position: 'center',
        overlay: true,
        buttons: [
          ['<button>YES</button>', (instance, toast) => {
            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
            this.api.cancelDeal(deal.id);
          }, true],
          ['<button>NO</button>', (instance, toast) => {
            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
          }],
        ],
      });
    },

    refresh() {
      this.api.getInfos((account) => {
        this.account = account.data;
        this.pending_deals = this.account.deals.filter(d => d.DONE === 0);
        this.confirmed_deals = this.account.deals.filter(d => d.DONE === 1);

        const chartData = [];

        this.confirmed_deals.forEach((deal) => {
          let y = deal.amount;
          if (deal.giver === this.account.email) y = -deal.amount;
          chartData.push({
            t: new Date(deal.date),
            y,
          });
        });

        this.chart = new ChartJS(document.getElementById('chart').getContext('2d'), {
          type: 'bar',
          data: {
            datasets: [
              {
                label: 'Transaction',
                borderColor: '#00b176',
                borderWidth: 2,
                data: chartData,
                pointRadius: 0,
              },
            ],
          },
          options: {
            responsive: true,
            legend: { display: false },
            elements: {
              line: {
                tension: 0,
              },
            },
            scales: {
              xAxes: [{
                display: false,
                type: 'time',
                distribution: 'series',
              }],
              yAxes: [{
                distribution: 'series',
                display: true,
              }],
            },
            tooltips: {
              mode: 'index',
              intersect: true,
            },
          },
        });
      });
    },

    selectMarket(market) {
      console.log('SELECT', market);
      this.tab = 'market';

      // eslint-disable-next-line
      new TradingView.Widget({
        autosize: true,
        symbol: market,
        interval: 'D',
        timezone: 'Europe/Paris',
        theme: 'dark',
        style: 1,
        locale: 'fr',
        toolbar_bg: '#f1f3f6',
        withdateranges: true,
        hide_side_toolbar: false,
        details: true,
        container_id: 'market_chart',
      });
    },

    marketAction(market, type) {
      this.tab = 'market_action';
      this.market_action.market = market;
      this.market_action.type = type;
      console.log('ACTION', this.market_action);
    },
  },

  mounted() {
    this.refresh();
    this.api.onChanges(this.refresh);
  },
};
</script>

<style scoped>

.dashboard {
  max-width: 500px;
  margin: 0 auto;
}

.container {
  background-color: #293A48;
  margin: 0 20px 20px;
  border-radius: 5px;
  box-shadow: 3px 3px 8px #0000002e;
}

.container > .top {
  background-color: #2D4252;
  border-radius: 5px 5px 0 0;
  padding: 15px 20px;
  width: 100%;
  text-align: left;
  font-size: 18px;
  font-weight: 600;
  user-select: none;
}

.container > .content {
  padding: 10px 20px 20px;
}

.tm_container {
  margin: 0 0 20px;
  border-radius: 5px;
}

.circle {
  background-color: #2c3e50;
  color: #00b176;
  border-radius: 50%;
  width: 250px;
  margin: 150px auto 80px;
  line-height: 250px;
  font-size: 35px;
  box-shadow: 3px 3px 8px #0000002e, inset 0 0 0 5px #00b176;
  user-select: none;
}

.circle.red { color: #ff1c1c; box-shadow: 3px 3px 8px #0000002e, inset 0 0 0 5px #ff1c1c; }
.circle.yellow { color: #a5b100; box-shadow: 3px 3px 8px #0000002e, inset 0 0 0 5px #a5b100; }
.circle.green { color: #00b176; box-shadow: 3px 3px 8px #0000002e, inset 0 0 0 5px #00b176; }

.circle.red:hover { box-shadow: 3px 3px 8px #0000002e, inset 0 0 0 200px #ff1c1c; }
.circle.yellow:hover { box-shadow: 3px 3px 8px #0000002e, inset 0 0 0 200px #a5b100; }
.circle.green:hover { box-shadow: 3px 3px 8px #0000002e, inset 0 0 0 200px #00b176; }

.circle:hover { color: #2c3e50 }

.header {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  background-color: #FFF;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 3px 8px #0000002e;
  opacity: 0.9;
}

.header > .logo_text {
  font-size: 40px;
  padding-top: 20px;
  color: #2c3e50;
  text-shadow: 0 0 3px #0000002e;
  user-select: none;
}

input[type=text],
input[type=number] {
  width: 100%;
  line-height: 28px;
  font-size: 18px;
  padding: 5px 15px;
  border: none;
  background-color: #2c3e50;
  caret-color: #FFF;
  outline-style: none;
}

:not(.column) > input[type=text],
:not(.column) > input[type=number] {
  border-radius: 5px;
}

.input > input {
  width: initial;
}

.contacts_container {
  margin: 0 40px;
}

.search {
  margin: 50px 0;
  box-shadow: 3px 3px 8px #0000002e;
}

.user {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 60px;
  margin: 7px 0;
  background-color: #2c3e50;
  border-radius: 7px;
  box-shadow: 3px 3px 8px #0000002e;
  cursor: pointer;
}

.icon {
  height: 60px;
  background-color: #01aa72;
  border: solid 10px #01aa72;
  border-radius: 7px 0 0 7px;
  fill: #FFF;
}

.username {
  font-size: 18px;
}

.select {
  height: 60px;
  border: solid 4px #01aa72;
  border-radius: 0 7px 7px 0;
  fill: #FFF;
}

.switch {
  width: 90%;
  margin: 0 auto;
  color: #202b36;
  display: flex;
  justify-content: space-evenly;
  font-size: 18px;
  line-height: 25px;
  text-shadow: 0 0 3px #0000002e;
  box-shadow: 0 -3px 8px #0000002e;
}

.switch.bottom {
  margin: 200px auto 0;
}

.switch > .choice {
  cursor: pointer;
  width: 50%;
  padding: 10px;
  box-shadow: inset 0 0 0 200px #202b36;
}

.switch > .choice:not(.red) { background-color: #059263 }
.switch > .choice.red       { background-color: #b93847 }

.switch > .choice.selected {
  box-shadow: inset 0 0 0 0 #202b36;
}

.flex {
  display: flex;
  margin: 15px auto;
  background-color: #2c3e50;
  width: 90%;
  height: 50px;
  color: #218a38;
  justify-content: space-evenly;
  font-size: 18px;
  line-height: 30px;
  text-shadow: 0 0 3px #0000002e;
  box-shadow: 0 -3px 8px #0000002e;
  border-radius: 5px;
  user-select: none;
}

.flex > * {
  padding: 10px;
  height: 100%;
  fill: #FFF;
}

.flex.inverse > svg { transform: rotate(540deg) }

.columns_container {
  display: flex;
  justify-content: space-between;
  margin: 0 5px;
}

.column {
  width: 100%;
}

.input {
  margin: 7px auto;
  display: flex;
  flex-flow: row;
  align-items: center;
  justify-content: space-between;
  width: 85%;
}

.deal {
  background-color: #00b176;
  margin: 15px 5px;
  display: flex;
  flex-direction: column;
  border-radius: 5px;
  box-shadow: 3px 3px 8px #0000002e;
}

.deal > .title {
  background-color: #059263;
  display: flex;
  justify-content: space-between;
  border-radius: 5px 5px 0 0;
  padding: 15px 20px;
  width: 100%;
  text-align: left;
  font-size: 18px;
  font-weight: 600;
  user-select: none;
}

.deal > .bottom {
  display: flex;
  justify-content: space-around;
  border-bottom-left-radius: 5px;
  border-bottom-right-radius: 5px;
  box-shadow: 3px 3px 8px #00000061;
  background-color: #2c3e50;
  background-image: linear-gradient(to right, #b93847c9 50%, #059263d1 50%);
  user-select: none;
}

.deal > .bottom > * {
  width: 50%;
  font-size: 18px;
  padding: 10px;
  cursor: pointer;
}

.deal > .bottom > *.disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.deal > .bottom > :not(.cancel):not(.disabled):hover { background-color: #059263 }
.deal > .bottom > .cancel:not(.disabled):hover { background-color: #ff00008c }

.market {
  cursor: pointer;
  display: flex;
  height: 100px;
  width: calc(100% - 30px);
  margin: 10px auto;
  align-items: center;
  -webkit-tap-highlight-color: transparent;
}

.market_container {
  height: 100%;
  width: 100%;
}

.market_container iframe {
  pointer-events: none;
  height: 100%;
  width: 100%;
}

.market_sidebar {
  margin-left: -100px;
  width: 100px;
  z-index: 10;
  display: flex;
  flex-direction: column;
}

.market_sidebar_button {
  padding: 16px 0;
  user-select: none;
}

.market_sidebar_button:first-child {
  background-color: #059263;
  background-image: linear-gradient(to right, #1e222d 0%, #059263 2%);
  border-top-right-radius: 2px;
}
.market_sidebar_button:last-child {
  background-color: #b93847;
  background-image: linear-gradient(to right, #1e222d 0%, #b93847 2%);
  border-bottom-right-radius: 2px;
}

.market_action_form {
  margin: 20px 20px 0;
}

.input_title {
  line-height: 35px;
}

.separator_title {
  font-size: 20px;
  text-align: left;
  margin: 20px 0 2px 20px;
}

.grid_button {
  cursor: pointer;
  margin: 2px 1px;
  line-height: 50px;
  font-size: 22px;
  user-select: none;
}

.grid_button:not(.selected) { background-color: #293A48 }

.footer {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  color: #202b36;
  display: flex;
  justify-content: space-evenly;
  font-size: 20px;
  text-shadow: 0 0 3px #0000002e;
  box-shadow: 0 -3px 8px #0000002e;
}

.footer > .tab {
  background-color: #00b176;
  cursor: pointer;
  width: 100%;
  height: 50px;
  padding: 10px;
  fill: #FFF;
  box-shadow: inset 0 0 0 200px #202b36;
  opacity: 0.95;
}
.footer > .tab.selected { box-shadow: inset 0 0 0 0 #202b36; }

@media screen and (max-width: 400px) {
  .header > .logo_text { font-size: 0; padding-top: 0; }
}

#chart { width: calc(100% - 40px) !important }

#market_chart {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  width: 100%;
  height: calc(100% - 50px);
}

#market_analysis {
  user-select: none;
  width: 100%;
  max-width: 430px;
  height: 420px;
  margin-top: -25px
}

</style>
